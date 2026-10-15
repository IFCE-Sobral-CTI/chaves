import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let Search;
let SearchItem;
const keys = [
    { id: 11, number: 101, room: { description: 'Sala Norte' } },
    { id: 22, number: 202, room: { description: 'Sala Sul' } },
];

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ default: Search } = await server.ssrLoadModule('/resources/js/Pages/Keys/Borrow/Components/Search.jsx'));
    ({ default: SearchItem } = await server.ssrLoadModule('/resources/js/Pages/Keys/Borrow/Components/SearchItem.jsx'));
});

after(async () => {
    await server?.close();
});

function renderSelection(data, values) {
    const html = renderToStaticMarkup(React.createElement(Search, { data, values, onChange() {} }));
    return [...html.matchAll(/<tbody[^>]*>([\s\S]*?)<\/tbody>/g)].map(match => match[1]);
}

test('renders an existing selection immediately, with the correct actions', () => {
    const [available, selected] = renderSelection(keys, [22]);
    assert.match(available, /Adicionar chave 101 - Sala Norte/);
    assert.doesNotMatch(available, /Sala Sul/);
    assert.match(selected, /Remover chave 202 - Sala Sul/);
    assert.doesNotMatch(selected, /Sala Norte/);
});

test('renders refreshed data and parent selections, including reset', () => {
    const refreshed = [{ ...keys[1], room: { description: 'Sala Sul atualizada' } }, keys[0]];
    const [available, selected] = renderSelection(refreshed, [22]);
    assert.match(available, /Sala Norte/);
    assert.match(selected, /Sala Sul atualizada/);

    const [afterChange, newSelection] = renderSelection(refreshed, [11]);
    assert.match(afterChange, /Adicionar chave 202/);
    assert.match(newSelection, /Remover chave 101/);
    assert.doesNotMatch(newSelection, /Sala Sul/);

    const [afterReset, cleared] = renderSelection(refreshed, []);
    assert.match(afterReset, /Sala Norte/);
    assert.match(afterReset, /Sala Sul atualizada/);
    assert.equal(cleared, '');
});

test('row activation forwards the key ID and its button cannot submit the form', () => {
    const calls = [];
    for (const icon of ['add', 'rem']) {
        const props = { item: keys[0], icon, toggleItemHandler: id => calls.push(id) };
        const row = SearchItem(props);
        row.props.onClick();
        const html = renderToStaticMarkup(React.createElement(SearchItem, props));
        assert.match(html, /<button type="button"/);
        assert.match(html, /aria-hidden="true"/);
        assert.match(html, icon === 'rem' ? /Remover chave 101/ : /Adicionar chave 101/);
        assert.match(html, /<path /);
    }
    assert.deepEqual(calls, [11, 11]);
});
