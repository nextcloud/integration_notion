import ClockOutlineIcon from 'vue-material-design-icons/ClockOutline.vue'
import TextIcon from 'vue-material-design-icons/Text.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import TextLongIcon from 'vue-material-design-icons/TextLong.vue'

export const fields = {
	name: {
		icon: TextIcon,
		label: t('integration_notion', 'Board name'),
		type: 'text',
		placeholder: t('integration_notion', 'board name'),
		default: t('integration_notion', 'New board'),
		mandatory: true,
	},
	description: {
		icon: TextLongIcon,
		label: t('integration_notion', 'Description'),
		type: 'textarea',
		placeholder: t('integration_notion', 'Board description'),
		default: '',
	},
	createdByName: {
		icon: AccountIcon,
		label: t('integration_notion', 'Created by'),
		type: 'text',
		readonly: true,
	},
	createdAt: {
		icon: ClockOutlineIcon,
		label: t('integration_notion', 'Created at'),
		type: 'ncDatetime',
		readonly: true,
	},
}
