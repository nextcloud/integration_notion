<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'config#isUserConnected', 'url' => '/is-connected', 'verb' => 'GET'],
		['name' => 'config#oauthRedirect', 'url' => '/oauth-redirect', 'verb' => 'GET'],
		['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'config#setSensitiveAdminConfig', 'url' => '/sensitive-admin-config', 'verb' => 'PUT'],
		['name' => 'config#popupSuccessPage', 'url' => '/popup-success', 'verb' => 'GET'],

		['name' => 'notionAPI#getThumbnail', 'url' => '/thumbnail', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserDatabases', 'url' => '/databases', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserDatabase', 'url' => '/databases/{id}', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserComments', 'url' => '/comments', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserBlocks', 'url' => '/blocks', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserPages', 'url' => '/pages', 'verb' => 'GET'],
		['name' => 'notionAPI#getUserPage', 'url' => '/pages/{id}', 'verb' => 'GET'],
	]
];
