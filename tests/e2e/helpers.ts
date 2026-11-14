import { execSync } from 'node:child_process';
import { expect, type Page } from '@playwright/test';

export function totp(): string {
  return totpFor('JBSWY3DPEHPK3PXP');
}

export function totpFor(secret: string): string {
  return execSync(`php tests/e2e/totp.php ${secret}`, { cwd: process.cwd() }).toString().trim();
}

export async function staffLogin(page: Page, email: string): Promise<void> {
  await page.goto('/office/login');
  await page.fill('#email', email);
  await page.fill('#password', 'E2e-Staff-2026');
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.fill('#code', totp());
  await page.getByRole('button', { name: 'Enter the office' }).click();
  await expect(page).toHaveURL(/\/office$/);
}
