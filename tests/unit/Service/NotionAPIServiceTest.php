<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notion\Tests;

use OCA\Notion\AppInfo\Application;
use PHPUnit\Framework\TestCase;

class NotionAPIServiceTest extends TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_notion', $app::APP_ID);
	}
}
