<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Controller;

use OCA\Notion\Service\NotionAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

class NotionAPIController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private NotionAPIService $notionAPIService,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserDatabases() {
		$result = $this->notionAPIService->getUserDatabases($this->userId);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserDatabase(string $id) {
		$result = $this->notionAPIService->getUserDatabase($this->userId, $id);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserComments() {
		// TODO
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserBlocks() {
		// TODO
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserPages() {
		$result = $this->notionAPIService->getUserPages($this->userId);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getUserPage(string $id) {
		$result = $this->notionAPIService->getUserPage($this->userId, $id);
		return new Http\JSONResponse($result, Http::STATUS_OK);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
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
