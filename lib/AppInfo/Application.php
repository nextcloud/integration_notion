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

namespace OCA\Notion\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Util;

use OCA\Notion\Listener\AddContentSecurityPolicyListener;

/**
 * Class Application
 *
 * @package OCA\Notion\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'integration_notion';

	public const INTEGRATION_USER_AGENT = 'Nextcloud Notion integration';
	public const NOTION_API_BASE_URL = 'https://api.notion.com';
	public const NOTION_DOMAIN = 'https://notion.so';
	public const NOTION_SUBDOMAINS = 'https://*.notion.site';

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(
			AddContentSecurityPolicyEvent::class,
			AddContentSecurityPolicyListener::class
		);

		$context->registerSearchProvider(\OCA\Notion\Search\NotionSearchDatabasesProvider::class);
		$context->registerSearchProvider(\OCA\Notion\Search\NotionSearchPagesProvider::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(function (
			IInitialState $initialState,
			IConfig $config,
			?string $userId
		) {
			$overrideClick = $config->getAppValue(Application::APP_ID, 'override_link_click', '0') === '1';
			$initialState->provideInitialState('override_link_click', $overrideClick);
			Util::addScript(self::APP_ID, self::APP_ID . '-standalone');
		});
	}
}

