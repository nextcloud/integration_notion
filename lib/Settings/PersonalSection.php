<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Notion\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {

	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getID(): string {
		return 'connected-accounts';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l->t('Connected accounts');
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority(): int {
		return 80;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('core', 'categories/integration.svg');
	}

}
