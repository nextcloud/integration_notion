<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Tests\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Notion\Reference\NotionReferenceProvider;
use OCA\Notion\Service\NotionAPIService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotionReferenceProviderTest extends TestCase {

	/**
	 * @return array<string, array{array<string, string>, bool}>
	 */
	public static function linkPreviewValues(): array {
		return [
			'link previews switched on' => [['link_preview_enabled' => '1'], true],
			'link previews switched off' => [['link_preview_enabled' => '0'], false],
			'link previews never set' => [[], true],
		];
	}

	/**
	 * @param array<string, string> $values
	 */
	#[DataProvider('linkPreviewValues')]
	public function testMatchReferenceReadsTheAdminLinkPreviewSwitchLazily(array $values, bool $matches): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->never())->method('getAppValue');
		// the server only returns a lazy value to a lazy read, so a non-lazy read fails here
		$appConfig->expects($this->once())->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($values): string {
				$this->assertSame('link_preview_enabled', $key);
				$this->assertTrue($lazy, 'app config value "' . $key . '" is read lazily');
				return $values[$key] ?? $default;
			});
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())->method('getAppValue');
		$config->method('getUserValue')
			->willReturnCallback(fn (string $userId, string $appName, string $key, string $default = ''): string => '1');

		$provider = new NotionReferenceProvider(
			notionAPIService: $this->createMock(NotionAPIService::class),
			appConfig: $appConfig,
			config: $config,
			l10n: $this->createMock(IL10N::class),
			urlGenerator: $this->createMock(IURLGenerator::class),
			referenceManager: $this->createMock(ReferenceManager::class),
			linkReferenceProvider: $this->createMock(LinkReferenceProvider::class),
			userId: 'alice',
		);

		$this->assertSame($matches, $provider->matchReference('https://www.notion.so/workspace/Page-0123456789abcdef0123456789abcdef'));
	}
}
