/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import './bootstrap.js'
import Vue from 'vue'
import AdminSettings from './components/AdminSettings.vue'
const View = Vue.extend(AdminSettings)

new View().$mount('#notion_prefs')
