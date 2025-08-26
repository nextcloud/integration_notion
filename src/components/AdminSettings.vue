<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="notion_prefs" class="section">
		<h2>
			<NotionIcon class="notion-icon" />
			{{ t('integration_notion', 'Notion integration') }}
		</h2>
		<p class="settings-hint">
			{{ t('integration_notion', 'If you want to allow your Nextcloud users to connect to Notion via OAuth, create an application in Notion and set the ID and secret here.') }}
			<a class="external" href="https://developers.notion.com/docs/create-a-notion-integration">
				{{ t('integration_notion', 'How to create a Notion OAuth app') }}
			</a>
		</p>
		<br>
		<p class="settings-hint">
			<InformationVariantIcon :size="24" class="icon" />
			{{ t('integration_notion', 'Make sure you set the "Redirect URI" to') }}
			&nbsp;<b> {{ redirect_uri }} </b>
		</p>
		<br>
		<p class="settings-hint">
			{{ t('integration_notion', 'Put the "Application ID" and "Application secret" below. Your Nextcloud users will then see a "Connect to Notion" button in their personal settings.') }}
		</p>
		<div class="field">
			<label for="notion-client-id">
				<KeyOutlineIcon :size="20" class="icon" />
				{{ t('integration_notion', 'Application ID') }}
			</label>
			<input id="notion-client-id"
				v-model="state.client_id"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_notion', 'ID of your Notion application')"
				@input="onInput"
				@focus="readonly = false">
		</div>
		<div class="field">
			<label for="notion-client-secret">
				<KeyOutlineIcon :size="20" class="icon" />
				{{ t('integration_notion', 'Application secret') }}
			</label>
			<input id="notion-client-secret"
				v-model="state.client_secret"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_notion', 'Client secret of your Notion application')"
				@focus="readonly = false"
				@input="onInput">
		</div>
		<NcCheckboxRadioSwitch
			class="field"
			:checked.sync="state.use_popup"
			@update:checked="onUsePopupChanged">
			{{ t('integration_notion', 'Use a popup to authenticate') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script>
import InformationVariantIcon from 'vue-material-design-icons/InformationVariant.vue'
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import NotionIcon from './icons/NotionIcon.vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',

	components: {
		NotionIcon,
		NcCheckboxRadioSwitch,
		InformationVariantIcon,
		KeyOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_notion', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_notion/oauth-redirect'),
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onUsePopupChanged(newValue) {
			this.saveOptions({ use_popup: newValue ? '1' : '0' }, false)
		},
		onInput() {
			delay(() => {
				const values = {
					client_id: this.state.client_id,
				}
				if (this.state.client_secret !== 'dummyToken') {
					values.client_secret = this.state.client_secret
				}
				this.saveOptions(values)
			}, 2000)()
		},
		async saveOptions(values, sensitive = true) {
			if (sensitive) {
				await confirmPassword()
			}
			const req = {
				values,
			}
			const url = sensitive
				? generateUrl('/apps/integration_notion/sensitive-admin-config')
				: generateUrl('/apps/integration_notion/admin-config')
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_notion', 'Notion admin options saved'))
			}).catch((error) => {
				showError(t('integration_notion', 'Failed to save Notion admin options'))
				console.error(error)
			})
		},
	},
}
</script>

<style scoped lang="scss">
#notion_prefs {
	.field {
		display: flex;
		align-items: center;
		margin-left: 30px;

		input,
		label {
			width: 300px;
		}

		label {
			display: flex;
			align-items: center;
		}
		.icon {
			margin-right: 8px;
		}
	}

	.settings-hint {
		display: flex;
		align-items: center;
	}

	h2 {
		display: flex;
		.notion-icon {
			margin-right: 12px;
		}
	}
}
</style>
