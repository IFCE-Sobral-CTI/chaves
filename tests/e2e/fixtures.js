import { test as base, expect } from '@playwright/test';

export const test = base.extend({
    page: async ({ page }, use) => {
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await use(page);
        expect(errors, 'uncaught browser errors').toEqual([]);
    },
});

export { expect };
