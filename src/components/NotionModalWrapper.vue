<template>
	<div id="nuiteqModalWrapper">
		<NotionModal v-if="open && boardUrl"
			:board-url="boardUrl"
			@close="onClose" />
	</div>
</template>

<script>
import NotionModal from './NotionModal.vue'

export default {
	name: 'NotionModalWrapper',

	components: {
		NotionModal,
	},

	props: {
	},

	data() {
		return {
			boardUrl: null,
			open: false,
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		openOn(boardUrl) {
			this.boardUrl = boardUrl
			this.open = true
		},
		onClose() {
			this.open = false
			this.$emit('close')
		},
	},
}
</script>

<style scoped lang="scss">
// nothing yet
</style>
