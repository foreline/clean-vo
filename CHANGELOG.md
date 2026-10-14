# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-10-14

### Added
- `CronExpression` value object (plain `StringValueObjectInterface`) validating cron expressions via `dragonmantank/cron-expression`; empty value means "not set" (e.g. keep the default schedule)

## [0.1.1] - 2026-10-07

### Fixed
- `Url` accepts scheme-less addresses, normalizing them with an `https://` prefix (matches existing consumer behavior and legacy stored values like `portal.bitrix24.ru`)

## [0.1.0] - 2026-10-07

### Added
- `Url` value object with the `UrlValueObjectInterface` marker (CRUD generator maps it to ui.type `url`)
- `Token` value object with the `TokenValueObjectInterface` marker (CRUD generator maps it to ui.type `password`)

## [0.0.4] - 2026-09-10

### Changed
- Allow foreline/clean 3.x with the explicit SourceHolderInterface getter contract.

## [0.0.3] - 2026-04-25

### Fixed
- Install openssh-client in Docker images for SSH support in CI pipeline

### Changed
- Clean up CSS class naming in value object configurations (removed 'js-' prefix)
- Make Money amount field optional by removing 'required' attribute

## [0.0.1] - 2026-04-25

### Added
- Initial release
