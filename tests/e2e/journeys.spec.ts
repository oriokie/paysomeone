import { execSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { staffLogin, totpFor } from './helpers';

test.beforeEach(() => {
  execSync('php artisan cache:clear', { cwd: process.cwd(), stdio: 'ignore' });
});

test('customers can sign in with bcrypt, md5, and salted sha1', async ({ page }) => {
  for (const [email, password] of [
    ['e2e-bcrypt@example.test', 'E2e-Bcrypt-2026'],
    ['e2e-md5@example.test', 'E2e-Md5-2026'],
    ['e2e-sha@example.test', 'E2e-Sha-2026'],
  ] as const) {
    await page.goto('/login');
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.getByRole('button', { name: 'Sign in' }).click();
    await expect(page).toHaveURL(/\/account/);
    await page.getByRole('button', { name: new RegExp(email.split('@')[0], 'i') }).click();
    await page.getByRole('button', { name: 'Sign out' }).click();
  }
});

test('a customer places a five-step article order and sees the quote', async ({ page }) => {
  await page.goto('/login');
  await page.fill('#email', 'e2e-bcrypt@example.test');
  await page.fill('#password', 'E2e-Bcrypt-2026');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await page.goto('/order');
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.fill('#title', 'E2E launch note');
  await page.fill('#brief', 'A launch note for existing customers. Lead with the result, then the next step we need from them.');
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByRole('button', { name: 'Have a code?' }).click();
  await page.fill('#coupon_code', 'E2E10');
  await expect(page.getByText('Code applied.')).toBeVisible();
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByRole('checkbox', { name: /I agree/ }).check();
  await page.getByRole('button', { name: 'Place order' }).click();
  await expect(page.getByRole('heading', { name: 'E2E launch note' })).toBeVisible();
  await page.getByRole('tab', { name: 'Payment' }).click();
  await expect(page.getByText('Safe mode is on. M-Pesa')).toBeVisible();
  await page.getByRole('button', { name: /Pay \$/ }).click();
  await expect(page.getByRole('status').getByText(/safe mode/i)).toBeVisible();
});

test('writer, sub-admin, and editor move an order from bid to delivery', async ({ page }) => {
  await staffLogin(page, 'e2e-writer@example.test');
  await page.goto('/office/pool');
  await expect(page.getByText('E2E pool brief')).toBeVisible();
  const card = page.locator('article', { hasText: 'E2E pool brief' });
  await card.locator('input[name=amount]').fill('30');
  await card.getByRole('button', { name: 'Place bid' }).click();
  await expect(page.getByText('Bid saved.')).toBeVisible();
  await page.getByRole('button', { name: 'Sign out' }).click();

  await staffLogin(page, 'e2e-sub@example.test');
  await page.goto('/office/orders?q=E2E+pool');
  await page.locator('table a').first().click();
  await page.getByRole('button', { name: 'Approve' }).click();
  await expect(page.getByText('Bid approved')).toBeVisible();
  await page.getByRole('button', { name: 'Sign out' }).click();

  await staffLogin(page, 'e2e-writer@example.test');
  await page.goto('/office/orders?q=E2E+pool');
  await page.locator('table a').first().click();
  await page.getByRole('button', { name: 'Submit for review' }).click();
  await page.getByRole('button', { name: 'Sign out' }).click();

  await staffLogin(page, 'e2e-editor@example.test');
  await page.goto('/office/orders?q=E2E+pool');
  await page.locator('table a').first().click();
  await expect(page.getByText('Customer contact details are hidden')).toBeVisible();
  await expect(page.getByText('e2e-bcrypt@example.test')).toHaveCount(0);
  await page.fill('#return-body', 'Please tighten the first paragraph so the result is the first sentence.');
  await page.getByRole('button', { name: 'Return with notes' }).click();
  await expect(page.getByText('Returned to the writer.')).toBeVisible();
});

test('forbidden office urls and a foreign customer order are refused', async ({ page }) => {
  await staffLogin(page, 'e2e-writer@example.test');
  const roles = await page.goto('/office/roles');
  expect(roles?.status()).toBe(403);
  const settings = await page.goto('/office/settings');
  expect(settings?.status()).toBe(403);
  await page.goto('/office');
  await page.getByRole('button', { name: 'Sign out' }).click();

  await page.goto('/login');
  await page.fill('#email', 'e2e-md5@example.test');
  await page.fill('#password', 'E2e-Md5-2026');
  await page.getByRole('button', { name: 'Sign in' }).click();
  const orderId = execSync('php -r \'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo App\\Models\\Order::query()->where("tracking_id","E2EPOOL")->value("order_id");\'', { cwd: process.cwd() }).toString().trim();
  const other = await page.goto(`/account/orders/${orderId}`);
  expect(other?.status()).toBe(403);
});

test('a payout approval stays unpaid while safe mode is on', async ({ page }) => {
  await staffLogin(page, 'e2e-writer@example.test');
  await page.goto('/office/earnings');
  await page.fill('#phone', '0712345678');
  await page.fill('#amount', '12');
  await page.getByRole('button', { name: 'Request payout' }).click();
  await expect(page.getByRole('status').getByText(/Safe mode is on/)).toBeVisible();
});

test('login is rate limited and a post without a token is rejected', async ({ page, request }) => {
  for (let i = 0; i < 6; i++) {
    await page.goto('/office/login');
    await page.fill('#email', 'nobody@example.test');
    await page.fill('#password', 'wrong-password');
    await page.getByRole('button', { name: 'Continue' }).click();
  }
  await expect(page.getByText(/Too Many Attempts|too many/i)).toBeVisible();
  const csrf = await request.post('/office/login', { form: { email: 'e2e-admin@example.test', password: 'E2e-Staff-2026' } });
  expect(csrf.status()).toBe(419);
});

test('a new admin enrols two-factor before the office opens', async ({ page }) => {
  await page.goto('/office/login');
  await page.fill('#email', 'e2e-enrol@example.test');
  await page.fill('#password', 'E2e-Staff-2026');
  await page.getByRole('button', { name: 'Continue' }).click();
  await expect(page.getByRole('heading', { name: 'Set up an authenticator' })).toBeVisible();
  const secret = (await page.locator('p.font-mono').first().innerText()).replace(/\s/g, '');
  await page.fill('#code', totpFor(secret));
  await page.getByRole('button', { name: 'Turn on two-factor' }).click();
  await expect(page).toHaveURL(/\/office$/);
});

test('a permissions change removes the writer pool until it is restored', async ({ page }) => {
  await staffLogin(page, 'e2e-admin@example.test');
  await page.goto('/office/roles');
  const box = page.getByRole('checkbox', { name: 'Writer Bid on orders in the pool' });
  await box.uncheck();
  await page.getByRole('button', { name: 'Save matrix' }).click();
  await expect(page.getByText('Permission matrix saved')).toBeVisible();
  await page.getByRole('button', { name: 'Sign out' }).click();
  await staffLogin(page, 'e2e-writer@example.test');
  const pool = await page.goto('/office/pool');
  expect(pool?.status()).toBe(403);
  await page.goto('/office');
  await page.getByRole('button', { name: 'Sign out' }).click();
  await staffLogin(page, 'e2e-admin@example.test');
  await page.goto('/office/roles');
  await page.getByRole('checkbox', { name: 'Writer Bid on orders in the pool' }).check();
  await page.getByRole('button', { name: 'Save matrix' }).click();
  await expect(page.getByText('Permission matrix saved')).toBeVisible();
});
