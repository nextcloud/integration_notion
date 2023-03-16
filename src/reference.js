import {} from '@nextcloud/vue-richtext'
import { registerWidget } from '@nextcloud/vue/dist/Components/NcRichText.js'

__webpack_nonce__ = btoa(OC.requestToken) // eslint-disable-line
__webpack_public_path__ = OC.linkTo('integration_notion', 'js/') // eslint-disable-line

registerWidget('integration_notion_page_database', async (el, { richObjectType, richObject, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "reference-issue-lazy" */'vue')
	const { default: NotionReferenceWidget } = await import(/* webpackChunkName: "reference-issue-lazy" */'./views/NotionReferenceWidget.vue')
	Vue.mixin({ methods: { t, n } })
	const Widget = Vue.extend(NotionReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})
