import { readFileSync, writeFileSync, mkdirSync, cpSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const from = `${root}node_modules/sienna-accessibility/dist/`;
const to = `${root}public/vendor/sienna/`;
mkdirSync(to, { recursive: true });
let script = readFileSync(`${from}sienna-accessibility.umd.js`, 'utf8');
if (!script.includes('document.createElement("style")')) {
    throw new Error('Review Sienna style injection before updating the vendored asset.');
}
// Only the trusted library's style creation receives a nonce. Never nonce
// scripts or markup submitted by users or returned by external services.
script = script.replaceAll('document.createElement("style")',
    'Object.assign(document.createElement("style"),{nonce:document.querySelector(\'meta[name="csp-nonce"]\')?.content||""})');
const styleBranch = 'a==="style"&&typeof f=="object"?Object.assign(r.style,f):';
if (!script.includes(styleBranch)) throw new Error('Review Sienna DOM style handling before updating.');
script = script.replace(styleBranch,
    'a==="style"?(typeof f==="object"?Object.assign(r.style,f):String(f).split(";").forEach(d=>{const c=d.indexOf(":");if(c>0){const v=d.slice(c+1).trim();r.style.setProperty(d.slice(0,c).trim(),v.replace(/\\s*!important\\s*$/i,""),/!important\\s*$/i.test(v)?"important":"")}})):');
script = script.replaceAll('https://cdn.jsdelivr.net/npm/sienna-accessibility/dist/', '/vendor/sienna/');
// Normalize only Sienna's widget-appearance CSS priorities.
// Keep accessibility feature styles and runtime behavior unchanged.
const themeSelectorPattern =
    /\.asw-(?:menu|card|select|btn|profile|footer|items)\b/;

let normalizedThemeDeclarations = 0;

script = script.replace(
    /([^{}]*\{)([^{}]*)(\})/g,
    (original, opening, declarations, closing) => {
        if (
            !themeSelectorPattern.test(opening) ||
            !/\s*!important\b/i.test(declarations)
        ) {
            return original;
        }

        const cleaned = declarations.replace(
            /\s*!important\b/gi,
            () => {
                normalizedThemeDeclarations++;
                return '';
            }
        );

        return opening + cleaned + closing;
    }
);

if (normalizedThemeDeclarations === 0) {
    throw new Error(
        'No Sienna theme declarations were normalized. ' +
        'Inspect the installed UMD bundle before continuing.'
    );
}

console.log(
    `Normalized ${normalizedThemeDeclarations} Sienna theme declarations.`
);
writeFileSync(`${to}sienna-accessibility.umd.js`, script);
cpSync(`${from}fonts`, `${to}fonts`, { recursive: true });
cpSync(`${from}locales`, `${to}locales`, { recursive: true });
