<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Settings;

use OCA\Notion\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;

use OCP\Security\ICrypto;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$clientId = $this->config->getAppValue(Application::APP_ID, 'client_id');
		if ($clientId !== '') {
			$clientId = $this->crypto->decrypt($clientId);
		}
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret') !== '' ? 'dummyToken' : '';
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0') === '1';

		$adminConfig = [
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
			'use_popup' => $usePopup,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
