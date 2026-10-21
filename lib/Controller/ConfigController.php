<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Controller;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Service\NotionAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;

class ConfigController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $appConfig,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IL10N $l,
		private IInitialState $initialStateService,
		private ICrypto $crypto,
		private NotionAPIService $notionAPIService,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function isUserConnected(): DataResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');

		$clientId = $this->appConfig->getAppValueString('client_id', lazy: true);
		if ($clientId !== '') {
			$clientId = $this->crypto->decrypt($clientId);
		}
		$clientSecret = $this->appConfig->getAppValueString('client_secret', lazy: true);
		if ($clientSecret !== '') {
			$clientSecret = $this->crypto->decrypt($clientSecret);
		}
		$oauthPossible = $clientId !== '' && $clientSecret !== '';
		$usePopup = $this->appConfig->getAppValueString('use_popup', '0', lazy: true);

		return new DataResponse([
			'connected' => $token !== '',
			'oauth_possible' => $oauthPossible,
			'use_popup' => ($usePopup === '1'),
			'client_id' => $clientId,
		]);
	}

	#[NoAdminRequired]
	public function setConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if ($key === 'token' && $value !== '') {
				$value = $this->crypto->encrypt($value);
			}
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		$result = [];

		if (isset($values['token'])) {
			$result['user_id'] = '';
			$result['user_name'] = '';
			if ($values['token'] && $values['token'] !== '') {
				if (isset($values['user_id'], $values['user_name'])) {
					$result['user_id'] = $values['user_id'];
					$result['user_name'] = $values['user_name'];
				}
			} else {
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token_type');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_id');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'user_name');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'bot_id');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'workspace_name');
				$this->config->deleteUserValue($this->userId, Application::APP_ID, 'workspace_id');
			}
		}
		return new DataResponse($result);
	}

	public function setAdminConfig(array $values): DataResponse {
		if (!$this->hasOnlyStringValuesFor($values, ['use_popup', 'link_preview_enabled'])) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			$this->appConfig->setAppValueString($key, $value, lazy: true);
		}
		return new DataResponse(1);
	}

	#[PasswordConfirmationRequired]
	public function setSensitiveAdminConfig(array $values): DataResponse {
		if (!$this->hasOnlyStringValuesFor($values, ['client_id', 'client_secret'])) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		foreach ($values as $key => $value) {
			if (in_array($key, ['client_id', 'client_secret']) && $value !== '') {
				$value = $this->crypto->encrypt($value);
			}
			$this->appConfig->setAppValueString($key, $value, lazy: true);
		}
		return new DataResponse('');
	}

	/**
	 * The admin endpoints only write the app's own keys: a lazy write of a key the server
	 * owns, like "enabled", would hide it from the server, which reads it non-lazily.
	 *
	 * @param array<array-key, mixed> $values
	 * @param list<string> $allowedKeys
	 */
	private function hasOnlyStringValuesFor(array $values, array $allowedKeys): bool {
		foreach ($values as $key => $value) {
			if (!in_array($key, $allowedKeys, true) || !is_string($value)) {
				return false;
			}
		}
		return true;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function popupSuccessPage(string $user_name, string $user_id): TemplateResponse {
		$this->initialStateService->provideInitialState('popup-data', ['user_name' => $user_name, 'user_id' => $user_id]);
		return new TemplateResponse(Application::APP_ID, 'popupSuccess', [], TemplateResponse::RENDER_AS_GUEST);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function oauthRedirect(string $code = '', string $state = ''): RedirectResponse {
		$configState = $this->config->getUserValue($this->userId, Application::APP_ID, 'oauth_state');
		$clientId = $this->appConfig->getAppValueString('client_id', lazy: true);
		if ($clientId !== '') {
			$clientId = $this->crypto->decrypt($clientId);
		}
		$clientSecret = $this->appConfig->getAppValueString('client_secret', lazy: true);
		if ($clientSecret !== '') {
			$clientSecret = $this->crypto->decrypt($clientSecret);
		}

		// anyway, reset state
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'oauth_state');

		if ($clientId && $clientSecret && $configState !== '' && $configState === $state) {
			$redirect_uri = $this->config->getUserValue($this->userId, Application::APP_ID, 'redirect_uri');
			$result = $this->notionAPIService->requestOAuthAccessToken([
				'client_id' => $clientId,
				'client_secret' => $clientSecret,
				'code' => $code,
				'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code'
			]);
			if (isset($result['access_token'])) {
				$accessToken = $result['access_token'] === '' ? '' : $this->crypto->encrypt($result['access_token']);
				$user_id = $result['owner']['user']['id'] ?? '';
				$user_name = $result['owner']['user']['name'] ?? '';
				$this->config->setUserValue($this->userId, Application::APP_ID, 'token', $accessToken);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'token_type', $result['token_type']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', $user_id);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $user_name);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'bot_id', $result['bot_id']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'workspace_name', $result['workspace_name']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'workspace_id', $result['workspace_id']);

				$usePopup = $this->appConfig->getAppValueString('use_popup', '0', lazy: true) === '1';
				if ($usePopup) {
					return new RedirectResponse(
						$this->urlGenerator->linkToRoute('integration_notion.config.popupSuccessPage', [
							'user_name' => $user_name,
							'user_id' => $user_id,
						])
					);
				} else {
					$oauthOrigin = $this->config->getUserValue($this->userId, Application::APP_ID, 'oauth_origin');
					$this->config->deleteUserValue($this->userId, Application::APP_ID, 'oauth_origin');
					if ($oauthOrigin === 'settings') {
						return new RedirectResponse(
							$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts'])
							. '?notionToken=success'
						);
					}

					if ($oauthOrigin === 'app') {
						return new RedirectResponse(
							$this->urlGenerator->linkToRoute(Application::APP_ID . '.page.index')
						);
					}
				}
			}
			$result = $this->l->t('Error getting OAuth access token. ' . $result['error']);
		} else {
			$result = $this->l->t('Error during OAuth exchanges');
		}
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts'])
			. '?notionToken=error&message=' . urlencode($result)
		);
	}
}
