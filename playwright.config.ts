import { defineConfig } from '@playwright/test';

const shared = {
  command: 'php artisan serve --host=127.0.0.1 --port=18474',
  url: 'http://127.0.0.1:18474/up',
  reuseExistingServer: false,
  timeout: 120_000,
};

export default defineConfig({
  testDir: 'tests/e2e',
  globalSetup: './tests/e2e/global-setup.ts',
  timeout: 90_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  use: {
    baseURL: 'http://127.0.0.1:18474',
    launchOptions: { executablePath: '/usr/local/bin/google-chrome', args: ['--no-sandbox'] },
  },
  webServer: {
    ...shared,
    env: {
      ...process.env,
      READ_ONLY_LEGACY: 'false',
      SAFE_MODE: 'true',
      MPESA_B2C_MOCK: 'true',
      MPESA_CONSUMER_KEY: 'sandbox-key-1234',
      MPESA_CONSUMER_SECRET: 'sandbox-secret',
    },
  },
});
