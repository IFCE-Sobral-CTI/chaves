import { execFileSync } from 'node:child_process';
import { serverEnv } from './environment.js';

export default function globalSetup() {
    process.env.E2E_FIXTURES = execFileSync('php', ['tests/Fixtures/E2E/prepare.php'], {
        env: { ...process.env, ...serverEnv },
        encoding: 'utf8',
    }).trim();
    JSON.parse(process.env.E2E_FIXTURES);
}
