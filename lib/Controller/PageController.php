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

use OCA\Notion\Service\NotionAPIService;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

use OCA\Notion\AppInfo\Application;

class PageController extends Controller {

	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IAppManager
	 */
	private $appManager;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var NotionAPIService
	 */
	private $notionAPIService;

	public function __construct(string           $appName,
                                IRequest         $request,
                                IConfig          $config,
                                IAppManager      $appManager,
                                IInitialState    $initialStateService,
                                LoggerInterface  $logger,
                                NotionAPIService $notionAPIService,
                                ?string          $userId) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->logger = $logger;
		$this->config = $config;
		$this->appManager = $appManager;
		$this->initialStateService = $initialStateService;
		$this->notionAPIService = $notionAPIService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
     * TODO: Rewrite initial state data to Notion
	 * @return TemplateResponse
	 * @throws \Exception
	 */
	public function index(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$clientID = $this->config->getAppValue(Application::APP_ID, 'client_id');
		// don't expose the client secret to users
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret') !== '';
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0') === '1';

		$notionUserId = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_id');
		$notionUserName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$notionTeamId = $this->config->getUserValue($this->userId, Application::APP_ID, 'team_id');
		$notionTeamName = $this->config->getUserValue($this->userId, Application::APP_ID, 'team_name');

		$talkEnabled = $this->appManager->isEnabledForUser('spreed', $this->userId);

		$pageInitialState = [
			'token' => $token ? 'dummyTokenContent' : '',
			'client_id' => $clientID,
			'client_secret' => $clientSecret,
			'use_popup' => $usePopup,
			'user_id' => $notionUserId,
			'user_name' => $notionUserName,
			'team_id' => $notionTeamId,
			'team_name' => $notionTeamName,
			'talk_enabled' => $talkEnabled,
			'board_list' => [],
		];
		if ($token !== '') {
			$pageInitialState['board_list'] = $this->notionAPIService->getMyBoards($this->userId);
		}
		$this->initialStateService->provideInitialState('notion-state', $pageInitialState);
		return new TemplateResponse(Application::APP_ID, 'main', []);
	}
}
