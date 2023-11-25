<?php

require_once __DIR__ . '/../../../tests/bootstrap.php';

use OCA\Notion\AppInfo\Application;

// remain compatible with stable26
\OC_App::loadApp(Application::APP_ID);
OC_Hook::clear();
