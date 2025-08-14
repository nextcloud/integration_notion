/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import './bootstrap.js'
import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'
const View = Vue.extend(PersonalSettings)

new View().$mount('#notion_prefs')
