import Vue from 'vue'
import './bootstrap.js'
import { loadState } from '@nextcloud/initial-state'
import MiroModalWrapper from './components/MiroModalWrapper.vue'

function init() {
	if (!OCA.Miro) {
		/**
		 * @namespace
		 */
		OCA.Miro = {}
	}

	const wrapperId = 'miroModalWrapper'
	const wrapperElement = document.createElement('div')
	wrapperElement.id = wrapperId
	document.body.append(wrapperElement)

	const View = Vue.extend(MiroModalWrapper)
	OCA.Miro.MiroModalWrapperVue = new View().$mount('#' + wrapperId)

	OCA.Miro.openModal = (boardUrl) => {
		OCA.Miro.MiroModalWrapperVue.openOn(boardUrl)
	}
}

function listen() {
	const body = document.querySelector('body')
	body.addEventListener('click', (e) => {
		const link = (e.target.tagName === 'A')
			? e.target
			: (e.target.parentElement?.tagName === 'A')
				? e.target.parentElement
				: null
		if (link !== null) {
			const href = link.getAttribute('href')
			if (!href) {
				return
			}
			if (href.startsWith('https://miro.com/app/board/')) {
				e.preventDefault()
				e.stopPropagation()
				const boardId = href.replace('https://miro.com/app/board/', '')
				const boardEmbedUrl = 'https://miro.com/app/live-embed/' + boardId + '/'
				OCA.Miro.openModal(boardEmbedUrl)
			} else if (href.startsWith('https://miro.com/app/live-embed/')) {
				e.preventDefault()
				e.stopPropagation()
				OCA.Miro.openModal(href)
			}
		}
	})
}

const overrideLinkClick = loadState('integration_notion', 'override_link_click')
init()
console.debug('[Miro] standalone modal is ready')
if (overrideLinkClick) {
	console.debug('[Miro] will handle clicks on links')
	listen()
}
