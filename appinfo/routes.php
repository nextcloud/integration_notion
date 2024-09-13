<?php
/**
 *
 * Nextcloud - Notion
 *
 * @copyright Copyright (c) 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @copyright Copyright (c) 2023 Alexander Piskun <bigcat88@icloud.com>
 *
 * @author 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
