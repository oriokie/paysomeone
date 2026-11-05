import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { staffLogin } from './helpers';

const widths = [390, 1024, 1440];

test('public pages have no critical axe violations and do not overflow', async ({ page }) => {
  for (const path of ['/', '/order', '/apply', '/login']) {
    for (const width of widths) {
      await page.setViewportSize({ width, height: 900 });
      const response = await page.goto(path);
      expect(response?.status(), path).toBeLessThan(500);
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, `${path} @ ${width}`).toBeLessThan(8);
    }
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();
    const serious = results.violations.filter((item) => item.impact === 'serious' || item.impact === 'critical');
    expect(serious, path).toEqual([]);
  }
});

test('gateway, fine, and application screens render for an admin', async ({ page }) => {
  await staffLogin(page, 'e2e-admin@example.test');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/apply');
  await expect(page.getByRole('heading', { name: 'Apply to write' })).toBeVisible();
  await page.screenshot({ path: '/opt/cursor/artifacts/writer-application.png', fullPage: true });
  await page.goto('/office/gateways');
  await expect(page.getByRole('heading', { name: 'Gateways' })).toBeVisible();
  await page.screenshot({ path: '/opt/cursor/artifacts/office-gateways.png', fullPage: true });
  await page.goto('/office/fines');
  await expect(page.getByRole('heading', { name: 'Fines' })).toBeVisible();
  await page.screenshot({ path: '/opt/cursor/artifacts/office-fines.png', fullPage: true });
  await page.goto('/office/applications');
  await expect(page.getByRole('heading', { name: 'Applications' })).toBeVisible();
  await page.screenshot({ path: '/opt/cursor/artifacts/office-applications.png', fullPage: true });
  await page.goto('/office/earnings');
  await page.screenshot({ path: '/opt/cursor/artifacts/office-payout-approval.png', fullPage: true });
});
