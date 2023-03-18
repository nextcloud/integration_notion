<?php
/**
 *
 * Nextcloud - Notion
 *
 * @copyright Copyright (c) 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @copyright Copyright (c) 2023 Alexander Piskun <bigcat88@icloud.com>
 *
 * @author 2023 Andrey Borysenko <andrey18106x@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Notion\Search;

use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Service\NotionAPIService;

class NotionSearchPagesProvider implements IProvider {
	/** @var IAppManager */
	private $appManager;

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var NotionAPIService
	 */
	private $service;

	public function __construct(IAppManager $appManager,
								IL10N $l10n,
								IConfig $config,
								IURLGenerator $urlGenerator,
								NotionAPIService $service) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->service = $service;
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
				'icon-notion-logo',
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
		$lastEditedTimeFormatted = new \DateTime($entry['last_edited_time']);
		return isset($entry['description'][0]) && count($entry['description']) === 0
			? $entry['description'][0]['plain_text']
			: $this->l10n->t('Last edited on %s', [$lastEditedTimeFormatted->format('d.m.Y H:i')]);
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
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'file') {
			$link = $entry['icon']['url'];
			if (str_starts_with($entry['icon']['url'], '/')) {
				$link = Application::NOTION_DOMAIN . $entry['icon']['url'];
			}
			return $this->urlGenerator->linkToRoute('integration_notion.notionAPI.getThumbnail', ['url' => $link]);
		}
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'external') {
			$link = $entry['icon']['external']['url'];
			if (str_starts_with($entry['icon']['external']['url'], '/')) {
				$link = Application::NOTION_DOMAIN . $entry['icon']['external']['url'];
			}
			return $this->urlGenerator->linkToRoute('integration_notion.notionAPI.getThumbnail', ['url' => $link]);
		}
		return '';
	}
}
