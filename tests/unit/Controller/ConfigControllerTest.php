<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Tests\Controller;

use ArrayObject;
use OCA\Notion\AppInfo\Application;
use OCA\Notion\Controller\ConfigController;
use OCA\Notion\Service\NotionAPIService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigControllerTest extends TestCase {

	private IAppConfig&MockObject $appConfig;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;
	private ICrypto&MockObject $crypto;
	private NotionAPIService&MockObject $notionAPIService;
	private ConfigController $controller;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->expects($this->never())->method('getAppValue');
		$this->appConfig->expects($this->never())->method('setAppValue');
		$this->config = $this->createMock(IConfig::class);
		$this->config->expects($this->never())->method('getAppValue');
		$this->config->expects($this->never())->method('setAppValue');
		$this->config->expects($this->never())->method('deleteAppValue');
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->crypto = $this->createMock(ICrypto::class);
		$this->crypto->method('encrypt')->willReturnCallback(fn (string $value): string => 'encrypted:' . $value);
		$this->crypto->method('decrypt')->willReturnCallback(fn (string $value): string => substr($value, strlen('encrypted:')));
		$this->notionAPIService = $this->createMock(NotionAPIService::class);

		$this->controller = new ConfigController(
			appName: Application::APP_ID,
			request: $this->createMock(IRequest::class),
			appConfig: $this->appConfig,
			config: $this->config,
			urlGenerator: $this->urlGenerator,
			l: $this->createMock(IL10N::class),
			initialStateService: $this->createMock(IInitialState::class),
			crypto: $this->crypto,
			notionAPIService: $this->notionAPIService,
			userId: 'alice',
		);
	}

	/**
	 * The server only returns a lazy value to a lazy read, so a non-lazy read fails here.
	 *
	 * @param array<string, string> $values
	 */
	private function storeLazily(array $values): void {
		$this->appConfig->method('getAppValueString')
			->willReturnCallback(function (string $key, string $default = '', bool $lazy = false) use ($values): string {
				$this->assertTrue($lazy, 'app config value "' . $key . '" is read lazily');
				return $values[$key] ?? $default;
			});
	}

	/**
	 * @return ArrayObject<int, array{string, string, bool}>
	 */
	private function recordWrites(): ArrayObject {
		$writes = new ArrayObject();
		$this->appConfig->method('setAppValueString')
			->willReturnCallback(function (string $key, string $value, bool $lazy = false) use ($writes): bool {
				$writes[] = [$key, $value, $lazy];
				return true;
			});
		return $writes;
	}

	/**
	 * @param array<string, string> $values
	 */
	private function userValues(array $values): void {
		$this->config->method('getUserValue')
			->willReturnCallback(fn (string $userId, string $appName, string $key, string $default = ''): string => $values[$key] ?? $default);
	}

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
	public function testIsUserConnectedReadsTheAdminValuesLazily(array $popup, bool $usePopup): void {
		$this->storeLazily([
			'client_id' => 'encrypted:the-client-id',
			'client_secret' => 'encrypted:the-client-secret',
		] + $popup);
		$this->userValues(['token' => 'encrypted:the-token']);

		$response = $this->controller->isUserConnected();

		$this->assertSame([
			'connected' => true,
			'oauth_possible' => true,
			'use_popup' => $usePopup,
			'client_id' => 'the-client-id',
		], $response->getData());
	}

	/**
	 * @return array<string, array{array<string, string>, string}>
	 */
	public static function oauthRedirectTargets(): array {
		$popup = '/route/integration_notion.config.popupSuccessPage';
		$settings = '/route/settings.PersonalSettings.index?notionToken=success';
		return [
			'popup switched on' => [['use_popup' => '1'], $popup],
			'popup switched off' => [['use_popup' => '0'], $settings],
			'popup never set' => [[], $settings],
		];
	}

	/**
	 * @param array<string, string> $popup
	 */
	#[DataProvider('oauthRedirectTargets')]
	public function testOauthRedirectReadsTheAdminValuesLazily(array $popup, string $target): void {
		$this->storeLazily([
			'client_id' => 'encrypted:the-client-id',
			'client_secret' => 'encrypted:the-client-secret',
		] + $popup);
		$this->userValues([
			'oauth_state' => 'the-state',
			'redirect_uri' => 'https://cloud.example.com/apps/integration_notion/oauth-redirect',
			'oauth_origin' => 'settings',
		]);
		$this->notionAPIService->expects($this->once())
			->method('requestOAuthAccessToken')
			->with([
				'client_id' => 'the-client-id',
				'client_secret' => 'the-client-secret',
				'code' => 'the-code',
				'redirect_uri' => 'https://cloud.example.com/apps/integration_notion/oauth-redirect',
				'grant_type' => 'authorization_code',
			])
			->willReturn([
				'access_token' => 'the-access-token',
				'token_type' => 'bearer',
				'owner' => ['user' => ['id' => 'notion-user', 'name' => 'Alice']],
				'bot_id' => 'the-bot',
				'workspace_name' => 'Workspace',
				'workspace_id' => 'the-workspace',
			]);
		$this->urlGenerator->method('linkToRoute')
			->willReturnCallback(fn (string $route): string => '/route/' . $route);

		$response = $this->controller->oauthRedirect('the-code', 'the-state');

		$this->assertSame($target, $response->getRedirectURL());
	}

	/**
	 * @return array<string, array{array<string, string>, list<array{string, string, bool}>}>
	 */
	public static function adminConfigValues(): array {
		return [
			'popup' => [['use_popup' => '1'], [['use_popup', '1', true]]],
			'link previews' => [['link_preview_enabled' => '0'], [['link_preview_enabled', '0', true]]],
		];
	}

	/**
	 * @param array<string, string> $values
	 * @param list<array{string, string, bool}> $expectedWrites
	 */
	#[DataProvider('adminConfigValues')]
	public function testSetAdminConfigWritesLazily(array $values, array $expectedWrites): void {
		$writes = $this->recordWrites();

		$response = $this->controller->setAdminConfig($values);

		$this->assertSame(1, $response->getData());
		$this->assertSame($expectedWrites, $writes->getArrayCopy());
	}

	/**
	 * @return array<string, array{array<array-key, mixed>}>
	 */
	public static function rejectedAdminConfigValues(): array {
		return [
			'a key the server owns' => [['enabled' => 'yes']],
			'a key the server owns after a valid one' => [['use_popup' => '1', 'enabled' => 'yes']],
			'the client id' => [['client_id' => 'the-client-id']],
			'the client secret' => [['client_secret' => 'the-client-secret']],
			'a key the app no longer uses' => [['override_link_click' => '1']],
			'a value that is not a string' => [['use_popup' => true]],
		];
	}

	/**
	 * @param array<array-key, mixed> $values
	 */
	#[DataProvider('rejectedAdminConfigValues')]
	public function testSetAdminConfigRejectsAnythingElseAndWritesNothing(array $values): void {
		$writes = $this->recordWrites();

		$response = $this->controller->setAdminConfig($values);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $writes->getArrayCopy());
	}

	public function testSetSensitiveAdminConfigWritesLazilyAndKeepsTheEncryption(): void {
		$writes = $this->recordWrites();

		$this->controller->setSensitiveAdminConfig([
			'client_id' => 'the-client-id',
			'client_secret' => '',
		]);

		$this->assertSame([
			['client_id', 'encrypted:the-client-id', true],
			['client_secret', '', true],
		], $writes->getArrayCopy());
	}

	/**
	 * @return array<string, array{array<array-key, mixed>}>
	 */
	public static function rejectedSensitiveAdminConfigValues(): array {
		return [
			'a key the server owns' => [['enabled' => 'yes']],
			'a key the server owns after a valid one' => [['client_id' => 'the-client-id', 'enabled' => 'yes']],
			'the popup switch' => [['use_popup' => '1']],
			'a value that is not a string' => [['client_secret' => ['the-client-secret']]],
		];
	}

	/**
	 * @param array<array-key, mixed> $values
	 */
	#[DataProvider('rejectedSensitiveAdminConfigValues')]
	public function testSetSensitiveAdminConfigRejectsAnythingElseAndWritesNothing(array $values): void {
		$writes = $this->recordWrites();

		$response = $this->controller->setSensitiveAdminConfig($values);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame([], $writes->getArrayCopy());
	}
}
