<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Tests\Migration;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Migration\Version2101Date202610211026;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

class Version2101Date202610211026Test extends TestCase {

	public function testPostSchemaChangeMarksTheAppValuesLazy(): void {
		$calls = [];
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->exactly(4))->method('updateLazy')
			->willReturnCallback(function (string $app, string $key, bool $lazy) use (&$calls): bool {
				$calls[] = [$app, $key, $lazy];
				return true;
			});

		$migration = new Version2101Date202610211026($appConfig);
		$migration->postSchemaChange($this->createMock(IOutput::class), fn () => null, []);

		$this->assertSame([
			[Application::APP_ID, 'client_id', true],
			[Application::APP_ID, 'client_secret', true],
			[Application::APP_ID, 'use_popup', true],
			[Application::APP_ID, 'link_preview_enabled', true],
		], $calls);
	}
}
