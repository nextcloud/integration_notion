<template>
	<div id="miro_prefs" class="section">
		<h2>
			<MiroIcon class="miro-icon" />
			{{ t('integration_notion', 'Miro integration') }}
		</h2>
		<p v-if="!showOAuth && !connected" class="settings-hint">
			{{ t('integration_notion', 'Ask your administrator to configure the Miro integration in Nextcloud.') }}
		</p>
		<div id="miro-content">
			<NcButton v-if="!connected && showOAuth"
				id="miro-connect"
				class="field"
				:disabled="loading === true"
				:class="{ loading }"
				@click="onConnectClick">
				<template #icon>
					<OpenInNewIcon />
				</template>
				{{ t('integration_notion', 'Connect to Miro') }}
			</NcButton>
			<div v-if="connected" class="field">
				<label class="miro-connected">
					<CheckIcon :size="24" class="icon" />
					{{ t('integration_notion', 'Connected as {user}', { user: connectedDisplayName }) }}
				</label>
				<NcButton id="miro-rm-cred" @click="onLogoutClick">
					<template #icon>
						<CloseIcon />
					</template>
					{{ t('integration_notion', 'Disconnect from Miro') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import CheckIcon from 'vue-material-design-icons/Check.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { oauthConnect } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import MiroIcon from './icons/MiroIcon.vue'

export default {
	name: 'PersonalSettings',

	components: {
		MiroIcon,
		NcButton,
		OpenInNewIcon,
		CloseIcon,
		CheckIcon,
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
		const glToken = urlParams.get('miroToken')
		if (glToken === 'success') {
			showSuccess(t('integration_notion', 'Successfully connected to Miro!'))
		} else if (glToken === 'error') {
			showError(t('integration_notion', 'Error connecting to Miro:') + ' ' + urlParams.get('message'))
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
					showSuccess(t('integration_notion', 'Miro options saved'))
				}
			}).catch((error) => {
				showError(
					t('integration_notion', 'Failed to save Miro options')
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
	},
}
</script>

<style scoped lang="scss">
#miro_prefs {
	h2 {
		display: flex;

		.miro-icon {
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
