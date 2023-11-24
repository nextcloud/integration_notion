<?php

namespace OCA\Notion\Reference;

use OC\Collaboration\Reference\LinkReferenceProvider;
use OC\Collaboration\Reference\ReferenceManager;
use OCA\Notion\AppInfo\Application;
use OCA\Notion\Service\NotionAPIService;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;

use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IConfig;

use OCP\IL10N;
use OCP\IURLGenerator;

class NotionReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider {

	private const RICH_OBJECT_TYPE = Application::APP_ID . '_page_database';

	public function __construct(private NotionAPIService $notionAPIService,
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private ReferenceManager $referenceManager,
		private LinkReferenceProvider $linkReferenceProvider,
		private ?string $userId) {
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'notion-page-database';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Notion page or database');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 10;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getSupportedSearchProviderIds(): array {
		if ($this->userId !== null) {
			$ids = [];
			$searchIssuesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_pages_enabled', '0') === '1';
			$searchReposEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_databases_enabled', '0') === '1';
			if ($searchIssuesEnabled) {
				$ids[] = 'notion-search-pages';
			}
			if ($searchReposEnabled) {
				$ids[] = 'notion-search-databases';
			}
			return $ids;
		}
		return ['notion-search-pages', 'notion-search-databases'];
	}

	/**
	 * @inheritDoc
	 */
	public function matchReference(string $referenceText): bool {
		if ($this->userId !== null) {
			$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '1') === '1';
			if (!$linkPreviewEnabled) {
				return false;
			}
		}
		$adminLinkPreviewEnabled = $this->config->getAppValue(Application::APP_ID, 'link_preview_enabled', '1') === '1';
		if (!$adminLinkPreviewEnabled) {
			return false;
		}
		if (preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:.+\.)?notion\.(?:so|site)\/.+/i', $referenceText) === 1) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveReference(string $referenceText): ?IReference {
		if ($this->matchReference($referenceText)) {
			$objectId = $this->getObjectId($referenceText);
			$objectInfo = $this->notionAPIService->getObjectInfo($this->userId, $objectId);
			if (isset($objectInfo)) {
				[$objectInfo, $createdByUserInfo, $editedByUserInfo] = $objectInfo;
				$reference = new Reference($referenceText);
				$objectTitle = $this->getObjectTitle($objectInfo) ?? '';
				$objectThumbnailUrl = $this->getObjectThumbnailUrl($objectInfo);
				$reference->setRichObject(
					self::RICH_OBJECT_TYPE,
					[
						'id' => $objectId,
						'type' => $objectInfo['object'],
						'title' => $objectTitle,
						'created_by' => $createdByUserInfo,
						'created_time' => $objectInfo['created_time'] ?? '',
						'edited_by' => $editedByUserInfo,
						'last_edited_time' => $objectInfo['last_edited_time'] ?? '',
						'thumbnail_url' => $objectThumbnailUrl,
						'url' => $referenceText,
					]
				);
				return $reference;
			}
			return $this->linkReferenceProvider->resolveReference($referenceText);
		}
		return null;
	}

	/**
	 * Get Notion page or database ID from path
	 */
	private function getObjectId(string $url): ?string {
		$url = explode('?', $url);
		if (isset($url[0])) {
			$url = $url[0];
		}
		preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:.+\.)?notion\.(?:so|site)\/(?:[a-zA-Z0-9-_]*?)?([a-fA-F0-9]+)$/i', $url, $matches);
		if (isset($matches[1])) {
			return $matches[1];
		}
		return null;
	}

	private function getObjectTitle(array $entry) {
		if ($entry['object'] === 'page') {
			$inDatabase = isset($entry['parent']['database_id']);
			return isset($entry['properties']['title']['title'])
				&& count($entry['properties']['title']['title']) === 0
				? $entry['properties']['title']['title'][0]['plain_text']
					. ($inDatabase ? ' (' . $this->l10n->t('in database') . ')' : '')
				: $this->searchForTitleProperty($entry)
					. ($inDatabase ? ' (' . $this->l10n->t('in database') . ')' : '');
		} elseif ($entry['object'] === 'database') {
			return count($entry['title']) > 0 && isset($entry['title'][0]['plain_text'])
				? $entry['title'][0]['plain_text']
				: json_encode($entry['title']);
		}
	}

	private function searchForTitleProperty(array $entry) {
		foreach ($entry['properties'] as $property) {
			if (isset($property['type']) && $property['type'] === 'title') {
				return $property['title'][0]['plain_text'];
			}
		}
		return $this->l10n->t('Untitled page');
	}

	private function getObjectThumbnailUrl(array $entry) {
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'file'
			|| isset($entry['icon']['type']) && $entry['icon']['type'] === 'external') {
			return $this->urlGenerator->linkToRoute('integration_notion.notionAPI.getThumbnail', ['notionObjectId' => $entry['id']]);
		}
		return null;
	}

	/**
	 * We use the userId here because when connecting/disconnecting from the GitHub account,
	 * we want to invalidate all the user cache and this is only possible with the cache prefix
	 * @inheritDoc
	 */
	public function getCachePrefix(string $referenceId): string {
		return $this->userId ?? '';
	}

	/**
	 * Return the type of the Notion object (page or database)
	 */
	public function getObjectType(array $entity): string {
		return $entity['object'];
	}

	/**
	 * We don't use the userId here but rather a reference unique id
	 * @inheritDoc
	 */
	public function getCacheKey(string $referenceId): ?string {
		$objectId = $this->getObjectId($referenceId);
		if ($objectId !== null) {
			return $objectId;
		}
		return $referenceId;
	}

	/**
	 * @param string $userId
	 * @return void
	 */
	public function invalidateUserCache(string $userId): void {
		$this->referenceManager->invalidateCache($userId);
	}
}
