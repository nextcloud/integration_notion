<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Notion\Settings;

use OCA\Notion\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;

use OCP\Settings\ISettings;

class Personal implements ISettings {

	public function __construct(private IConfig $config,
		private IInitialState $initialStateService,
		private ?string $userId) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$notionUserId = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_id');
		$notionUserName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$searchPagesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_pages_enabled', '0');
		$searchDatabasesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_databases_enabled', '0');
		$linkPreviewEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'link_preview_enabled', '0');

		// for OAuth
		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		// don't expose the client secret to users
		$clientSecret = ($this->config->getAppValue(Application::APP_ID, 'client_secret') !== '');
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0') === '1';

		$userConfig = [
			'token' => $token ? 'dummyTokenContent' : '',
			'client_id' => $clientID,
			'client_secret' => $clientSecret,
			'use_popup' => $usePopup,
			'user_id' => $notionUserId,
			'user_name' => $notionUserName,
			'search_pages_enabled' => $searchPagesEnabled === '1',
			'search_databases_enabled' => $searchDatabasesEnabled === '1',
			'link_preview_enabled' => $linkPreviewEnabled === '1',
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
