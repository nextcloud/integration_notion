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

namespace OCA\Notion\Service;

use Datetime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Notion\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;
use OCP\Share\IManager as ShareManager;

/**
 * Service to make requests to the Notion API
 */
class NotionAPIService {
	/**
	 * @var string
	 */
	private $appName;
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var \OCP\Http\Client\IClient
	 */
	private $client;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IRootFolder
	 */
	private $root;
	/**
	 * @var ShareManager
	 */
	private $shareManager;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	public function __construct(string $appName,
								LoggerInterface $logger,
								IL10N $l10n,
								IConfig $config,
								IRootFolder $root,
								ShareManager $shareManager,
								IURLGenerator $urlGenerator,
								IClientService $clientService) {
		$this->appName = $appName;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->client = $clientService->newClient();
		$this->config = $config;
		$this->root = $root;
		$this->shareManager = $shareManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function getUserDatabases(string $userId): array {
		$result = $this->request($userId, 'v1/search/', [
			'filter' => [
				'value' => 'database',
				'property' => 'object'
			]
		], 'POST');
		return $result;
	}

	/**
	 * @param string $userId
	 * @param string $databaseId
	 * @return array
	 */
	public function getUserDatabase(string $userId, string $databaseId): array {
		$result = $this->request($userId, 'v1/databases/' . $databaseId, [], 'GET');
		return $result;
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function getUserComment(string $userId): array {
		// TODO
		return array();
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	public function getUserBlock(string $userId): array {
		// TODO
		return array();
	}

	/**
	 * @param string $userId
	 * @return array|string[]
	 */
	public function getUserPages(string $userId): array {
		$result = $this->request($userId, 'v1/search/', [
			'filter' => [
				'value' => 'page',
				'property' => 'object'
			]
		], 'POST');
		return $result;
	}

	/**
	 * @param string $userId
	 * @param string $databaseId
	 * @return array|string[]
	 */
	public function getUserPage(string $userId, string $pageId): array {
		$result = $this->request($userId, 'v1/pages/' . $pageId, [], 'GET');
		return $result;
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
		$result = $this->request($userId, 'v1/search', $params, 'POST', true);
		return $result;
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
		$result = $this->request($userId, 'v1/search', $params, 'POST', true);
		return $result;
	}

	public function getUserInfo(string $userId, string $notionUserId): ?array {
		$result = $this->request($userId, 'v1/users/' . $notionUserId, [], 'GET');
		return $result;
	}

	/**
	 * Request a thumbnail image for Notion page or database
	 *
	 * @param string $userId
	 * @param string $notionObjectId
	 * @param string $objectType
	 *
	 * @return array|null
	 */
	public function getThumbnail(string $userId, string $notionObjectId, string $objectType = ''): ?array {
		[$objectInfo] = $this->getObjectInfo($userId, $notionObjectId, $objectType);
		$url = $this->getThumbnailUrl($objectInfo);
		if ($url !== null && $url !== '') {
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
		try {
			$url = Application::NOTION_API_BASE_URL . '/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent'  => Application::INTEGRATION_USER_AGENT,
					'Authorization' => 'Bearer ' . $accessToken,
					'Notion-Version' => Application::NOTION_API_VERSION // Latest Notion API version (release date)
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = '';
					foreach ($params as $key => $value) {
						if (is_array($value)) {
							foreach ($value as $oneArrayValue) {
								$paramsContent .= $key . '[]=' . urlencode($oneArrayValue) . '&';
							}
							unset($params[$key]);
						}
					}
					$paramsContent .= http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['json'] = $params;
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
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
		} catch (ServerException | ClientException $e) {
			$this->logger->debug('Notion API error : '.$e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @param string $userId
	 * @return bool
	 * @throws \OCP\PreConditionNotMetException
	 */
	private function refreshToken(string $userId): bool {
		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret');
		$redirect_uri = $this->config->getUserValue($userId, Application::APP_ID, 'redirect_uri');
		$refreshToken = $this->config->getUserValue($userId, Application::APP_ID, 'refresh_token');
		if (!$refreshToken) {
			$this->logger->error('No Notion refresh token found', ['app' => Application::APP_ID]);
			return false;
		}
		$result = $this->requestOAuthAccessToken([
			'client_id' => $clientID,
			'client_secret' => $clientSecret,
			'grant_type' => 'refresh_token',
			'redirect_uri' => $redirect_uri,
			'refresh_token' => $refreshToken,
		]);
		if (isset($result['access_token'])) {
			$this->logger->info('Notion access token successfully refreshed', ['app' => Application::APP_ID]);
			$accessToken = $result['access_token'];
			$refreshToken = $result['refresh_token'];
			$this->config->setUserValue($userId, Application::APP_ID, 'token', $accessToken);
			$this->config->setUserValue($userId, Application::APP_ID, 'refresh_token', $refreshToken);
			if (isset($result['expires_in'])) {
				$nowTs = (new Datetime())->getTimestamp();
				$expiresAt = $nowTs + (int) $result['expires_in'];
				$this->config->setUserValue($userId, Application::APP_ID, 'token_expires_at', $expiresAt);
			}
			return true;
		} else {
			// impossible to refresh the token
			$this->logger->error(
				'Token is not valid anymore. Impossible to refresh it. '
					. $result['error'] . ' '
					. $result['error_description'] ?? '[no error description]',
				['app' => Application::APP_ID]
			);
			return false;
		}
	}

	/**
	 * @param array $params
	 * @param string $method
	 * @return array
	 */
	public function requestOAuthAccessToken(array $params): array {
		try {
			$url = Application::NOTION_API_BASE_URL . '/v1/oauth/token';
			$options = [
				'headers' => [
					'User-Agent'  => Application::INTEGRATION_USER_AGENT,
					'Content-Type' => 'application/json',
					'Authorization' =>
						'Basic ' . base64_encode($params['client_id'] . ':' . $params['client_secret']),
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
			$this->logger->warning('Notion OAuth error : '.$e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	public function revokeToken(string $userId): bool {
		// $token = $this->config->getUserValue($userId, Application::APP_ID, 'token');
		// $revokeResponse = $this->request($userId, 'v1/oauth/revoke?access_token=' . $token, [], 'POST', false);
		// return $revokeResponse === '';
		// TODO: Keep until appropriate API route is available
		return true;
	}
}
