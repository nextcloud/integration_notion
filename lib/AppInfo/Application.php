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

use OCA\Notion\Listener\UnifiedSearchCSSLoader;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Collaboration\Reference\RenderReferenceEvent;

use OCA\Notion\Listener\NotionReferenceListener;

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
	public const NOTION_API_VERSION = '2022-06-28';

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, UnifiedSearchCSSLoader::class);

		$context->registerSearchProvider(\OCA\Notion\Search\NotionSearchDatabasesProvider::class);
		$context->registerSearchProvider(\OCA\Notion\Search\NotionSearchPagesProvider::class);

		$context->registerReferenceProvider(\OCA\Notion\Reference\NotionReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class,NotionReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}

