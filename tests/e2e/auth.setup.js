import { test, expect } from './fixtures.js';
import { account, authFile } from './environment.js';

test('authenticate isolated E2E administrator', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill(account.email);
    await page.getByLabel('Senha', { exact: true }).fill(account.password);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/\/admin$/);
    await page.context().storageState({ path: authFile });
});
