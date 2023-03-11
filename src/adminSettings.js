/* jshint esversion: 6 */

/**
 * Nextcloud - Notion
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author Andrey Borysenko <andrey18106x@gmail.com>
 * @copyright Andrey Borysenko 2023
 */

import Vue from 'vue'
import './bootstrap.js'
import AdminSettings from './components/AdminSettings.vue'

// eslint-disable-next-line
'use strict'

// eslint-disable-next-line
new Vue({
	el: '#notion_prefs',
	render: h => h(AdminSettings),
})
