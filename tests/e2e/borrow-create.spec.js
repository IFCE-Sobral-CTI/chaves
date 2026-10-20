import { expect, test } from './fixtures.js';

test('admin can open the new borrow form', async ({ page }) => {
    await page.goto('/admin/borrows/create');
    await expect(page.getByRole('heading', { name: 'Cadastro de novo Empréstimo' })).toBeVisible();
    await expect(page.getByText('Mutuário', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Observações')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Enviar' })).toBeVisible();
});
