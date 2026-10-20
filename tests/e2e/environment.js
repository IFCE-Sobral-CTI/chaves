export const baseURL = 'http://127.0.0.1:18082';
export const authFile = 'tests/e2e/.auth/admin.json';
export const account = { email: 'admin@e2e.test', password: 'e2e-password' };

export const serverEnv = {
    APP_ENV: 'local',
    APP_URL: baseURL,
    ASSET_URL: baseURL,
    DB_DATABASE: 'testing',
    SESSION_DRIVER: 'file',
    SESSION_COOKIE: 'chaves_e2e_session',
    SESSION_SECURE_COOKIE: 'false',
    CACHE_STORE: 'array',
    CACHE_DRIVER: 'array',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    BCRYPT_ROUNDS: '4',
    APP_PAGINATION: '2',
};
