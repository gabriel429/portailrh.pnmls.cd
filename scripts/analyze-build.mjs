import { readFileSync, readdirSync } from 'fs';

const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
const referenced = new Set();

for (const entry of Object.values(manifest)) {
    if (entry.file) referenced.add(entry.file.replace('assets/', ''));
    for (const k of (entry.imports || [])) {
        const e = manifest[k];
        if (e?.file) referenced.add(e.file.replace('assets/', ''));
    }
    for (const f of (entry.css || [])) referenced.add(f.replace('assets/', ''));
    for (const a of (entry.assets || [])) referenced.add(a.replace('assets/', ''));
}

const actual = readdirSync('public/build/assets');
const orphans = actual.filter(f => !referenced.has(f));

console.log(`Manifest entries : ${Object.keys(manifest).length}`);
console.log(`Referenced       : ${referenced.size}`);
console.log(`On disk          : ${actual.length}`);
console.log(`Orphelins        : ${orphans.length}`);
if (orphans.length > 0) {
    console.log('\n--- Fichiers orphelins ---');
    orphans.forEach(f => console.log(' ORPHAN:', f));
}
