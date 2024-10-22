<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Migration;

use Closure;
use OCA\Notion\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version1200Date202410151354 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
		private ICrypto $crypto,
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
		foreach (['appconfig', 'preferences'] as $tableToUpdate) {
			$qbUpdate = $this->connection->getQueryBuilder();
			$qbUpdate->update($tableToUpdate)
				->set('configvalue', $qbUpdate->createParameter('updateValue'))
				->where(
					$qbUpdate->expr()->eq('appid', $qbUpdate->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
				)
				->andWhere(
					$qbUpdate->expr()->eq('configkey', $qbUpdate->createParameter('updateConfigKey'))
				);
			if ($tableToUpdate === 'preferences') {
				$qbUpdate->andWhere(
					$qbUpdate->expr()->eq('userid', $qbUpdate->createParameter('updateUserId'))
				);
			}

			$qbSelect = $this->connection->getQueryBuilder();
			$columns = $tableToUpdate === 'preferences' ? ['userid', 'configvalue', 'configkey'] : ['configvalue', 'configkey'];
			$qbSelect->select(...$columns)
				->from($tableToUpdate)
				->where(
					$qbSelect->expr()->eq('appid', $qbSelect->createNamedParameter(Application::APP_ID, IQueryBuilder::PARAM_STR))
				);

			$or = $qbSelect->expr()->orx();
			$or->add($qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('token', IQueryBuilder::PARAM_STR)));
			$or->add($qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('client_id', IQueryBuilder::PARAM_STR)));
			$or->add($qbSelect->expr()->eq('configkey', $qbSelect->createNamedParameter('client_secret', IQueryBuilder::PARAM_STR)));
			$qbSelect->andWhere($or);

			$qbSelect->andWhere(
				$qbSelect->expr()->nonEmptyString('configvalue')
			)
				->andWhere(
					$qbSelect->expr()->isNotNull('configvalue')
				);
			$req = $qbSelect->executeQuery();
			while ($row = $req->fetch()) {
				$configKey = $row['configkey'];
				$storedClearValue = $row['configvalue'];
				$encryptedValue = $this->crypto->encrypt($storedClearValue);
				$qbUpdate->setParameter('updateConfigKey', $configKey, IQueryBuilder::PARAM_STR);
				$qbUpdate->setParameter('updateValue', $encryptedValue, IQueryBuilder::PARAM_STR);
				if ($tableToUpdate === 'preferences') {
					$userId = $row['userid'];
					$qbUpdate->setParameter('updateUserId', $userId, IQueryBuilder::PARAM_STR);
				}
				$qbUpdate->executeStatement();
			}
			$req->closeCursor();
		}
		return null;
	}
}
