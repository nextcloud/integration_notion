<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\AppInfo;

use OCA\Notion\Listener\NotionReferenceListener;
use OCA\Notion\Listener\UnifiedSearchCSSLoader;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

use OCP\Collaboration\Reference\RenderReferenceEvent;

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
		$context->registerEventListener(RenderReferenceEvent::class, NotionReferenceListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
