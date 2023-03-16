<!--
 - @copyright Copyright (c) 2023 Andrey Borysenko <andrey18106x@gmail.com>
 -
 - @copyright Copyright (c) 2023 Alexander Piskun <bigcat88@icloud.com>
 -
 - @author 2023 Andrey Borysenko <andrey18106x@gmail.com>
 -
 - @license AGPL-3.0-or-later
 -
 - This program is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License as
 - published by the Free Software Foundation, either version 3 of the
 - License, or (at your option) any later version.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<div class="notion-page-database-reference">
		<!-- TODO: Display error info -->
		<div class="notion-page-database-wrapper">
			<template v-if="richObject.thumbnail_url">
				<img :src="richObject.thumbnail_url" :height="48">
			</template>
			<NotionIcon v-else :size="48" />
			<div class="notion-link-content">
				<a :href="notionUrl" target="_blank" rel="noreferrer noopener">
					<strong>{{ richObject.title }}</strong>
				</a>
				<p>
					{{ t('integration_notion', 'Last edit time: ') }}
					{{ formattedLastEditedTime }}
				</p>
			</div>
			<!-- TODO: Add additional info about Notion object entry -->
		</div>
	</div>
</template>

<script>
import Vue from 'vue'
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'
import { isDarkMode } from '../utils.js'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'

import NotionIcon from '../components/icons/NotionIcon.vue'

Vue.directive('tooltip', Tooltip)

export default {
	name: 'NotionReferenceWidget',
	components: {
		NotionIcon,
	},
	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			settingsUrl: generateUrl('/settings/user/connected-accounts#notion_prefs'),
		}
	},
	computed: {
		isPage() {
			return this.richObject.object === 'page'
		},
		isDatabase() {
			return this.richObject.object === 'database'
		},
		notionUrl() {
			return this.richObject.url
		},
		isDarkMode() {
			return isDarkMode()
		},
		formattedLastEditedTime() {
			return moment(this.richObject.last_edited_time).format('LLL')
		},
	},
	methods: {
		getNotionObjectThumbnail() {
			const url = this.richObject?.thumbnail_url ?? ''
			return generateUrl('/apps/integration_notion/thumbnail?url={url}', url)
		},
	},
}
</script>

<style scoped lang="scss">
.notion-page-database-reference {
	width: 100%;
	white-space: normal;
	padding: 12px;

	.notion-page-database-wrapper {
		display: flex;
		flex-direction: row;
		align-items: center;

		.notion-link-content {
			display: flex;
			flex-direction: column;
			justify-content: center;
			margin-left: 12px;
		}
	}
}
</style>
