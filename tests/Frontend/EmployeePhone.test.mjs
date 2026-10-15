import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let formatPhone;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ formatPhone } = await server.ssrLoadModule('/resources/js/Pages/Keys/Employee/phone.js'));
});

after(async () => {
    await server?.close();
});

test('formats Brazilian landline and mobile phone numbers while typing', () => {
    assert.equal(formatPhone('8833334444'), '(88) 3333-4444');
    assert.equal(formatPhone('88999998888'), '(88) 99999-8888');
    assert.equal(formatPhone('(88) 99999-8888'), '(88) 99999-8888');
    assert.equal(formatPhone('88'), '(88');
    assert.equal(formatPhone(''), '');
});

test('keeps legacy numbers longer than the Brazilian mask intact', () => {
    assert.equal(formatPhone('5588999998888'), '5588999998888');
});
