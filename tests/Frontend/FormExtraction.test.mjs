import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { createServer } from 'vite';
import reactPlugin from '@vitejs/plugin-react';

let server;
let LocalSelect;
let filterLocalSelectOptions;
let handleEmployeeChange;
let getBorrowableKeyError;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [reactPlugin()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ default: LocalSelect, filterLocalSelectOptions } = await server.ssrLoadModule('/resources/js/Components/Form/LocalSelect.jsx'));
    ({ handleEmployeeChange, getBorrowableKeyError } = await server.ssrLoadModule('/resources/js/Pages/Keys/Employee/Components/Form.jsx'));
});

after(async () => {
    await server?.close();
});

test('local selector preserves edit selection and associates label and error', () => {
    const html = renderToStaticMarkup(React.createElement(LocalSelect, {
        id: 'room_id', label: 'Sala', emptyName: 'Nenhuma sala selecionada',
        data: [{ id: 1, description: 'Sala 1' }, { id: 2, description: 'Sala 2' }],
        value: '2', error: 'Selecione uma sala.', onChange() {},
    }));

    assert.match(html, /<label for="room_id-trigger"[^>]*>Sala<\/label>/);
    assert.match(html, /Sala 2/);
    assert.match(html, /Selecione uma sala\./);
    assert.match(html, /aria-describedby="room_id-error"/);
});

test('employee form shows an indexed borrowable key error beyond the first item', () => {
    assert.equal(getBorrowableKeyError({ 'borrowable_key_ids.2': 'Chave inválida.' }), 'Chave inválida.');
    assert.equal(getBorrowableKeyError({ borrowable_key_ids: 'Selecione pelo menos uma chave.' }), 'Selecione pelo menos uma chave.');
});

test('local selector filters descriptions without changing source IDs', () => {
    const options = [{ id: 1, description: 'Bloco A' }, { id: '2', description: 'Bloco B' }];
    assert.deepEqual(filterLocalSelectOptions(options, 'bLoCo b'), [options[1]]);
    assert.deepEqual(options.map((item) => item.id), [1, '2']);
});

test('employee change keeps restricted keys and clears them for other affiliations', () => {
    const changes = [];
    const setData = (...args) => changes.push(args);
    handleEmployeeChange(setData, { target: { name: 'type', value: '3' } });
    assert.deepEqual(changes, [['type', '3']]);

    changes.length = 0;
    handleEmployeeChange(setData, { target: { name: 'type', value: '1' } });
    assert.deepEqual(changes, [['type', '1'], ['borrowable_key_ids', []]]);

    changes.length = 0;
    handleEmployeeChange(setData, { target: { name: 'tel', value: '88999998888' } });
    assert.deepEqual(changes, [['tel', '(88) 99999-8888']]);
});
