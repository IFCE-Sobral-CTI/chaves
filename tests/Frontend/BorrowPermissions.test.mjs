import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import React from 'react';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let Show;
let DeleteModal;
let Receive;
const originalRoute = globalThis.route;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ default: Show } = await server.ssrLoadModule('/resources/js/Pages/Keys/Borrow/Show.jsx'));
    ({ default: DeleteModal } = await server.ssrLoadModule('/resources/js/Components/Dashboard/DeleteModal.jsx'));
    ({ default: Receive } = await server.ssrLoadModule('/resources/js/Pages/Keys/Borrow/Components/Receive.jsx'));
    globalThis.route = name => name;
});

after(async () => {
    globalThis.route = originalRoute;
    await server?.close();
});

function components(node, type) {
    if (!React.isValidElement(node)) return [];
    return [
        ...(node.type === type ? [node] : []),
        ...React.Children.toArray(node.props.children).flatMap(child => components(child, type)),
    ];
}

function page(can, closed = false) {
    const key = { id: 11, number: 101, room: { description: 'Sala' } };
    const pendingKey = { id: 12, number: 102, room: { description: 'Outra sala' } };
    const borrow = {
        id: 1, employee: { name: 'Mutuário' }, user: { name: 'Operador' },
        keys: closed ? [key] : [key, pendingKey], devolution: closed ? '15/10/2026 12:00:00' : null,
        received: [{ id: 7, receiver: 'Recepção', user: { name: 'Operador' }, keys: [key] }],
    };
    return Show({ borrow, received: [11], can });
}

test('receiving permission does not show receipt deletion', () => {
    const tree = page({ receive: true, deleteReceived: false });
    assert.equal(components(tree, DeleteModal).length, 0);
    assert.equal(components(tree, Receive).length, 1);
});

test('receipt deletion permission works independently of receiving and closing', () => {
    for (const closed of [false, true]) {
        const tree = page({ receive: false, deleteReceived: true }, closed);
        const dialogs = components(tree, DeleteModal);
        assert.equal(dialogs.length, 1);
        assert.equal(dialogs[0].props.url, 'borrows.receive.destroy');
        assert.equal(dialogs[0].props.forTable, true);
        assert.equal(components(tree, Receive).length, 0);
    }
});

test('borrow deletion permission does not show receipt deletion', () => {
    const dialogs = components(page({ delete: true, deleteReceived: false }), DeleteModal);
    assert.equal(dialogs.length, 1);
    assert.equal(dialogs[0].props.url, 'borrows.destroy');
});
