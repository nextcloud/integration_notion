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

namespace OCA\Notion\Controller;

use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;

use OCA\Notion\Service\NotionAPIService;
use OCA\Notion\AppInfo\Application;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IURLGenerator;

class NotionAPIController extends Controller {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var NotionAPIService
	 */
	private $notionAPIService;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $accessToken;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IURLGenerator $urlGenerator,
								NotionAPIService $notionAPIService,
								?string $userId) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->notionAPIService = $notionAPIService;
		$this->userId = $userId;
		$this->accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserDatabases() {
		$result = $this->notionAPIService->getUserDatabases($this->userId);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserDatabase(string $id) {
		$result = $this->notionAPIService->getUserDatabase($this->userId, $id);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserComments() {
		// TODO
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserBlocks() {
		// TODO
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserPages() {
		$result = $this->notionAPIService->getUserPages($this->userId);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getUserPage(string $id) {
		$result = $this->notionAPIService->getUserPage($this->userId, $id);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getThumbnail(string $notionObjectId, string $objectType = '') {
		$thumbnail = $this->notionAPIService->getThumbnail($this->userId, $notionObjectId, $objectType);
		if ($thumbnail !== null && isset($thumbnail['body'], $thumbnail['headers'])) {
			$response = new DataDisplayResponse(
				$thumbnail['body'],
				Http::STATUS_OK,
				['Content-Type' => $thumbnail['headers']['Content-Type'][0] ?? 'image/jpeg']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', 400);
	}
}
