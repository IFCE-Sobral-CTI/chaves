import { expect, test } from './fixtures.js';

function loadFixtures() {
    return JSON.parse(process.env.E2E_FIXTURES);
}

async function exerciseLocalSelect(page, { id, searchName, option, emptyText }) {
    const trigger = page.locator(`#${id}-trigger`);
    await expect(trigger).toContainText(emptyText);
    await trigger.focus();
    await page.keyboard.press('Enter');

    const search = page.getByRole('textbox', { name: searchName });
    await expect(search).toBeFocused();
    await search.fill(option);
    const result = page.getByRole('button', { name: option, exact: true });
    await expect(result).toBeVisible();
    await page.keyboard.press('Tab');
    await expect(result).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(trigger).toContainText(option);
    await expect(trigger).toBeFocused();

    await page.keyboard.press('Enter');
    await expect(search).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');

    await page.keyboard.press('Enter');
    await expect(search).toBeFocused();
    const outside = page.locator('main h1').first();
    await outside.click();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
}

test('room and key local selectors work in create and edit forms', async ({ page }, testInfo) => {
    const fixtures = loadFixtures();
    const forms = [
        {
            url: '/admin/rooms/create',
            id: 'block_id',
            searchName: 'Pesquisar bloco',
            emptyText: 'Nenhum bloco selecionado',
            option: fixtures.otherBlock.description,
        },
        {
            url: `/admin/rooms/${fixtures.room.id}/edit`,
            id: 'block_id',
            searchName: 'Pesquisar bloco',
            emptyText: fixtures.block.description,
            option: fixtures.otherBlock.description,
            initial: fixtures.block.description,
        },
        {
            url: '/admin/keys/create',
            id: 'room_id',
            searchName: 'Pesquisar sala',
            emptyText: 'Nenhuma sala selecionada',
            option: fixtures.otherRoom.description,
        },
        {
            url: `/admin/keys/${fixtures.key.id}/edit`,
            id: 'room_id',
            searchName: 'Pesquisar sala',
            emptyText: fixtures.room.description,
            option: fixtures.otherRoom.description,
            initial: fixtures.room.description,
        },
    ];

    for (const [index, form] of forms.entries()) {
        await page.goto(form.url);
        const trigger = page.locator(`#${form.id}-trigger`);
        if (form.initial) {
            await expect(trigger).toContainText(form.initial);
        }
        await page.screenshot({ path: testInfo.outputPath(`selector-form-${index + 1}.png`) });
        await exerciseLocalSelect(page, form);
    }
});

test('employee edit keeps the formatted phone and authorized key after saving', async ({ page }, testInfo) => {
    const { employee, room, key } = loadFixtures();
    const editUrl = `/admin/employees/${employee.id}/edit`;
    const keyName = `${key.number} - ${room.description}`;

    await page.goto(editUrl);
    await expect(page.locator('#tel')).toHaveValue('(88) 99999-8888');
    await expect(page.locator('#valid_until')).toHaveValue(employee.valid_until);
    await expect(page.getByText(keyName, { exact: true })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('employee-edit.png') });
    await page.locator('#tel').fill('88999998888');
    await expect(page.locator('#tel')).toHaveValue('(88) 99999-8888');
    await page.getByRole('button', { name: 'Enviar' }).click();

    await expect(page).toHaveURL(new RegExp(`/admin/employees/${employee.id}$`));
    await page.goto(editUrl);
    await expect(page.locator('#tel')).toHaveValue('(88) 99999-8888');
    await expect(page.getByText(keyName, { exact: true })).toBeVisible();
});

test('changing employee type clears authorized key IDs from the submitted update', async ({ page }) => {
    const { employee } = loadFixtures();
    const editUrl = `/admin/employees/${employee.id}/edit`;
    let submittedData;

    await page.goto(editUrl);
    await page.route(`**/admin/employees/${employee.id}`, async (route) => {
        if (route.request().method() === 'PUT') {
            submittedData = route.request().postDataJSON();
            await route.fulfill({ status: 303, headers: { Location: editUrl } });
            return;
        }
        await route.continue();
    });

    await page.getByLabel('Vínculo do Mutuário').selectOption('1');
    await page.getByRole('button', { name: 'Enviar' }).click();
    await expect.poll(() => submittedData).toBeDefined();
    expect(submittedData.borrowable_key_ids).toEqual([]);
});

test('group, permission, and rule create and edit forms preserve fields and return paths', async ({ page }, testInfo) => {
    const fixtures = loadFixtures();
    const resources = [
        { resource: 'groups', item: fixtures.group },
        { resource: 'permissions', item: fixtures.permission },
        { resource: 'rules', item: fixtures.rule },
    ];

    for (const { resource, item } of resources) {
        let createdId;

        try {
            await page.goto(`/admin/${resource}/create`);
            await expect(page.locator('#description')).toBeVisible();
            await expect(page.getByRole('button', { name: 'Enviar' })).toBeVisible();
            const createBack = page.getByRole('link', { name: 'Voltar' });
            const createBackHref = await createBack.getAttribute('href');
            expect(new URL(createBackHref).pathname).toBe(`/admin/${resource}`);
            await page.screenshot({ path: testInfo.outputPath(`${resource}-create.png`) });
            await createBack.click();
            await expect(page).toHaveURL(new RegExp(`/admin/${resource}$`));
            await page.goto(`/admin/${resource}/create`);

            const createdDescription = `E2E Form ${resource}`;
            await page.locator('#description').fill(createdDescription);
            if (resource === 'rules') {
                await page.locator('#control').fill('browserforms.read');
                await page.locator('#group_id').selectOption(String(fixtures.otherGroup.id));
            }
            await page.getByRole('button', { name: 'Enviar' }).click();
            await expect(page).toHaveURL(new RegExp(`/admin/${resource}/\\d+$`));
            createdId = new URL(page.url()).pathname.split('/').at(-1);
            await expect(page.getByText(createdDescription, { exact: true }).first()).toBeVisible();

            await page.goto(`/admin/${resource}`);
            await expect(page).toHaveURL(new RegExp(`/admin/${resource}$`));

            await page.goto(`/admin/${resource}/${item.id}/edit`);
            await expect(page.locator('#description')).toHaveValue(item.description);
            if (resource === 'rules') {
                await expect(page.getByRole('heading', { name: 'Editar Regra', exact: true })).toBeVisible();
                const groupSelect = page.locator('#group_id');
                await expect(groupSelect).toHaveValue(String(fixtures.otherGroup.id));
                await expect(groupSelect.locator('option').first()).not.toHaveAttribute('value', String(fixtures.otherGroup.id));
                await expect(page.locator('#control')).toHaveValue(item.control);
            }
            await page.screenshot({ path: testInfo.outputPath(`${resource}-edit.png`) });
            await page.getByRole('button', { name: 'Enviar' }).click();
            await expect(page).toHaveURL(new RegExp(`/admin/${resource}/${item.id}$`));
            await expect(page.getByText(item.description, { exact: true }).first()).toBeVisible();
            await page.getByRole('link', { name: 'Voltar' }).click();
            await expect(page).toHaveURL(new RegExp(`/admin/${resource}$`));
        } finally {
            if (createdId) {
                await page.goto(`/admin/${resource}/${createdId}`);
                await page.getByRole('button', { name: 'Apagar' }).click();
                await page.getByRole('dialog').getByRole('button', { name: 'Sim' }).click();
                await expect(page).toHaveURL(new RegExp(`/admin/${resource}$`));
            }
        }
    }
});
