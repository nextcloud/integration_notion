<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Notion\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Service to make requests to the Notion API
 */
class NotionAPIService {

	private IClient $client;

	public function __construct(
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IConfig $config,
		private ICrypto $crypto,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $userId
	 * @return array
	 * @throws Exception
	 */
	public function getUserDatabases(string $userId): array {
		return $this->request($userId, 'v1/search/', [
			'filter' => [
				'value' => 'database',
				'property' => 'object'
			]
		], 'POST');
	}

	/**
	 * @param string $userId
	 * @param string $databaseId
	 * @return array
	 * @throws Exception
	 */
	public function getUserDatabase(string $userId, string $databaseId): array {
		return $this->request($userId, 'v1/databases/' . $databaseId);
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function getUserComment(string $userId): array {
		// TODO
		return [];
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function getUserBlock(string $userId): array {
		// TODO
		return [];
	}

	/**
	 * @param string $userId
	 * @return array|string[]
	 * @throws Exception
	 */
	public function getUserPages(string $userId): array {
		return $this->request($userId, 'v1/search/', [
			'filter' => [
				'value' => 'page',
				'property' => 'object'
			]
		], 'POST');
	}

	/**
	 * @param string $userId
	 * @param string $pageId
	 * @return array|string[]
	 * @throws Exception
	 */
	public function getUserPage(string $userId, string $pageId): array {
		return $this->request($userId, 'v1/pages/' . $pageId);
	}

	/**
	 * Search Notion pages
	 *
	 * @param string $userId
	 * @param string $query
	 * @param string|int $offset
	 * @param int $limit
	 *
	 * @return array
	 * @throws Exception
	 */
	public function searchPages(string $userId, string $query, string|int $offset = 0, int $limit = 5): array {
		$params = [
			'query' => $query,
			'sort' => [
				'direction' => 'ascending',
				'timestamp' => 'last_edited_time'
			],
			'filter' => [
				'value' => 'page',
				'property' => 'object'
			],
			'page_size' => $limit
		];
		if ($offset !== 0) {
			$params['start_cursor'] = $offset;
		}
		return $this->request($userId, 'v1/search', $params, 'POST');
	}

	/**
	 * Search Notion databases
	 *
	 * @param string $userId
	 * @param string $query
	 * @param string|int $offset
	 * @param int $limit
	 *
	 * @return array
	 * @throws Exception
	 */
	public function searchDatabases(string $userId, string $query, string|int $offset = 0, int $limit = 5): array {
		$params = [
			'query' => $query,
			'sort' => [
				'direction' => 'ascending',
				'timestamp' => 'last_edited_time'
			],
			'filter' => [
				'value' => 'database',
				'property' => 'object'
			],
			'page_size' => $limit
		];
		if ($offset !== 0) {
			$params['start_cursor'] = $offset;
		}
		return $this->request($userId, 'v1/search', $params, 'POST');
	}

	public function getUserInfo(string $userId, string $notionUserId): ?array {
		return $this->request($userId, 'v1/users/' . $notionUserId);
	}

	/**
	 * Request a thumbnail image for Notion page or database
	 *
	 * @param string $userId
	 * @param string $notionObjectId
	 * @param string $objectType
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public function getThumbnail(string $userId, string $notionObjectId, string $objectType = ''): ?array {
		[$objectInfo] = $this->getObjectInfo($userId, $notionObjectId, $objectType);
		$url = $this->getThumbnailUrl($objectInfo);
		if ($url !== '') {
			$thumbnailResponse = $this->client->get($url);
			if ($thumbnailResponse->getStatusCode() === Http::STATUS_OK) {
				return [
					'body' => $thumbnailResponse->getBody(),
					'headers' => $thumbnailResponse->getHeaders(),
				];
			}
		}
		return null;
	}

	/**
	 * @param array $entry
	 * @return string
	 */
	public function getThumbnailUrl(array $entry): string {
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'file') {
			$link = $entry['icon']['url'];
			if (str_starts_with($entry['icon']['url'], '/')) {
				$link = Application::NOTION_DOMAIN . $entry['icon']['url'];
			}
			return $link;
		}
		if (isset($entry['icon']['type']) && $entry['icon']['type'] === 'external') {
			$link = $entry['icon']['external']['url'];
			if (str_starts_with($entry['icon']['external']['url'], '/')) {
				$link = Application::NOTION_DOMAIN . $entry['icon']['external']['url'];
			}
			return $link;
		}
		return '';
	}

	/**
	 * Get Notion object info (page or database)
	 *
	 * @param string $userId
	 * @param string $objectId
	 * @param string $objectType
	 *
	 * @return array|null
	 * @throws Exception
	 */
	public function getObjectInfo(string $userId, string $objectId, string $objectType = ''): ?array {
		if ($objectType === 'page') {
			return $this->getPageInfo($userId, $objectId);
		}
		if ($objectType === 'database') {
			return $this->getDatabaseInfo($userId, $objectId);
		}
		if ($objectType === '') {
			$pageInfo = $this->getPageInfo($userId, $objectId);
			$databaseInfo = $this->getDatabaseInfo($userId, $objectId);
			if ($pageInfo !== null && !isset($pageInfo['error'])) {
				return $pageInfo;
			}
			if ($databaseInfo !== null && !isset($databaseInfo['error'])) {
				return $databaseInfo;
			}
		}
		return null;
	}

	/**
	 * Get Notion page info
	 *
	 * @param string $userId
	 * @param string $objectId
	 *
	 * @return array|null [pageInfo, createdByUserInfo, lastEditedByUserInfo]
	 * @throws Exception
	 */
	public function getPageInfo(string $userId, string $objectId): ?array {
		$pageInfo = $this->getUserPage($userId, $objectId);
		if (!isset($pageInfo['error'])) {
			$createdByUserInfo = $this->getUserInfo($userId, $pageInfo['created_by']['id']);
			if ($pageInfo['last_edited_by']['id'] !== $pageInfo['created_by']['id']) {
				$lastEditedByUserInfo = $this->getUserInfo($userId, $pageInfo['last_edited_by']['id']);
			}
			$lastEditedByUserInfo = $createdByUserInfo;
			return [$pageInfo, $createdByUserInfo, $lastEditedByUserInfo];
		}
		return null;
	}

	/**
	 * Get Notion database info
	 *
	 * @param string $userId
	 * @param string $objectId
	 *
	 * @return array|null [databaseInfo, createdByUserInfo, lastEditedByUserInfo]
	 * @throws Exception
	 */
	public function getDatabaseInfo(string $userId, string $objectId): ?array {
		$databaseInfo = $this->getUserDatabase($userId, $objectId);
		if (!isset($databaseInfo['error'])) {
			$createdByUserInfo = $this->getUserInfo($userId, $databaseInfo['created_by']['id']);
			if ($databaseInfo['last_edited_by']['id'] !== $databaseInfo['created_by']['id']) {
				$lastEditedByUserInfo = $this->getUserInfo($userId, $databaseInfo['last_edited_by']['id']);
			}
			$lastEditedByUserInfo = $createdByUserInfo;
			return [$databaseInfo, $createdByUserInfo, $lastEditedByUserInfo];
		}
		return null;
	}

	/**
	 * @param string $userId
	 * @param string $endPoint
	 * @param array $params
	 * @param string $method
	 * @param bool $jsonResponse
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function request(string $userId, string $endPoint, array $params = [], string $method = 'GET',
		bool $jsonResponse = true) {
		$accessToken = $this->config->getUserValue($userId, Application::APP_ID, 'token');
		if ($accessToken !== '') {
			$accessToken = $this->crypto->decrypt($accessToken);
		}
		try {
			$url = Application::NOTION_API_BASE_URL . '/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
					'Authorization' => 'Bearer ' . $accessToken,
					'Notion-Version' => Application::NOTION_API_VERSION // Latest Notion API version (release date)
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['json'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				if ($jsonResponse) {
					return json_decode($body, true);
				} else {
					return $body;
				}
			}
		} catch (ServerException|ClientException $e) {
			$this->logger->debug('Notion API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function requestOAuthAccessToken(array $params): array {
		try {
			$url = Application::NOTION_API_BASE_URL . '/v1/oauth/token';
			$options = [
				'headers' => [
					'User-Agent' => Application::INTEGRATION_USER_AGENT,
					'Content-Type' => 'application/json',
					'Authorization'
						=> 'Basic ' . base64_encode($params['client_id'] . ':' . $params['client_secret']),
				],
			];
			$options['body'] = json_encode($params);
			$response = $this->client->post($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();
			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('OAuth access token refused')];
			} else {
				return json_decode($body, true);
			}
		} catch (Exception $e) {
			$this->logger->warning('Notion OAuth error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
