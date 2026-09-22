/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { login } from '@nextcloud/e2e-test-server/playwright'
import { test as base, expect } from '@playwright/test'

// the test container always has this admin user
const admin = { userId: 'admin', password: 'admin' }
const ocs = { 'OCS-APIRequest': 'true', Accept: 'application/json' }

// a page as the app puts it into the rich object of a link preview
const notionPage = {
	id: '1f2e3d4c',
	type: 'page',
	title: 'Integration notes',
	created_by: { name: 'Jane Doe' },
	created_time: '2026-09-01T10:00:00.000Z',
	edited_by: { name: 'John Doe' },
	last_edited_time: '2026-09-18T10:00:00.000Z',
	thumbnail_url: null,
	url: 'https://www.notion.so/Integration-notes-1f2e3d4c',
}

// Every test also fails on an uncaught exception, or on an unexpected failing request to one of the app's own routes.
// Errors of other apps on the instance are ignored on purpose.
const test = base.extend<{ appErrors: void }>({
	appErrors: [async ({ page }, use) => {
		const errors: string[] = []
		page.on('pageerror', (error) => errors.push(`uncaught: ${error.message}`))
		page.on('response', (response) => {
			if (response.status() >= 400 && response.url().includes('/integration_notion/')) {
				errors.push(`${response.status()} ${response.request().method()} ${new URL(response.url()).pathname}`)
			}
		})
		await use()
		expect(errors).toEqual([])
	}, { auto: true }],
})

/**
 * Load the reference bundle of the app and render its widget, the way Nextcloud does
 * wherever a link preview appears.
 *
 * @param page a page of a logged in user
 * @param type the rich object type of the widget
 * @param richObject the rich object to render
 */
async function renderReferenceWidget(page: Page, type: string, richObject: object) {
	await page.goto('apps/files/')
	const registered = await page.evaluate(async ([widgetType, object]) => {
		const globals = window as unknown as {
			OC: { appswebroots: Record<string, string> }
			_vue_richtext_widgets: Record<string, { callback: (element: HTMLElement, data: object) => void }>
		}
		await import(/* @vite-ignore */ `${globals.OC.appswebroots.integration_notion}/js/integration_notion-reference.mjs`)
		const element = document.createElement('div')
		element.id = 'reference-widget'
		document.body.appendChild(element)
		globals._vue_richtext_widgets[widgetType as string].callback(element, {
			richObjectType: widgetType,
			richObject: object,
			accessible: false,
		})
		return Object.keys(globals._vue_richtext_widgets)
	}, [type, richObject] as [string, object])
	expect(registered).toContain(type)
	return page.locator('#reference-widget')
}

test.beforeEach(async ({ page }) => {
	await login(page.request, admin)
})

test.describe('Link previews', () => {
	test('offer the provider to the smart picker', async ({ page }) => {
		const response = await page.request.get('../ocs/v2.php/references/providers', { headers: ocs })
		expect(response.ok()).toBe(true)
		const providers = (await response.json()).ocs.data as Array<{ id: string, title: string, icon_url: string }>
		const provider = providers.find((candidate) => candidate.id === 'notion-page-database')
		expect(provider).toBeDefined()
		expect(provider?.title).toBe('Notion page or database')
		// the provider icon is served by the app
		expect((await page.request.get(provider!.icon_url)).ok()).toBe(true)
	})

	test('render a page in the reference widget', async ({ page }) => {
		const widget = await renderReferenceWidget(page, 'integration_notion_page_database', notionPage)

		const link = widget.getByRole('link', { name: /Integration notes/ })
		await expect(link).toBeVisible()
		await expect(link).toHaveAttribute('href', notionPage.url)
		await expect(widget.getByText('Page:')).toBeVisible()
		await expect(widget.getByText('Jane Doe', { exact: true })).toBeVisible()
		await expect(widget.getByText('John Doe', { exact: true })).toBeVisible()
	})
})

test.describe('Search providers', () => {
	test('offer the providers to unified search', async ({ page }) => {
		const response = await page.request.get('../ocs/v2.php/search/providers', { headers: ocs })
		expect(response.ok()).toBe(true)
		const providers = (await response.json()).ocs.data as Array<{ id: string, appId: string, name: string }>
		const expected = {
			'notion-search-pages': 'Notion pages',
			'notion-search-databases': 'Notion databases',
		}
		for (const [id, name] of Object.entries(expected)) {
			expect(providers.find((candidate) => candidate.id === id), `provider ${id}`).toMatchObject({
				appId: 'integration_notion',
				name,
			})
		}
	})

	test('answer an empty result for a user without a Notion account', async ({ page }) => {
		for (const id of ['notion-search-pages', 'notion-search-databases']) {
			const response = await page.request.get(`../ocs/v2.php/search/providers/${id}/search?term=nextcloud`, { headers: ocs })
			expect(response.ok(), id).toBe(true)
			expect((await response.json()).ocs.data.entries, id).toEqual([])
		}
	})
})
