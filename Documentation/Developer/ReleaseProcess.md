# Release Process

This document describes the release process for `dp_t3_toc`.

## Overview

We follow [Semantic Versioning 2.0.0](https://semver.org/) and use [Conventional Commits](https://www.conventionalcommits.org/) for automated changelog generation.

**Version Format:** `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes (`feat!:`, `BREAKING CHANGE:`)
- **MINOR**: New features (`feat:`)
- **PATCH**: Bug fixes (`fix:`)

## Release Types

### Patch Release (e.g., 3.0.0 → 3.0.1)

**When:** Bug fixes, documentation updates, internal refactoring

**Changelog:** Only `fix:`, `docs:`, `refactor:` commits

**Example:**
```
fix(toc): correct sorting order for nested containers
docs: update installation instructions
refactor(repository): optimize query performance
```

### Minor Release (e.g., 3.0.1 → 3.1.0)

**When:** New features (backward compatible)

**Changelog:** `feat:`, `fix:`, `docs:` commits

**Example:**
```
feat(toc): add support for custom CSS classes
feat(processor): add cacheLifetime configuration option
fix(service): handle empty header fields correctly
```

### Major Release (e.g., 3.1.0 → 4.0.0)

**When:** Breaking changes (incompatible API changes)

**Changelog:** All commits, with special `⚠ BREAKING CHANGES` section

**Example:**
```
feat(processor)!: change constructor signature

BREAKING CHANGE:
TocProcessor now requires CacheManager as third constructor parameter.
Update your Services.yaml configuration accordingly.
```

## Release Checklist

### 1. Pre-Release Preparation

- [ ] All tests pass (`composer test:unit`)
- [ ] All QA checks pass (`composer check`)
- [ ] PHPStan max level with zero errors
- [ ] Code style is clean (`composer fix`)
- [ ] All feature branches are merged to `main`
- [ ] CHANGELOG.md is up-to-date (see below)
- [ ] README.md reflects new features/changes
- [ ] Documentation is complete

### 2. Version Bump

Update version numbers in:

- [ ] `ext_emconf.php`: `'version' => 'X.Y.Z'`
- [ ] `composer.json`: `"version": "X.Y.Z"` (if present)
- [ ] CHANGELOG.md: Add `[X.Y.Z] - YYYY-MM-DD` section

**Example commit:**
```bash
git add ext_emconf.php composer.json CHANGELOG.md
git commit -m "chore(release): bump version to 3.1.0"
```

### 3. Create Git Tag

```bash
# Create annotated tag
git tag -a v3.1.0 -m "Release version 3.1.0"

# Push tag to GitHub
git push origin v3.1.0
```

### 4. Create GitHub Release

1. Go to [Releases](https://github.com/ndrstmr/dp_t3_toc/releases)
2. Click "Draft a new release"
3. Select the tag you just created (`v3.1.0`)
4. **Release title:** `v3.1.0 - Brief description`
5. **Description:** Copy relevant section from CHANGELOG.md
6. Attach `.zip` file (optional, GitHub auto-generates)
7. **Pre-release:** Check if it's a beta/alpha release
8. Click "Publish release"

**Release Notes Template:**
```markdown
## What's Changed

### Features
- Add support for custom CSS classes (#123)
- Add cacheLifetime configuration option (#124)

### Bug Fixes
- Fix sorting order for nested containers (#125)

### Documentation
- Update installation instructions
- Add maxDepth examples

**Full Changelog**: https://github.com/ndrstmr/dp_t3_toc/compare/v3.0.1...v3.1.0

## Installation

```bash
composer require ndrstmr/dp-t3-toc:^3.1
```

## Upgrade Guide

No breaking changes. Simply update via Composer.

---

Thanks to all contributors! 🎉
```

### 5. Publish to TER (TYPO3 Extension Repository)

1. **Login** to [TER](https://extensions.typo3.org/)
2. **Upload** extension package:
   ```bash
   # Create clean package
   composer install --no-dev
   zip -r dp_t3_toc_3.1.0.zip . -x '*.git*' -x '*Tests*' -x '*Build*'
   ```
3. **Upload** the `.zip` file
4. **Add release notes** (same as GitHub release)
5. **Publish** the version

### 6. Announce Release

- [ ] Tweet/post on social media (if applicable)
- [ ] Update project website (if applicable)
- [ ] Notify users in discussions
- [ ] Update documentation site

## CHANGELOG.md Format

We use [Keep a Changelog](https://keepachangelog.com/) format:

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Feature X

### Changed
- Refactored Y

### Fixed
- Bug Z

## [3.1.0] - 2025-11-15

### Added
- Support for custom CSS classes in TOC items
- New `cacheLifetime` configuration option

### Fixed
- Sorting order for nested containers

## [3.0.0] - 2025-10-30

### ⚠ BREAKING CHANGES

**Constructor Signature Changes:**
- `TocProcessor` now requires `TcaContainerCheckServiceInterface` as second parameter
- `TocBuilderService` now requires `TcaContainerCheckServiceInterface` as second parameter

**Migration Guide:**
Update your `Configuration/Services.yaml`:
...
```

## Hotfix Process

For critical bugs that need immediate release:

1. **Create hotfix branch** from latest release tag:
   ```bash
   git checkout -b hotfix/v3.0.1 v3.0.0
   ```

2. **Fix the bug** and commit:
   ```bash
   git commit -m "fix(critical): resolve security vulnerability"
   ```

3. **Update version** (PATCH bump):
   ```bash
   # Update ext_emconf.php
   git commit -m "chore(release): bump version to 3.0.1"
   ```

4. **Merge back**:
   ```bash
   git checkout main
   git merge hotfix/v3.0.1
   git push origin main
   ```

5. **Tag and release** (see steps above)

## Beta/Alpha Releases

For testing new features before stable release:

1. **Use pre-release suffix**:
   - Alpha: `3.1.0-alpha.1`
   - Beta: `3.1.0-beta.1`
   - RC: `3.1.0-rc.1`

2. **Create tag**:
   ```bash
   git tag -a v3.1.0-beta.1 -m "Beta release for testing"
   git push origin v3.1.0-beta.1
   ```

3. **Mark as pre-release** on GitHub

4. **Install with Composer**:
   ```bash
   composer require ndrstmr/dp-t3-toc:3.1.0-beta.1
   ```

## Automated Release Pipeline (Future)

We plan to implement GitHub Actions workflow for automated releases:

```yaml
# .github/workflows/release.yml
name: Release
on:
  push:
    tags:
      - 'v*'
jobs:
  release:
    - Validate tag format
    - Run all QA checks
    - Build extension package
    - Create GitHub release
    - Upload to TER (if credentials configured)
```

## Questions?

If you have questions about the release process:
- Check this document first
- Ask in [GitHub Discussions](https://github.com/ndrstmr/dp_t3_toc/discussions)
- Contact maintainer: **ndrstmr**

---

**Last updated:** 2025-10-30
