import Vue from 'vue'
import './bootstrap.js'
import { loadState } from '@nextcloud/initial-state'
import NotionModalWrapper from './components/NotionModalWrapper.vue'

function init() {
	if (!OCA.Notion) {
		/**
		 * @namespace
		 */
		OCA.Notion = {}
	}

	const wrapperId = 'notionModalWrapper'
	const wrapperElement = document.createElement('div')
	wrapperElement.id = wrapperId
	document.body.append(wrapperElement)

	const View = Vue.extend(NotionModalWrapper)
	OCA.Notion.NotionModalWrapperVue = new View().$mount('#' + wrapperId)

	OCA.Notion.openModal = (boardUrl) => {
		OCA.Notion.NotionModalWrapperVue.openOn(boardUrl)
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
				OCA.Notion.openModal(boardEmbedUrl)
			} else if (href.startsWith('https://miro.com/app/live-embed/')) {
				e.preventDefault()
				e.stopPropagation()
				OCA.Notion.openModal(href)
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
