import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { resolve, extname } from 'node:path';
import { chromium } from 'playwright-core';

test('compiled controls and styles work under enforced CSP while injected inline code is blocked', async () => {
    const root = resolve('public');
    const manifest = JSON.parse(readFileSync(resolve(root, 'build/manifest.json'), 'utf8'));
    const bundle = '/build/' + manifest['resources/js/security.js'].file;
    const css = '/build/' + manifest['resources/css/csp-utilities.css'].file;
    const pdf = '/build/' + manifest['resources/js/reports/pdf-tools.js'].file;
    const source = readFileSync('resources/js/core/action-handlers.js', 'utf8');
    const findAction = (expression, exact = false) => {
        for (const match of source.matchAll(/'(action-[a-f\d]+)': function \(event\) \{([\s\S]*?)(?=\n    \},)/g)) {
            if (exact ? match[2].trim().replace(/;$/, '') === expression : match[2].includes(expression)) return match[1];
        }
        throw new Error('Missing action: ' + expression);
    };
    const faq = findAction('toggleFaq(this)');
    const stop = findAction('event.stopPropagation()', true);
    const reset = findAction("openResetModal('users'");
    const payload = `O'Neil <img src=x onerror=alert(1)> "quoted"`;
    const escape = value => value.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const nonce = 'security-fixture-nonce-123456';
    const server = createServer((req, res) => {
        if (req.url === '/') {
            res.setHeader('Content-Type', 'text/html');
            res.setHeader('Content-Security-Policy', `default-src 'self'; script-src 'self' 'nonce-${nonce}'; script-src-attr 'none'; style-src 'self' 'nonce-${nonce}'; style-src-attr 'none'; img-src 'self' data:; object-src 'none'`);
            res.end(`<!doctype html><html><head><meta name="csp-nonce" content="${nonce}"><link rel="stylesheet" href="${css}"><script nonce="${nonce}">
                window.violations=[];window.violationDetails=[];document.addEventListener('securitypolicyviolation',e=>{violations.push(e.effectiveDirective);violationDetails.push({source:e.sourceFile,line:e.lineNumber,sample:e.sample});});
                window.faqCalls=0;window.toggleFaq=function(button){faqCalls++;button.textContent='Opened';};
                window.openResetModal=function(...args){window.received=args;};
                </script><script type="module" src="${bundle}"></script><script type="module" src="${pdf}"></script>
                <script defer src="/vendor/sienna/sienna-accessibility.umd.js"></script></head><body>
                <button id="faq" data-ui-click="${faq}">Open</button>
                <div id="parent"><button id="stop" data-ui-click="${stop}">Stop</button></div>
                <button id="reset" data-ui-click="${reset}" data-ui-click-arg-0="42" data-ui-click-arg-1="${escape(JSON.stringify(payload))}">Reset</button>
                <div id="progress" data-ui-style="width: 37%; color: rgb(12, 34, 56); --label: 'a;b'">Progress</div>
                <div id="hidden" data-ui-presentation="presentation-d0466aa33fa8">Hidden</div>
                <iframe id="preview" sandbox="allow-same-origin" title="Template preview"></iframe>
                <button id="injected">Untrusted</button>
                </body></html>`);
            return;
        }
        const file = resolve(root, '.' + decodeURIComponent(req.url.split('?')[0]));
        if (!file.startsWith(root + '/') && !file.startsWith(root + '\\')) { res.writeHead(403).end(); return; }
        if (!existsSync(file)) { res.writeHead(404).end(); return; }
        res.setHeader('Content-Type', { '.js': 'text/javascript', '.css': 'text/css', '.json': 'application/json' }[extname(file)] || 'application/octet-stream');
        res.end(readFileSync(file));
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const executable = process.env.BROWSER_EXECUTABLE || [
        'C:/Program Files/BraveSoftware/Brave-Browser/Application/brave.exe',
        'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
        'C:/Program Files/Google/Chrome/Application/chrome.exe',
    ].find(existsSync);
    let browser;
    try {
        browser = await chromium.launch({ executablePath: executable, headless: true });
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`http://127.0.0.1:${server.address().port}`);
        await page.click('#faq');
        assert.equal(await page.textContent('#faq'), 'Opened');
        await page.evaluate(() => { document.querySelector('#faq').onclick = function() { this.textContent = 'Override'; }; });
        await page.click('#faq');
        assert.equal(await page.textContent('#faq'), 'Override');
        assert.equal(await page.evaluate(() => window.faqCalls), 1);
        await page.click('#reset');
        assert.deepEqual(await page.evaluate(() => window.received), ['users', 42, payload]);
        await page.evaluate(() => {
            window.parentClicks=0;
            document.querySelector('#parent').addEventListener('click',()=>parentClicks++);
        });
        await page.click('#stop');
        assert.equal(await page.evaluate(() => window.parentClicks), 0);
        assert.equal(await page.$eval('#hidden', e => getComputedStyle(e).display), 'none');
        assert.equal(await page.$eval('#hidden', e => e.style.display), 'none');
        await page.$eval('#hidden', e => { e.style.display = 'block'; });
        assert.equal(await page.$eval('#hidden', e => getComputedStyle(e).display), 'block');
        assert.equal(await page.$eval('#progress', e => e.style.width), '37%');
        assert.equal(await page.$eval('#progress', e => e.style.getPropertyValue('--label')), "'a;b'");
        await page.evaluate(faq => {
            const node = document.createElement('button');
            node.id='dynamic';node.setAttribute('data-ui-click',faq);node.textContent='Dynamic';document.body.append(node);
        }, faq);
        await page.click('#dynamic');
        assert.equal(await page.textContent('#dynamic'), 'Opened');
        assert.deepEqual(await page.evaluate(() => window.violations), []);
        await page.evaluate(() => {
            document.querySelector('#preview').srcdoc = window.prepareDocumentPreview('<style>.sample { color: rgb(10, 20, 30); }</style><p class="sample" style="font-weight: 700" onclick="parent.compromised=true">Preview</p><script>parent.compromised=true</'+'script>');
        });
        const preview = page.frameLocator('#preview').locator('.sample');
        await preview.waitFor();
        assert.equal(await preview.evaluate(e => getComputedStyle(e).fontWeight), '700');
        assert.equal(await preview.evaluate(e => getComputedStyle(e).color), 'rgb(10, 20, 30)');
        assert.equal(await page.frameLocator('#preview').locator('script').count(), 0);
        // The deliberately hostile input's inline attributes are blocked even
        // while parsed inertly, then removed from the rendered preview.
        assert.deepEqual(await page.evaluate(() => [...new Set(window.violations)].sort()), ['script-src-attr', 'style-src-attr']);
        await page.evaluate(() => { window.violations = []; });
        const pdfResult = await page.evaluate(async () => {
            const { canvasRenderer, jsPDF } = await window.loadReportPdfTools();
            const canvas = await canvasRenderer(document.querySelector('#faq'), { logging: false });
            const pdf = new jsPDF();
            pdf.text('CSP export check', 10, 10);
            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 10, 20, 40, 15);
            return { canvasWidth: canvas.width, bytes: pdf.output('arraybuffer').byteLength };
        });
        assert.ok(pdfResult.canvasWidth > 0);
        assert.ok(pdfResult.bytes > 100);
        assert.deepEqual(await page.evaluate(() => window.violations), []);
        await page.evaluate(() => document.querySelector('#injected').setAttribute('onclick', 'window.compromised=true'));
        await page.click('#injected');
        await page.waitForFunction(() => window.violations.includes('script-src-attr'));
        assert.equal(await page.evaluate(() => window.compromised), undefined);
        assert.deepEqual(errors, []);
    } finally {
        await browser?.close();
        await new Promise(resolve => server.close(resolve));
    }
});
