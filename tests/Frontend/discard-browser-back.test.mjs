import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { chromium } from 'playwright-core';

const component = readFileSync(new URL('../../resources/views/components/discard-changes.blade.php', import.meta.url), 'utf8');
const markup = component.slice(0, component.indexOf('<script'));
const script = component.match(/<script\b[^>]*>([\s\S]*?)<\/script>/)[1];

test('browser Back uses the shared discard confirmation under CSP', async t => {
    let cleanupCalls = 0;
    const server = createServer((req, res) => {
        if (req.url === '/cleanup') {
            cleanupCalls++;
            res.writeHead(204).end();
            return;
        }

        res.setHeader('Content-Type', 'text/html');
        res.setHeader('Content-Security-Policy', "default-src 'self'; script-src 'nonce-discard-test'; script-src-attr 'none'; style-src 'nonce-discard-test'");

        if (req.url === '/previous') {
            res.end('<a href="/form" id="start">Start form</a>');
            return;
        }

        const mode = new URL(req.url, 'http://localhost').searchParams.get('mode');
        const wrapper = mode === 'hidden-modal' ? '<div class="ui-modal">' : '<div>';
        res.end(`<!doctype html><html><head><style nonce="discard-test">
            .discard-modal-overlay, .ui-modal { display: none; }
            .discard-modal-overlay.open, .ui-modal.open { display: block; }
            .discard-modal-overlay.open { position: fixed; inset: 0; background: white; }
            </style></head><body>${wrapper}
            <form id="draft" ${mode === 'untracked' ? '' : 'data-discard-form'} ${mode === 'cleanup' ? 'data-discard-handler="cleanupDraft"' : ''}>
                <input id="value" name="value" value="initial">
            </form></div>
            <a id="systemBack" href="/previous" data-discard-navigation data-discard-form-target="#draft">Back</a>
            ${markup}<script nonce="discard-test">${script}</script>
            <script nonce="discard-test">
                window.cleanupShouldFail = false;
                window.cleanupCompleted = 0;
                window.cleanupDraft = async () => {
                    await fetch('/cleanup', { method: 'POST' });
                    window.cleanupCompleted++;
                    return !window.cleanupShouldFail;
                };
            </script></body></html>`);
    });

    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const executablePath = process.env.BROWSER_EXECUTABLE || [
        'C:/Program Files/BraveSoftware/Brave-Browser/Application/brave.exe',
        'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
        'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
        'C:/Program Files/Google/Chrome/Application/chrome.exe',
    ].find(existsSync);
    let browser;
    const errors = [];

    try {
        browser = await chromium.launch({ executablePath, headless: true });
        const origin = `http://127.0.0.1:${server.address().port}`;
        const openForm = async (mode = '') => {
            const page = await browser.newPage();
            page.on('pageerror', error => errors.push(error.message));
            await page.goto(`${origin}/previous`);
            await page.goto(`${origin}/form${mode ? '?mode=' + mode : ''}`);
            return page;
        };
        const back = page => page.evaluate(() => history.back());
        const modal = page => page.locator('#globalDiscardModal');
        const waitForModal = page => page.locator('#globalDiscardModal.open').waitFor();
        const waitForPrevious = page => page.waitForURL(`${origin}/previous`);

        await t.test('Keep Editing and Escape preserve edits and repeated Back stays protected', async () => {
            const page = await openForm();
            await page.fill('#value', 'unsaved changes');
            const historyLength = await page.evaluate(() => history.length);
            await back(page);
            await waitForModal(page);
            await page.click('[data-discard-keep]');
            assert.equal(await page.inputValue('#value'), 'unsaved changes');
            await back(page);
            await waitForModal(page);
            await page.keyboard.press('Escape');
            assert.equal(await modal(page).getAttribute('aria-hidden'), 'true');
            await back(page);
            await waitForModal(page);
            assert.equal(await page.evaluate(() => history.length), historyLength);
            await page.click('[data-discard-confirm]');
            await waitForPrevious(page);
            await page.close();
        });

        await t.test('unchanged and submitting forms return directly to the previous page', async () => {
            for (const submitting of [false, true]) {
                const page = await openForm();
                if (submitting) {
                    await page.fill('#value', 'submitted changes');
                    await page.evaluate(() => DiscardChanges.markSubmitting(document.querySelector('#draft')));
                }
                await back(page);
                await waitForPrevious(page);
                await page.close();
            }
        });

        await t.test('hidden procedure tracking forms still prompt and run cleanup before leaving', async () => {
            const page = await openForm('cleanup');
            await page.evaluate(() => {
                document.querySelector('#draft').hidden = true;
                document.querySelector('#value').value = 'procedure started';
                window.cleanupShouldFail = true;
            });
            const before = cleanupCalls;
            await back(page);
            await waitForModal(page);
            await page.click('[data-discard-confirm]');
            await page.waitForFunction(() => window.cleanupCompleted === 1);
            assert.equal(await modal(page).getAttribute('aria-hidden'), 'false');
            assert.equal(cleanupCalls, before + 1);
            assert.match(page.url(), /\/form\?mode=cleanup$/);
            await page.evaluate(() => { window.cleanupShouldFail = false; });
            await page.click('[data-discard-confirm]');
            await waitForPrevious(page);
            assert.equal(cleanupCalls, before + 2);
            await page.close();
        });

        await t.test('closed modal drafts do not block leaving the page', async () => {
            const page = await openForm('hidden-modal');
            await page.evaluate(() => { document.querySelector('#value').value = 'hidden draft'; });
            await back(page);
            await waitForPrevious(page);
            await page.close();
        });

        await t.test('open modal drafts can be saved before browser Back continues', async () => {
            const page = await openForm('hidden-modal');
            await page.evaluate(() => {
                document.querySelector('.ui-modal').classList.add('open');
                document.querySelector('#draft').dataset.discardSaveHandler = 'saveDraft';
                window.savedDraft = null;
                window.saveDraft = async () => {
                    window.savedDraft = document.querySelector('#value').value;
                    return false;
                };
            });
            await page.fill('#value', 'modal draft');
            await back(page);
            await waitForModal(page);
            await page.click('[data-discard-save]');
            assert.equal(await page.evaluate(() => window.savedDraft), 'modal draft');
            assert.equal(await modal(page).getAttribute('aria-hidden'), 'false');
            await page.evaluate(() => { window.saveDraft = async () => true; });
            await page.click('[data-discard-save]');
            await waitForPrevious(page);
            await page.close();
        });

        await t.test('same-page hash navigation and its Back do not prompt for discard', async () => {
            const page = await openForm();
            await page.fill('#value', 'anchor draft');
            await page.evaluate(() => { location.hash = 'details'; });
            await page.waitForURL(`${origin}/form#details`);
            assert.equal(await modal(page).getAttribute('aria-hidden'), 'true');
            await page.reload();
            await page.fill('#value', 'anchor draft after reload');
            await back(page);
            await page.waitForURL(`${origin}/form`);
            assert.equal(await modal(page).getAttribute('aria-hidden'), 'true');
            await back(page);
            await waitForModal(page);
            await page.close();
        });

        await t.test('pages without tracked forms keep normal browser Back behavior', async () => {
            const page = await openForm('untracked');
            await page.fill('#value', 'untracked changes');
            await back(page);
            await waitForPrevious(page);
            await page.close();
        });

        await t.test('reload does not add another history entry and browser Back remains protected', async () => {
            const page = await openForm();
            const historyLength = await page.evaluate(() => history.length);
            await page.reload();
            assert.equal(await page.evaluate(() => history.length), historyLength);
            await page.fill('#value', 'after reload');
            await back(page);
            await waitForModal(page);
            await page.click('[data-discard-confirm]');
            await waitForPrevious(page);
            await page.goForward();
            await page.waitForURL(`${origin}/form`);
            await page.fill('#value', 'after forward');
            await back(page);
            await waitForModal(page);
            await page.close();
        });

        await t.test('the existing system Back link still uses the same modal', async () => {
            const page = await openForm();
            await page.fill('#value', 'system back draft');
            await page.click('#systemBack');
            await waitForModal(page);
            await page.click('[data-discard-keep]');
            assert.equal(await page.inputValue('#value'), 'system back draft');
            await page.close();
        });

        assert.deepEqual(errors, []);
    } finally {
        await browser?.close();
        await new Promise(resolve => server.close(resolve));
    }
});
