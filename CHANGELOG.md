# Changelog

## 2026-11-08

- chore(deps): require foreline/clean v3.4.0

## [0.4.5] - 2026-11-04

### Fixed
- Restore the value objects, link services and network collection fixes from 0.4.4 while preserving newer configuration schema changes and GitHub dependency sources.

## [0.4.4] - 2026-10-17

### Fixed
- Remove conflicting item storage declarations from EmailCollection, MacAddressCollection, URICollection and DomainCollection; initialization belongs to CollectionTrait.
- Clear collections to an empty array, reset the cursor on replacement, validate replacements before changing state and support self-assignment and self-append.

## 2026-10-17

- chore(deps): require clean 3.3.1 without Webmozart

## [0.4.2] - 2026-10-17

### Changed
- Require foreline/clean 3.3 with boolean and semantic active/sort value objects.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.4.1] - 2026-10-16

### Changed
- Link editor defaults hide scheme, IP and port while retaining them in the serialized value for metadata autofill and persistence. Applications can override individual parts; description remains a visible textarea by default.

## [0.4.0] - 2026-10-16

### Added
- Extensible Link value object and generated LinkCollection, with value equality and no entity identity.
- Shared URI building/parsing services and framework-neutral page metadata, public-resource fetching and icon decoding services.
- ValueObjectData codec for structured values and ordered collections at JSON and relational column boundaries.
- Shipped Link editor configuration; generic ownership and cardinality remain PHP type semantics.

### Changed
- Require foreline/clean >=2.11 for length-aware text values; declare URI-resolution and IDN dependencies.

## [0.3.0] - 2026-10-15

### Changed
- Refresh the dependency lock to foreline/clean v3.2.0 with empty defaults for the text VO family.

### Fixed
- Implement the required integer conversion on NetworkIdentifier\Port so the class can load.

### Added
- Empty constructor defaults for Url, Token, CronExpression and FilePath; Money defaults to zero in the existing default currency (RUB).

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
