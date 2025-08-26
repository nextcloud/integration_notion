<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../tests/bootstrap.php';

use OCA\Notion\AppInfo\Application;

// remain compatible with stable26
\OC_App::loadApp(Application::APP_ID);
OC_Hook::clear();
