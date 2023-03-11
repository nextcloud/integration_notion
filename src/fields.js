import ClockOutlineIcon from 'vue-material-design-icons/ClockOutline.vue'
// import ShareVariantIcon from 'vue-material-design-icons/ShareVariant.vue'
// import HomeGroupIcon from 'vue-material-design-icons/HomeGroup.vue'
// import AccountGroupIcon from 'vue-material-design-icons/AccountGroup.vue'
// import AccountGroupOutlineIcon from 'vue-material-design-icons/AccountGroupOutline.vue'
import TextIcon from 'vue-material-design-icons/Text.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
// import LockIcon from 'vue-material-design-icons/Lock.vue'
// import PaletteIcon from 'vue-material-design-icons/Palette.vue'
// import PaletteSwatchIcon from 'vue-material-design-icons/PaletteSwatch.vue'
// import PaletteSwatchOutlineIcon from 'vue-material-design-icons/PaletteSwatchOutline.vue'
import TextLongIcon from 'vue-material-design-icons/TextLong.vue'
// import CalendarMonthIcon from 'vue-material-design-icons/CalendarMonth.vue'
// import FormatListBulletedTypeIcon from 'vue-material-design-icons/FormatListBulletedType.vue'

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
	/*
	password: {
		icon: LockIcon,
		label: t('integration_notion', 'Password'),
		type: 'password',
		placeholder: t('integration_notion', 'Password'),
		togglable: true,
		enabled: false,
		view: false,
		default: '',
	},
	comment: {
		icon: TextLongIcon,
		label: t('integration_notion', 'Text area'),
		type: 'textarea',
		placeholder: t('integration_notion', 'long text\nplaceholder\n...'),
		default: 'optional\ndefault\nvalue',
	},
	date: {
		icon: CalendarMonthIcon,
		label: t('integration_notion', 'Date'),
		type: 'ncDate',
		default: new Date('2022-12-29'),
	},
	datetime: {
		icon: ClockOutlineIcon,
		label: t('integration_notion', 'Datetime'),
		type: 'ncDatetime',
		default: new Date('2022-12-29 01:02:03'),
	},
	color: {
		icon: PaletteIcon,
		label: t('integration_notion', 'Color'),
		type: 'ncColor',
		default: '#6ea68f',
	},
	share: {
		icon: ShareVariantIcon,
		label: t('integration_notion', 'Switch'),
		type: 'ncSwitch',
		default: true,
		// enabledIcon: TextIcon,
		// disabledIcon: TextLongIcon,
	},
	share2: {
		label: t('integration_notion', 'Switch without icon'),
		type: 'ncSwitch',
		default: false,
		// enabledIcon: TextIcon,
		// disabledIcon: TextLongIcon,
	},
	check: {
		icon: ShareVariantIcon,
		label: t('integration_notion', 'Checkbox'),
		type: 'ncCheckbox',
		default: true,
		// enabledIcon: TextIcon,
		// disabledIcon: TextLongIcon,
	},
	check2: {
		label: t('integration_notion', 'Checkbox without icon'),
		type: 'ncCheckbox',
		default: false,
		// enabledIcon: TextIcon,
		// disabledIcon: TextLongIcon,
	},
	type: {
		icon: FormatListBulletedTypeIcon,
		label: t('integration_notion', 'Multi select'),
		type: 'select',
		placeholder: t('integration_notion', 'Select a value'),
		options: {
			one: { id: 'one', label: 'First multiselect value', icon: AccountGroupIcon },
			two: { id: 'two', label: 'Second multiselect value', icon: AccountGroupOutlineIcon },
			three: { id: 'three', label: 'Third multiselect long long long value', icon: HomeGroupIcon },
			four: { id: 'four', label: 'Fourth multiselect value without icon' },
		},
		default: { id: 'one', label: 'First multiselect value', icon: AccountGroupIcon },
	},
	style: {
		icon: PaletteSwatchIcon,
		label: t('integration_notion', 'Custom radio selector'),
		type: 'customRadioSet',
		options: {
			one: { label: 'First radio value', icon: PaletteSwatchIcon },
			two: { label: 'Second radio value', icon: PaletteSwatchOutlineIcon },
			three: { label: 'Third radio value with long long long text', icon: PaletteIcon },
			four: { label: 'Fourth radio value without icon' },
		},
		default: 'two',
	},
	permissions: {
		icon: PaletteSwatchIcon,
		label: t('integration_notion', 'Classic Radio selector'),
		type: 'ncRadioSet',
		options: {
			one: { label: 'First radio value', icon: PaletteSwatchIcon },
			two: { label: 'Second radio value', icon: PaletteSwatchOutlineIcon },
			three: { label: 'Third radio value with long text', icon: PaletteIcon },
			four: { label: 'Fourth radio value without icon' },
		},
		default: 'three',
	},
	acls: {
		icon: PaletteSwatchIcon,
		label: t('integration_notion', 'Classic multi checkbox selector'),
		type: 'ncCheckboxSet',
		options: {
			one: { label: 'First radio value', icon: PaletteSwatchIcon },
			two: { label: 'Second radio value', icon: PaletteSwatchOutlineIcon },
			three: { label: 'Third radio value with long text', icon: PaletteIcon },
			four: { label: 'Fourth radio value without icon' },
		},
		default: ['one', 'three'],
	},
	*/
}
