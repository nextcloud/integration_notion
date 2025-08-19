/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerWidget } from '@nextcloud/vue/components/NcRichText'

registerWidget('integration_notion_page_database', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: NotionReferenceWidget } = await import('./views/NotionReferenceWidget.vue')

	const app = createApp(
		NotionReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })
