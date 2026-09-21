/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as base, expect } from '@playwright/test'

// the test container always has this admin user
const admin = { userId: 'admin', password: 'admin' }

// Every test also fails on an uncaught exception, or on a failing request to one of the app's own routes.
// Errors of other apps on the instance are ignored on purpose.
const test = base.extend<{ appErrors: void }>({
	appErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(`uncaught: ${error.message}`))
		page.on('response', (response) => {
			if (response.status() >= 400 && response.url().includes('/integration_notion/')) {
				errors.push(`${response.status()} ${response.request().method()} ${response.url()}`)
			}
		})
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

test.beforeEach(async ({ page }) => {
	await login(page.request, admin)
})

test.describe('Admin settings', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('settings/admin/connected-accounts')
	})

	test('show the Notion section', async ({ page }) => {
		const section = page.locator('#notion_prefs')
		await expect(section.getByRole('heading', { name: /Notion integration/ })).toBeVisible()
		await expect(section.getByLabel('Application ID')).toBeVisible()
		await expect(section.getByLabel('Application secret')).toBeVisible()
	})

	test('save the popup authentication setting', async ({ page }) => {
		const section = page.locator('#notion_prefs')
		const popup = section.getByRole('checkbox', { name: 'Use a popup to authenticate' })
		// NcCheckboxRadioSwitch hides its input, so click the label
		const toggle = async () => {
			const saved = page.waitForResponse((response) => response.url().includes('/apps/integration_notion/admin-config'))
			await section.getByText('Use a popup to authenticate').click()
			expect((await saved).ok()).toBe(true)
		}

		const before = await popup.isChecked()
		await toggle()
		try {
			await page.reload()
			await expect(popup).toBeChecked({ checked: !before })
		} finally {
			await toggle()
		}
	})
})

test.describe('Personal settings', () => {
	test('ask for the Notion app to be configured first', async ({ page }) => {
		await page.goto('settings/user/connected-accounts')
		await expect(page.locator('#notion_prefs')
			.getByText('Ask your administrator to configure the Notion integration in Nextcloud.')).toBeVisible()
	})
})
