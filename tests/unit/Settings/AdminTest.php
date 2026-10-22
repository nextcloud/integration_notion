<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Tests\Settings;

use OCA\Notion\Settings\Admin;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Security\ICrypto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase {

	/**
	 * @return array<string, array{array<string, string>, bool}>
	 */
	public static function popupValues(): array {
		return [
			'popup switched on' => [['use_popup' => '1'], true],
			'popup switched off' => [['use_popup' => '0'], false],
			'popup never set' => [[], false],
		];
	}

	/**
	 * @param array<string, string> $popup
	 */
	#[DataProvider('popupValues')]
	public function testGetFormReadsTheAdminValuesLazily(array $popup, bool $usePopup): void {
		$values = [
			'client_id' => 'encrypted:the-client-id',
			'client_secret' => 'encrypted:the-client-secret',
		] + $popup;
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->never())->method('getAppValue');
		// the server only returns a lazy value to a lazy read, so a non-lazy read fails here
		$appConfig->expects($this->exactly(3))->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($values): string {
				$this->assertTrue($lazy, 'app config value "' . $key . '" is read lazily');
				return $values[$key] ?? $default;
			});
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('decrypt')->willReturnCallback(fn (string $value): string => substr($value, strlen('encrypted:')));
		$initialState = $this->createMock(IInitialState::class);
		$initialState->expects($this->once())
			->method('provideInitialState')
			->with('admin-config', [
				'client_id' => 'the-client-id',
				'client_secret' => 'dummyToken',
				'use_popup' => $usePopup,
			]);

		$admin = new Admin(
			appConfig: $appConfig,
			initialStateService: $initialState,
			crypto: $crypto,
		);
		$admin->getForm();
	}
}
