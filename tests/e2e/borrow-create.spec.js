import { expect, test } from '@playwright/test';

test.skip(!process.env.E2E_USER_EMAIL || !process.env.E2E_USER_PASSWORD, 'Set E2E_USER_EMAIL and E2E_USER_PASSWORD');

test('admin can open the new borrow form', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill(process.env.E2E_USER_EMAIL);
    await page.getByLabel('Senha').fill(process.env.E2E_USER_PASSWORD);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/admin$/);

    await page.goto('/admin/borrows/create');
    await expect(page.getByRole('heading', { name: 'Cadastro de nova Empréstimo' })).toBeVisible();
    await expect(page.getByText('Mutuário')).toBeVisible();
    await expect(page.getByLabel('Observações')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Enviar' })).toBeVisible();
});
