// Document templates may contain author-provided CSS, but previews never run
// author-provided JavaScript. The iframe also excludes allow-scripts.
window.prepareDocumentPreview = function(content) {
    const preview = document.implementation.createHTMLDocument('Document preview');
    const template = preview.createElement('template');
    template.innerHTML = String(content || '');
    const fragment = template.content;
    const nonce = document.querySelector('meta[name="csp-nonce"]')?.content || '';
    fragment.querySelectorAll('script, iframe, object, embed, base, meta[http-equiv]').forEach(node => node.remove());
    const rules = [];
    for (const [index, element] of [...fragment.querySelectorAll('*')].entries()) {
        for (const attribute of [...element.attributes]) {
            if (/^on/i.test(attribute.name)) element.removeAttribute(attribute.name);
        }
        if (element.hasAttribute('style')) {
            const id = `preview-${index}`;
            element.setAttribute('data-preview-style', id);
            rules.push(`[data-preview-style="${id}"] { ${element.getAttribute('style').replace(/<\//g, '<\\/')} }`);
            element.removeAttribute('style');
        }
    }
    fragment.querySelectorAll('style').forEach(style => { style.nonce = nonce; });
    preview.body.append(fragment);
    const styles = preview.createElement('style');
    styles.nonce = nonce;
    styles.textContent = rules.join('\n');
    preview.head.append(styles);
    const utilities = document.querySelector('link[href*="csp-utilities"]');
    if (utilities) preview.head.append(utilities.cloneNode());
    // Browsers hide nonce attributes during DOM serialization. Restore the
    // current nonce only on these script-free preview styles after serialization.
    const html = preview.documentElement.outerHTML.replace(/<style\b[^>]*>/gi, tag =>
        tag.replace(/\snonce=(?:"[^"]*"|'[^']*')/gi, '').replace(/>$/, ` nonce="${nonce}">`));
    return '<!doctype html>' + html;
};
