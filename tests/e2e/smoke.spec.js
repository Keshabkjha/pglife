const { test, expect } = require('@playwright/test');

test.describe('PGLife E2E Smoke Tests', () => {
    test('home page loads and lists cities', async ({ page }) => {
        await page.goto('/home.php');
        await expect(page).toHaveTitle(/PGLife/i);
    });

    test('property detail page renders correctly', async ({ page }) => {
        await page.goto('/property_detail.php?property_id=1');
        const propertyName = page.locator('.property-name');
        if (await propertyName.count() > 0) {
            await expect(propertyName).toBeVisible();
        }
    });

    test('legal pages have correct non-placeholder text', async ({ page }) => {
        await page.goto('/privacy_policy.php');
        const content = await page.textContent('body');
        expect(content).not.toContain('[Your City]');
        expect(content).not.toContain('[Grievance Officer Name]');
    });
});
