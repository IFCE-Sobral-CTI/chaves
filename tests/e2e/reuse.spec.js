import { readFile } from 'node:fs/promises';
import { test, expect } from './fixtures.js';

for (const resource of ['activities', 'blocks', 'borrows', 'employees', 'groups', 'keys', 'permissions', 'rooms', 'rules', 'users']) {
    test(`${resource} list renders with working pagination`, async ({ page }) => {
        await page.goto(`/admin/${resource}`);
        await expect(page.locator('table').first()).toBeVisible();
        await expect(page.locator('input[type="search"]')).toBeVisible();
        await page.getByRole('link', { name: 'Próxima' }).click();
        await expect(page).toHaveURL(url => url.searchParams.get('page') === '2');
        await page.getByRole('link', { name: 'Anterior' }).click();
        await expect(page).toHaveURL(url => url.searchParams.get('page') === '1');
    });
}

for (const [resource, term] of [['blocks', 'E2E Bloco paginado'], ['borrows', 'E2E Servidor']]) {
    test(`${resource} search resets page seven and restores the term on history navigation`, async ({ page }) => {
        await page.goto(`/admin/${resource}?page=7`);
        const search = page.locator('input[type="search"]');
        await expect(search).toBeVisible();
        await expect(page).toHaveURL(url => url.searchParams.get('page') === '7');
        await search.fill(term);
        await expect(page).toHaveURL(url => url.searchParams.get('term') === term && url.searchParams.get('page') === '1');
        await page.getByRole('link', { name: 'Próxima' }).click();
        await expect(page).toHaveURL(url => url.searchParams.get('term') === term && url.searchParams.get('page') === '2');
        await search.fill('');
        await expect(page).toHaveURL(url => !url.searchParams.get('term') && url.searchParams.get('page') === '1');
        await page.goBack();
        await expect(search).toHaveValue(term);
        await expect(page).toHaveURL(url => url.searchParams.get('term') === term);
    });
}

test('an old search response cannot replace a newer search', async ({ page }) => {
    let releaseOld;
    let oldStarted;
    const oldRequestStarted = new Promise(resolve => { oldStarted = resolve; });
    const oldCanFinish = new Promise(resolve => { releaseOld = resolve; });
    await page.route('**/admin/blocks?**', async route => {
        if (new URL(route.request().url()).searchParams.get('term') !== 'paginado 0') {
            await route.continue();
            return;
        }
        const response = await route.fetch();
        oldStarted();
        await oldCanFinish;
        await route.fulfill({ response }).catch(() => {});
    });
    await page.goto('/admin/blocks');
    const search = page.locator('input[type="search"]');
    await search.fill('paginado 0');
    await oldRequestStarted;
    await search.fill('paginado 15');
    try {
        await expect(page.locator('tbody tr')).toHaveCount(1);
        await expect(page.locator('tbody')).toContainText('E2E Bloco paginado 15');
    } finally {
        releaseOld();
    }
    await expect(search).toHaveValue('paginado 15');
});

test('borrow badges use server situation on the list, dashboard and report', async ({ page }, testInfo) => {
    await page.goto('/admin/borrows?term=E2E%20Servidor');
    await expect(page.locator('tbody').getByText('Devolvido', { exact: true })).toBeVisible();
    await expect(page.locator('tbody').getByText('Atrasado', { exact: true })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('borrow-list.png'), fullPage: true });
    await page.goto('/admin');
    const recentBorrows = page.locator('table').first();
    await expect(recentBorrows.getByText('Atrasado', { exact: true })).toBeVisible();
    await expect(recentBorrows.getByText('Devolvido', { exact: true })).toBeVisible();
    await expect(recentBorrows.getByText('Aberto', { exact: true }).first()).toBeVisible();
    await page.goto('/admin/reports/borrows?situation=3');
    await expect(page.locator('tbody').getByText('Atrasado', { exact: true })).toBeVisible();
});

for (const report of ['rooms', 'staff', 'turnaround', 'employees', 'borrows']) {
    test(`${report} report keeps applied dates in the table and CSV until search is submitted`, async ({ page }) => {
        const { dates } = JSON.parse(process.env.E2E_FIXTURES);
        await page.goto(`/admin/reports/${report}`);
        await page.getByLabel('Início', { exact: true }).fill(dates.start);
        await page.getByLabel('Final', { exact: true }).fill(dates.end);
        await page.getByRole('button', { name: 'Buscar' }).click();
        await expect(page).toHaveURL(url => url.searchParams.get('start') === dates.start && url.searchParams.get('end') === dates.end);
        await page.getByLabel('Início', { exact: true }).fill('2000-01-01');
        const request = page.waitForRequest(request => new URL(request.url()).searchParams.get('export_csv') === '1');
        const download = page.waitForEvent('download');
        await page.getByRole('button', { name: 'CSV', exact: true }).click();
        const [csvRequest, csv] = await Promise.all([request, download]);
        expect(new URL(csvRequest.url()).searchParams.get('start')).toBe(dates.start);
        expect(new URL(csvRequest.url()).searchParams.get('end')).toBe(dates.end);
        expect(await csv.failure()).toBeNull();
        expect((await readFile(await csv.path(), 'utf8')).length).toBeGreaterThan(20);
        await page.getByRole('link', { name: 'Limpar' }).click();
        await expect(page).toHaveURL(url => url.pathname === `/admin/reports/${report}` && !url.search);
        await expect(page.getByLabel('Início', { exact: true })).toHaveValue('');
    });
}

test('legacy report URL redirects to the current report hub', async ({ page }) => {
    await page.goto('/admin/reports/index');
    await expect(page).toHaveURL(/\/admin\/reports$/);
    await expect(page.getByRole('heading', { name: 'Relatórios', exact: true })).toBeVisible();
});
