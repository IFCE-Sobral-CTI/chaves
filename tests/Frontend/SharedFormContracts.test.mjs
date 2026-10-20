import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';

let server;
let RuleForm;
let PeriodFilterForm;
let FormActions;

before(async () => {
    server = await createServer({
        configFile: false,
        cacheDir: 'node_modules/.cache/frontend-tests',
        plugins: [react()],
        resolve: { alias: { '@': '/resources/js' } },
        server: { middlewareMode: true, hmr: false, ws: false, watch: null },
        appType: 'custom',
    });
    ({ default: RuleForm } = await server.ssrLoadModule('/resources/js/Pages/Rule/Components/Form.jsx'));
    ({ default: PeriodFilterForm } = await server.ssrLoadModule('/resources/js/Pages/Reports/Components/PeriodFilterForm.jsx'));
    ({ default: FormActions } = await server.ssrLoadModule('/resources/js/Components/Form/FormActions.jsx'));
});

after(async () => {
    await server?.close();
});

function find(node, predicate) {
    if (!React.isValidElement(node)) return undefined;
    if (predicate(node)) return node;
    for (const child of React.Children.toArray(node.props.children)) {
        const found = find(child, predicate);
        if (found) return found;
    }
}

test('editing a rule preserves a non-first group and displays its validation error', () => {
    const updates = [];
    const props = {
        data: { description: 'Consultar salas', control: 'rooms.view', group_id: 7 },
        groups: [{ id: 1, description: 'Usuários' }, { id: 7, description: 'Salas' }],
        errors: { group_id: 'Selecione um grupo válido.' },
        setData: (...args) => updates.push(args), handleSubmit() {},
        backUrl: '/admin/rules/9', processing: false,
    };
    const html = renderToStaticMarkup(React.createElement(RuleForm, props));

    assert.match(html, /<option value="7" selected="">Salas<\/option>/);
    assert.match(html, /Selecione um grupo válido\./);
    assert.match(html, /<label for="group_id"/);
    const control = find(RuleForm(props), node => node.props.name === 'group_id');
    control.props.handleChange({ target: { name: 'group_id', value: '1' } });
    assert.deepEqual(updates, [['group_id', '1']]);
});

test('period filters preserve draft values, errors and applied-filter clearing', () => {
    const props = {
        data: { start: '2026-10-01', end: '2026-10-20' },
        filters: { start: null, end: null },
        errors: { end: 'A data final deve ser posterior à inicial.' },
        onChange() {}, onSubmit() {}, processing: true,
        clearUrl: '/admin/reports/rooms',
    };
    const draft = renderToStaticMarkup(React.createElement(PeriodFilterForm, props));

    assert.match(draft, /name="start"[^>]*value="2026-10-01"/);
    assert.match(draft, /name="end"[^>]*value="2026-10-20"/);
    assert.match(draft, /A data final deve ser posterior à inicial\./);
    assert.match(draft, /type="submit"[^>]*disabled=""/);
    assert.doesNotMatch(draft, /Limpar/);

    const applied = renderToStaticMarkup(React.createElement(PeriodFilterForm, {
        ...props, filters: { start: '2026-10-01', end: null }, processing: false,
    }));
    assert.match(applied, /href="\/admin\/reports\/rooms"/);
    assert.match(applied, /Limpar/);
});

test('shared form actions disable submission and preserve the caller return URL', () => {
    const html = renderToStaticMarkup(React.createElement(FormActions, {
        processing: true, backUrl: '/admin/groups/12',
    }));

    assert.match(html, /type="submit"[^>]*disabled=""/);
    assert.match(html, /href="\/admin\/groups\/12"/);
    assert.match(html, /Enviar/);
    assert.match(html, /Voltar/);
});
