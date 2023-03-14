<template>
	<div id="notion_prefs" class="section">
		<h2>
			<NotionIcon class="notion-icon" />
			{{ t('integration_notion', 'Notion integration') }}
		</h2>
		<p v-if="!showOAuth && !connected" class="settings-hint">
			{{ t('integration_notion', 'Ask your administrator to configure the Notion integration in Nextcloud.') }}
		</p>
		<div id="notion-content">
			<NcButton v-if="!connected && showOAuth"
				id="notion-connect"
				class="field"
				:disabled="loading === true"
				:class="{ loading }"
				@click="onConnectClick">
				<template #icon>
					<OpenInNewIcon />
				</template>
				{{ t('integration_notion', 'Connect to Notion') }}
			</NcButton>
			<div v-if="connected" class="field">
				<label class="notion-connected">
					<CheckIcon :size="24" class="icon" />
					{{ t('integration_notion', 'Connected as {user}', { user: connectedDisplayName }) }}
				</label>
				<NcButton id="notion-rm-cred" @click="onLogoutClick">
					<template #icon>
						<CloseIcon />
					</template>
					{{ t('integration_notion', 'Disconnect from Notion') }}
				</NcButton>
			</div>
			<div v-if="connected" id="notion-search-block">
				<NcCheckboxRadioSwitch
					:checked="state.search_pages_enabled"
					@update:checked="onCheckboxChanged($event, 'search_pages_enabled')">
					{{ t('integration_notion', 'Enable searching for Notion pages') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					:checked="state.search_databases_enabled"
					@update:checked="onCheckboxChanged($event, 'search_databases_enabled')">
					{{ t('integration_notion', 'Enable searching for Notion databases') }}
				</NcCheckboxRadioSwitch>
				<br>
				<p v-if="state.search_pages_enabled || state.search_databases_enabled" class="settings-hint">
					<InformationOutlineIcon :size="20" class="icon" style="margin-right: 5px;" />
					{{ t('integration_notion', 'Warning, everything you type in the search bar will be sent in request to Notion.') }}
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import CheckIcon from 'vue-material-design-icons/Check.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { oauthConnect } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import NotionIcon from './icons/NotionIcon.vue'

export default {
	name: 'PersonalSettings',

	components: {
		NotionIcon,
		NcButton,
		OpenInNewIcon,
		CloseIcon,
		CheckIcon,
		InformationOutlineIcon,
		NcCheckboxRadioSwitch,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_notion', 'user-config'),
			loading: false,
			redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_notion/oauth-redirect'),
		}
	},

	computed: {
		showOAuth() {
			return !!this.state.client_id && !!this.state.client_secret
		},
		connected() {
			return !!this.state.token && !!this.state.user_name
		},
		connectedDisplayName() {
			return this.state.user_name
		},
	},

	watch: {
	},

	mounted() {
		const paramString = window.location.search.substr(1)
		// eslint-disable-next-line
		const urlParams = new URLSearchParams(paramString)
		const glToken = urlParams.get('notionToken')
		if (glToken === 'success') {
			showSuccess(t('integration_notion', 'Successfully connected to Notion!'))
		} else if (glToken === 'error') {
			showError(t('integration_notion', 'Error connecting to Notion:') + ' ' + urlParams.get('message'))
		}
	},

	methods: {
		onLogoutClick() {
			this.state.token = ''
			this.saveOptions({ token: '' })
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_notion/config')
			axios.put(url, req).then((response) => {
				if (values.token === '' && response.data.user_name === '') {
					showSuccess(t('integration_notion', 'Successfully disconnected'))
				} else {
					showSuccess(t('integration_notion', 'Notion options saved'))
				}
			}).catch((error) => {
				showError(
					t('integration_notion', 'Failed to save Notion options')
					+ ': ' + (error.response?.request?.responseText ?? '')
				)
				console.error(error)
			}).then(() => {
				this.loading = false
			})
		},
		onConnectClick() {
			if (this.showOAuth) {
				this.connectWithOauth()
			}
		},
		connectWithOauth() {
			if (this.state.use_popup) {
				oauthConnect(this.state.client_id, null, true)
					.then((data) => {
						this.state.token = 'dummyToken'
						this.state.user_name = data.userName
						this.state.user_id = data.userId
					})
			} else {
				oauthConnect(this.state.client_id, 'settings')
			}
		},
		onCheckboxChanged(newValue, key) {
			this.state[key] = newValue
			this.saveOptions({ [key]: this.state[key] ? '1' : '0' })
		},
	},
}
</script>

<style scoped lang="scss">
#notion_prefs {
	h2 {
		display: flex;

		.notion-icon {
			margin-right: 12px;
		}
	}

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
}
</style>
