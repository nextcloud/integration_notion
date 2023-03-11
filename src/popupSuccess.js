import { loadState } from '@nextcloud/initial-state'

const state = loadState('integration_notion', 'popup-data')
const userName = state.user_name
const userId = state.user_id

if (window.opener) {
	window.opener.postMessage({ userName, userId })
	window.close()
}
