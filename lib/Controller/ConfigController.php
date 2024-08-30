<?php

namespace OCA\Notion\Controller;

use OCA\Notion\AppInfo\Application;
use OCA\Notion\Service\NotionAPIService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IRequest;
use OCP\IURLGenerator;

// TODO: Rewrite configuration
class ConfigController extends Controller {

	public function __construct(string $appName,
		IRequest $request,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IL10N $l,
		private IInitialState $initialStateService,
		private NotionAPIService $notionAPIService,
		private ?string $userId) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function isUserConnected(): DataResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');

		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret');
		$oauthPossible = $clientID !== '' && $clientSecret !== '';
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0');

		return new DataResponse([
			'connected' => $token !== '',
			'oauth_possible' => $oauthPossible,
			'use_popup' => ($usePopup === '1'),
			'client_id' => $clientID,
		]);
	}

	#[NoAdminRequired]
	public function setConfig(array $values): DataResponse {
		// revoke the token
		if (isset($values['token']) && $values['token'] === '') {
			$this->notionAPIService->revokeToken($this->userId);
		}

		foreach ($values as $key => $value) {
			$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
		}
		$result = [];

		if (isset($values['token'])) {
			$result['user_id'] = '';
			$result['user_name'] = '';
			if ($values['token'] && $values['token'] !== '') {
				if (isset($values['user_id']) && isset($values['user_name'])) {
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
			//			// if the token is set, cleanup refresh token and expiration date
			//			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'refresh_token');
			//			$this->config->deleteUserValue($this->userId, Application::APP_ID, 'token_expires_at');
		}
		return new DataResponse($result);
	}

	#[PasswordConfirmationRequired]
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
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
		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret');

		// anyway, reset state
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'oauth_state');

		if ($clientID && $clientSecret && $configState !== '' && $configState === $state) {
			$redirect_uri = $this->config->getUserValue($this->userId, Application::APP_ID, 'redirect_uri');
			$result = $this->notionAPIService->requestOAuthAccessToken([
				'client_id' => $clientID,
				'client_secret' => $clientSecret,
				'code' => $code,
				'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code'
			]);
			if (isset($result['access_token'])) {
				$accessToken = $result['access_token'];
				$user_id = $result['owner']['user']['id'] ?? '';
				$user_name = $result['owner']['user']['name'] ?? '';
				$this->config->setUserValue($this->userId, Application::APP_ID, 'token', $accessToken);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'token_type', $result['token_type']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_id', $user_id);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'user_name', $user_name);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'bot_id', $result['bot_id']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'workspace_name', $result['workspace_name']);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'workspace_id', $result['workspace_id']);

				$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0') === '1';
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
							$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts']) .
							'?notionToken=success'
						);
					} elseif ($oauthOrigin === 'app') {
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
			$this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'connected-accounts']) .
			'?notionToken=error&message=' . urlencode($result)
		);
	}
}
