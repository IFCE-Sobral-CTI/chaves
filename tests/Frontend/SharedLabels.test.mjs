import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let BorrowSituationBadge;
let employeeTypeLabel;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, watch: null },
        appType: 'custom',
    });
    ({ default: BorrowSituationBadge } = await server.ssrLoadModule('/resources/js/Components/Dashboard/BorrowSituationBadge.jsx'));
    ({ default: employeeTypeLabel } = await server.ssrLoadModule('/resources/js/Pages/Keys/Employee/employeeTypeLabel.js'));
});

after(async () => {
    await server?.close();
});

test('borrow badge maps all server situations and keeps an unknown value visible', () => {
    for (const [situation, label, color] of [
        ['devolvido', 'Devolvido', 'bg-green-500'],
        ['aberto', 'Aberto', 'bg-yellow-500'],
        ['atrasado', 'Atrasado', 'bg-red-500'],
    ]) {
        const badge = BorrowSituationBadge({ situation });
        assert.equal(badge.props.children, label);
        assert.ok(badge.props.className.split(' ').includes(color));
    }

    const unknown = BorrowSituationBadge({ situation: 'indefinido' });
    assert.equal(unknown.props.children, 'indefinido');
    assert.match(unknown.props.className, /bg-neutral-400/);
});

test('employee type labels are shared for each known type', () => {
    assert.deepEqual([1, 2, 3, 4].map(employeeTypeLabel), ['Servidor', 'Colaborador', 'Discente', 'Externo']);
});
