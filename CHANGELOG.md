<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Changed

- Load the app config values lazily, so they are no longer loaded on every request
- Only accept the app's own settings on the admin settings endpoints

### Removed

- Nextcloud 32 and PHP 8.1 support

## 2.1.0 - 2026-09-29

### Added

- Added support for Nextcloud 36

### Changed

- Update nextcloud/coding-standard and @nextcloud/eslint-config, and sync the lint workflows
- Update dependencies & translations

## 2.0.3 - 2026-07-29

### Added

- Added support for Nextcloud 35

### Changed

- Update the Nextcloud frontend libraries from release candidates to stable releases
- Update dependencies & translations

## 2.0.2 - 2026-04-20

### Added

- Added support for Nextcloud 34

### Changed

- Update dependencies & translations

## 2.0.1 - 2025-11-10

### Added

- Added support for Nextcloud 33

### Changed

- Update dependencies & translations

## 2.0.0 - 2025-09-01

### Removed

- Nextcloud 30 and 31 support

## 1.3.0 - 2025-08-26

### Added

- Added support for Nextcloud 32
- Mark the search providers as external sources

### Changed

- Migrate to Vue 3
- Change the icons to outlined versions
- Update dependencies & translations

### Removed

- Nextcloud 28 and 29 support

## 1.2.0 - 2024-10-23

### Changed

- Update dependencies

### Fixed

- Correct the sensitive config storage handling

## 1.1.0 - 2024-09-25

### Added

- Added support for Nextcloud 31

### Changed

- Update dependencies

### Removed

- Nextcloud 26 and 27 support

### Fixed

- Correct the admin sensitive config handling

## 1.0.1 - 2024-05-03

### Changed

- Update dependencies

## 1.0.0 - 2024-04-04

### Added

- Added support for Nextcloud 29 and 30

### Changed

- Update dependencies

## 0.1.4 - 2023-10-25

### Added

- Added support for Nextcloud 28

### Changed

- Update dependencies & translations

## 0.1.3 - 2023-06-03

### Fixed

- Fix the Notion thumbnail retrieval

## 0.1.2 - 2023-05-05

### Changed

- Update translations

### Fixed

- Fix the popup authentication

## 0.1.1 - 2023-04-22

### Changed

- Update the build and packaging setup
- Update dependencies

## 0.1.0 - 2023-04-06

### Added

- Initial release for Nextcloud 26 and 27
