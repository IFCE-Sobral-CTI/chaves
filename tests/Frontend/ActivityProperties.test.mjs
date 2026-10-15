import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let Properties;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ default: Properties } = await server.ssrLoadModule('/resources/js/Pages/Activity/Components/Properties.jsx'));
});

after(async () => {
    await server?.close();
});

const render = props => renderToStaticMarkup(React.createElement(Properties, props));

test('shows v5 model changes alongside custom metadata', () => {
    const html = render({
        attributeChanges: { attributes: { observation: 'Corrigido' }, old: { observation: 'Anterior' } },
        properties: { key_ids: [11, 22] },
    });

    assert.match(html, /Novos valores.*Corrigido.*Antigos valores.*Anterior.*Detalhes.*\[11,22\]/);
});

test('keeps legacy changes visible without requiring v5 data', () => {
    const html = render({ properties: { attributes: { number: 202 }, old: { number: 101 } } });

    assert.match(html, /Novos valores.*202.*Antigos valores.*101/);
    assert.doesNotMatch(html, /Detalhes/);
});

test('shows deletion snapshots and preserves null, zero, false and empty arrays', () => {
    const html = render({ properties: {
        before: { pending_key_ids: [], received_by: null, count: 0, closed: false },
        after: null,
        received_id: 42,
    } });

    assert.match(html, /Novos valores.*null.*Antigos valores.*\[\].*null.*0.*false.*Detalhes.*42/);
});

test('shows nested transition values and escapes user content', () => {
    const html = render({ properties: {
        before: { key_ids: [11] },
        after: { key_ids: [22], metadata: { count: 2 }, observation: '<script>alert(1)</script>' },
    } });

    assert.match(html, /\[22\]/);
    assert.match(html, /&quot;count&quot;:2/);
    assert.match(html, /&lt;script&gt;alert\(1\)&lt;\/script&gt;/);
    assert.doesNotMatch(html, /<script>|\[object Object\]/);
});

test('handles activities without properties or changes', () => {
    assert.equal(render({ properties: null, attributeChanges: null }), '<div class="flex"></div>');
});
