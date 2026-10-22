<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Migration;

use Closure;
use OCA\Notion\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2101Date202610211026 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// mark the app config values written by the legacy API as lazy
		$this->appConfig->updateLazy(Application::APP_ID, 'client_id', true);
		$this->appConfig->updateLazy(Application::APP_ID, 'client_secret', true);
		$this->appConfig->updateLazy(Application::APP_ID, 'use_popup', true);
		$this->appConfig->updateLazy(Application::APP_ID, 'link_preview_enabled', true);

		return null;
	}
}
