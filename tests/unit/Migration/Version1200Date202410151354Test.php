<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Tests\Migration;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Migration\Version1200Date202410151354;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;

class Version1200Date202410151354Test extends TestCase {

	public function testPostSchemaChangeEncryptsTheClientCredentialsLazily(): void {
		$values = [
			'client_id' => 'the-client-id',
			'client_secret' => 'the-client-secret',
		];
		$writes = [];
		$appConfig = $this->createMock(IAppConfig::class);
		// the server only returns a lazy value to a lazy read, so a non-lazy read fails here
		$appConfig->expects($this->exactly(2))->method('getValueString')
			->willReturnCallback(function (string $app, string $key, string $default = '', bool $lazy = false) use ($values): string {
				$this->assertSame(Application::APP_ID, $app);
				$this->assertTrue($lazy, 'app config value "' . $key . '" is read lazily');
				return $values[$key] ?? $default;
			});
		$appConfig->expects($this->exactly(2))->method('setValueString')
			->willReturnCallback(function (string $app, string $key, string $value, bool $lazy = false) use (&$writes): bool {
				$writes[] = [$app, $key, $value, $lazy];
				return true;
			});
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('encrypt')->willReturnCallback(fn (string $value): string => 'encrypted:' . $value);

		// no user token to encrypt
		$result = $this->createMock(IResult::class);
		$result->method('fetch')->willReturn(false);
		$queryBuilder = $this->createMock(IQueryBuilder::class);
		foreach (['update', 'set', 'where', 'andWhere', 'select', 'from'] as $method) {
			$queryBuilder->method($method)->willReturnSelf();
		}
		$queryBuilder->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
		$queryBuilder->method('executeQuery')->willReturn($result);
		$connection = $this->createMock(IDBConnection::class);
		$connection->method('getQueryBuilder')->willReturn($queryBuilder);

		$migration = new Version1200Date202410151354($connection, $crypto, $appConfig);
		$migration->postSchemaChange($this->createMock(IOutput::class), fn () => null, []);

		$this->assertSame([
			[Application::APP_ID, 'client_id', 'encrypted:the-client-id', true],
			[Application::APP_ID, 'client_secret', 'encrypted:the-client-secret', true],
		], $writes);
	}
}
