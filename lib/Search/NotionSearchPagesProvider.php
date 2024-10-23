<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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

class NotionSearchPagesProvider implements IProvider {

	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private NotionAPIService $service,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'notion-search-pages';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Notion pages');
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
		$searchPagesEnabled = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'search_pages_enabled', '0') === '1';

		if ($accessToken === '' || !$searchPagesEnabled) {
			return SearchResult::paginated($this->getName(), [], 0);
		}

		$searchResult = $this->service->searchPages($user->getUID(), $term, $offset, $limit);
		if (isset($searchResult['error'])) {
			$pages = [];
		} else {
			$pages = $searchResult['results'];
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
		}, $pages);

		if (isset($searchResult['has_more']) && $searchResult['has_more']) {
			return SearchResult::paginated(
				$this->getName(),
				$formattedResults,
				isset($searchResult['has_more']) && $searchResult['has_more']
					? $searchResult['next_cursor'] : 0
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
		$inDatabase = isset($entry['parent']['database_id']);
		return isset($entry['properties']['title']['title'])
			&& count($entry['properties']['title']['title']) === 0
			? $entry['properties']['title']['title'][0]['plain_text']
				. ($inDatabase ? ' (' . $this->l10n->t('in database') . ')' : '')
			: $this->searchForTitleProperty($entry)
				. ($inDatabase ? ' (' . $this->l10n->t('in database') . ')' : '');
	}

	protected function searchForTitleProperty(array $entry) {
		foreach ($entry['properties'] as $property) {
			if (isset($property['type']) && $property['type'] === 'title') {
				return $property['title'][0]['plain_text'];
			}
		}
		return $this->l10n->t('Untitled page');
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	protected function getSubline(array $entry): ?string {
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
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'file'
			|| isset($entry['icon']['type']) && $entry['icon']['type'] === 'external') {
			return $this->urlGenerator->linkToRoute('integration_notion.notionAPI.getThumbnail', ['notionObjectId' => $entry['id'], 'objectType' => 'page']);
		}
		return '';
	}
}
