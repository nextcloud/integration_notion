<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Search;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Service\NotionAPIService;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;

use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Security\ICrypto;

class NotionSearchDatabasesProvider implements IProvider {

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private ICrypto $crypto,
		private NotionAPIService $service,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'notion-search-databases';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Notion databases');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Notion results
			return -1;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		if (!$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = $query->getLimit();
		$term = $query->getTerm();
		$offset = $query->getCursor();
		$offset = isset($offset) && $offset !== 0 ? $offset : 0;

		$accessToken = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'token');
		if ($accessToken !== '') {
			$accessToken = $this->crypto->decrypt($accessToken);
		}
		$searchDatabasesEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_databases_enabled', '0') === '1';

		if ($accessToken === '' || !$searchDatabasesEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->service->searchDatabases($user->getUID(), $term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$databases = [];
		} else {
			$databases = $searchResult['results'];
		}

		$formattedResults = array_map(function (array $entry): NotionSearchResultEntry {
			return new NotionSearchResultEntry(
				$this->getThumbnailUrl($entry),
				$this->getMainText($entry),
				$this->getSubline($entry),
				$this->getLinkToNotion($entry),
				$this->getThumbnailUrl($entry) === '' ? 'icon-notion-logo' : '',
				false
			);
		}, $databases);

		if (isset($searchResult['has_more']) && $searchResult['has_more']) {
			return SearchResult::paginated(
				$this->getName(),
				$formattedResults,
				$searchResult['next_cursor'] ?? 0
			);
		}
		return SearchResult::complete(
			$this->getName(),
			$formattedResults
		);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getMainText(array $entry): string {
		if (count($entry['title']) > 0 && isset($entry['title'][0]['plain_text'])) {
			return $entry['title'][0]['plain_text'];
		}
		$text = json_encode($entry['title'], JSON_THROW_ON_ERROR);
		return $text !== false ? $text : '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getSubline(array $entry): string {
		$lastEditedTimeFormatted = new \DateTime($entry['last_edited_time'], new \DateTimeZone('UTC'));
		return isset($entry['description'][0]) && count($entry['description']) === 0
			? $entry['description'][0]['plain_text']
			: $this->l10n->t('Last edited on %s', [$lastEditedTimeFormatted->format('F d, Y h:i A')]);
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getLinkToNotion(array $entry): string {
		return $entry['url'] ?? '';
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getThumbnailUrl(array $entry): string {
		if ((isset($entry['icon']['type']) && $entry['icon']['type'] === 'file')
			|| (isset($entry['icon']['type']) && $entry['icon']['type'] === 'external')) {
			return $this->urlGenerator->linkToRoute('integration_notion.notionAPI.getThumbnail', ['notionObjectId' => $entry['id'], 'objectType' => 'database']);
		}
		return '';
	}
}
