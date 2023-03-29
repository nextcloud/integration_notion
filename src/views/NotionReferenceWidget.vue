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
		<div class="notion-page-database-wrapper">
			<template v-if="richObject.thumbnail_url">
				<img :src="richObject.thumbnail_url" :height="48">
			</template>
			<NotionIcon v-else :size="48" />
			<div class="notion-link-content">
				<div class="notion-title">
					<a :href="notionUrl" target="_blank" rel="noreferrer noopener">
						<strong>
							{{ titlePrefix }}
							{{ richObject.title }}
						</strong>
					</a>
				</div>
				<div class="notion-details">
					<div class="notion-created-by">
						<p>
							{{ t('integration_notion', 'Created by: ') }}
							<b>{{ createdByName }}</b>
							({{ formattedCreatedTime }})
						</p>
					</div>
					<div class="notion-edited-by">
						<p>
							{{ t('integration_notion', 'Last edited by: ') }}
							<b>{{ editedByName }}</b>
							({{ formattedLastEditedTime }})
						</p>
					</div>
				</div>
			</div>
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
			return this.richObject.type === 'page'
		},
		isDatabase() {
			return this.richObject.type === 'database'
		},
		notionUrl() {
			return this.richObject.url
		},
		isDarkMode() {
			return isDarkMode()
		},
		titlePrefix() {
			if (this.isPage) {
				return t('integration_notion', 'Page: ')
			} else if (this.isDatabase) {
				return t('integration_notion', 'Database: ')
			}
			return ''
		},
		formattedCreatedTime() {
			return moment(this.richObject.created_time).utc().format('LLL')
		},
		formattedLastEditedTime() {
			return moment(this.richObject.last_edited_time).utc().format('LLL')
		},
		createdByName() {
			return this.richObject?.created_by?.name
		},
		editedByName() {
			return this.richObject?.edited_by?.name
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
