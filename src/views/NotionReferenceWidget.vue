<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="notion-page-database-reference">
		<div class="notion-page-database-wrapper">
			<template v-if="richObject.thumbnail_url">
				<img :src="richObject.thumbnail_url" :height="48" :alt="t('integration_notion', 'Thumbnail for {title}', { title: richObject.title })">
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
import { generateUrl } from '@nextcloud/router'
import moment from '@nextcloud/moment'

import NotionIcon from '../components/icons/NotionIcon.vue'

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
