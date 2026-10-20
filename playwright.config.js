import { defineConfig, devices } from '@playwright/test';
import { baseURL, serverEnv, authFile } from './tests/e2e/environment.js';

export default defineConfig({
    testDir: './tests/e2e',
    workers: 1,
    globalSetup: './tests/e2e/global-setup.js',
    reporter: [['list'], ['html', { open: 'never' }]],
    webServer: {
        // Preserve the isolated database and asset URL in Artisan's server process.
        command: 'php artisan serve --host=127.0.0.1 --port=18082 --no-reload',
        url: `${baseURL}/login`,
        env: serverEnv,
        reuseExistingServer: false,
    },
    use: {
        baseURL,
        ...devices['Desktop Chrome'],
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            name: 'chromium',
            testMatch: /.*\.spec\.js/,
            use: { storageState: authFile },
            dependencies: ['setup'],
        },
    ],
});
