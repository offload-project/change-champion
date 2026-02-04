# Changelog

All notable changes to this project will be documented in this file.

## v1.5.0 - 2026-02-04

### Features

- Add `draftRelease` config option to create GitHub releases as drafts for manual marketplace publishing.

## v1.4.0 - 2026-02-04

### Features

- Update default PHP version to 8.2 in GitHub Action and workflow templates

## v1.3.1 - 2026-02-04

### Fixes

- Add configurable php-version input to GitHub Action (default: 8.2)

## v1.3.0 - 2026-02-04

### Features

- Update config defaults: releaseBranchPrefix to 'release/' and versionPrefix to 'v'. Rename workflow files to change-champ-*.yml prefix.

## v1.2.0 - 2026-02-04

### Features

- Add GitHub Action for easy CI integration via `uses: offload-project/change-champion@v1`.

## v1.1.1 - 2026-02-04

### Fixes

- Fix missing blank line between version entries in CHANGELOG.

## v1.1.0 - 2026-02-04

### Features

- Add `champ generate` command to create changesets from conventional commits (hybrid mode like release-please).
- Add custom version prefix for changelog headers via `versionPrefix` config option (e.g., `"v"` for `v1.0.0` format).

## v1.0.0 - 2026-02-04

### Breaking Changes

- Rename CLI command from `cc` to `champ`.

## v0.7.0 - 2026-02-04

### Features

- Add configurable release branch prefix

## v0.6.0 - 2026-02-04

### Features

- Add configurable changelog section headers via `sections` config option.

## v0.5.0 - 2026-02-04

### Features

- Add automatic issue linking in changelogs.

## v0.4.0 - 2026-02-04

### Features

- Add `champ preview` command to show CHANGELOG output before running version.

## v0.3.0 - 2026-02-04

### Features

-
    - New `champ check` command validates changeset format for CI

## v0.2.0 - 2026-02-04

### Features

- Add pre-release version support with `--prerelease` flag for alpha, beta, and rc versions.

## v0.1.0 - 2026-02-04

### Features

- Initial release of Change Champion - a changesets-inspired tool for PHP/Composer packages.
