export function readActionArg(element, event, index, json) {
    const value = element.getAttribute(`data-ui-${event}-arg-${index}`) ?? '';
    if (json) return JSON.parse(value);
    // Old generated markup escaped string arguments for JavaScript. Decode scalar
    // escapes without interpreting the value as code or resolving any properties.
    return value.replace(/\\(?:u([\da-f]{4})|x([\da-f]{2})|([\\'"nrtbf]))/gi,
        (_, unicode, hex, character) => unicode || hex
            ? String.fromCharCode(parseInt(unicode || hex, 16))
            : ({ n: '\n', r: '\r', t: '\t', b: '\b', f: '\f' }[character] ?? character));
}
