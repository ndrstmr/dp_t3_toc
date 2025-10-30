# Roadmap

This document outlines the planned features and improvements for `dp_t3_toc`.

## Current Status

**Latest Stable Version:** v3.0.0 (October 2025)

**Focus Areas:**
- PHPStan max level compliance
- Comprehensive test coverage (115 tests)
- TYPO3 v13 LTS support
- Container extension integration

## Version 3.x (Current Stable - TYPO3 v13 LTS)

### v3.0.0 - Architecture & Type Safety ✅

**Released:** October 2025

**Highlights:**
- Complete refactoring with PHPStan max level compliance
- Comprehensive unit test coverage (115 tests, 157 assertions)
- Interface-based dependency injection
- TypeCastingTrait for safe type conversions
- EXT:container support via TcaContainerCheckService

**Breaking Changes:**
- Constructor signature changes (Services.yaml updates required)
- Stricter type handling

**Status:** ✅ Released

### v3.1.0 - Community & Documentation

**Target:** November 2025

**Planned Features:**
- ✅ Contributing guidelines (CONTRIBUTING.md)
- ✅ Issue templates (bug report, feature request)
- ✅ Pull request template
- ✅ Security policy (SECURITY.md)
- ✅ Release process documentation
- ✅ Roadmap documentation
- Enhanced README with more examples
- Video tutorials (if resources available)

**Status:** 🚧 In Progress

### v3.2.0 - Performance & Caching

**Target:** November/December 2025

**Planned Features:**
- Cache management for TOC results
- Performance optimization for large page trees
- Lazy loading for nested containers
- Database query optimization
- Benchmark suite for performance testing

**Status:** 📋 Planned

### v3.3.0 - Frontend Enhancements

**Target:** Q1 2026 (January-March)

**Planned Features:**
- JavaScript smooth scrolling integration
- Active section highlighting
- Sticky TOC component option
- Mobile-friendly responsive templates
- Collapse/expand functionality
- Intersection Observer API integration

**Status:** 📋 Planned

## Version 4.x (TYPO3 v14 LTS - 2026)

### v4.0.0 - TYPO3 v14 LTS Support

**Target:** Q2 2026 (April-June, aligned with TYPO3 v14 LTS release)

**Planned Features:**
- TYPO3 v14 LTS compatibility
- PHP 8.4+ support (pending TYPO3 v14 requirements)
- Rector rules for automated migration from v3.x
- Deprecation of legacy features
- Modern Fluid template improvements
- Updated dependency injection patterns
- Compatibility with TYPO3 v14 core changes

**Breaking Changes:**
- Drop TYPO3 v13 support
- Drop PHP 8.3 support (if TYPO3 v14 requires PHP 8.4+)
- Potential API changes for v14 compatibility
- Constructor signatures may change based on TYPO3 v14 best practices

**Migration Path:**
- v3.x will continue to receive security updates until TYPO3 v13 EOL (2027)
- Migration guide will be provided
- Rector rules for automated code updates

**Status:** 📋 Planned (Development starts Q1 2026)

## Feature Backlog

These features are under consideration but not yet scheduled:

### High Priority

- **Multilingual TOC Support:** Better handling of translated content elements
- **Custom Anchors:** Allow users to define custom anchor IDs
- **Accessibility Improvements:** ARIA labels, keyboard navigation, screen reader support
- **TOC Styling Presets:** Ready-to-use CSS themes (minimal, modern, classic)
- **Backend Preview:** Visual TOC preview in page module

### Medium Priority

- **Export Formats:** Generate PDF bookmarks, EPUB TOC
- **Analytics Integration:** Track TOC link clicks via data attributes
- **Content Element Groups:** Support for gridelements/DCE
- **Exclude Patterns:** Regex-based header exclusion
- **TOC Template Variants:** Sidebar, dropdown, tree view, accordion

### Low Priority / Nice-to-Have

- **REST API:** Expose TOC data via JSON API
- **GraphQL Support:** TOC queries for headless TYPO3
- **AI-Generated Summaries:** Auto-generate section summaries (experimental)
- **Cross-Page TOC:** Generate TOC across multiple pages (sitemap-style)
- **Live Preview:** Real-time TOC updates in backend

## Community Requests

Feature requests from the community will be tracked here once submitted:

| Request | Status | Priority | Votes | Target Version |
|---------|--------|----------|-------|----------------|
| *No requests yet* | - | - | - | - |

**Want to suggest a feature?** [Open a feature request](https://github.com/ndrstmr/dp_t3_toc/issues/new/choose)!

## Deprecation Notices

### v3.x (Current)

- No deprecations currently

### v4.0 (Q2 2026)

The following will be deprecated/removed in v4.0:

- **TYPO3 v13 support** will be dropped
- **PHP 8.3 support** may be dropped (pending TYPO3 v14 requirements)
- Legacy configuration options (if any are identified)
- Deprecated TYPO3 core APIs that v14 removes

**Migration Timeline:**
- v3.x receives security updates until TYPO3 v13 EOL (estimated 2027)
- Deprecation warnings will be added in v3.3.0 (Q1 2026)
- Full migration guide will be published with v4.0.0 release

## Long-Term Vision

Our long-term goals for `dp_t3_toc`:

1. **Best-in-class TOC solution** for TYPO3 with zero PHPStan errors
2. **100% test coverage** for all critical functionality
3. **Active community** with regular contributions and feedback
4. **Excellent documentation** with examples, videos, and best practices
5. **Performance leader** with efficient caching and query optimization
6. **Accessibility champion** meeting WCAG 2.1 AAA standards
7. **Future-proof architecture** ready for TYPO3 v15+ and beyond

## How to Influence the Roadmap

We welcome community input! Here's how you can help shape the future:

1. **Vote on features:** React to issues with 👍 emoji
2. **Submit feature requests:** [Create a feature request](https://github.com/ndrstmr/dp_t3_toc/issues/new/choose)
3. **Contribute code:** [Submit a pull request](https://github.com/ndrstmr/dp_t3_toc/pulls)
4. **Sponsor development:** Contact maintainer for sponsorship opportunities
5. **Share use cases:** Tell us how you use the extension in [Discussions](https://github.com/ndrstmr/dp_t3_toc/discussions)
6. **Test pre-releases:** Help test beta/RC versions before stable release

## Release Cadence

We aim for the following release schedule:

- **Patch releases:** As needed (bug fixes, security)
- **Minor releases:** Every 1-2 months during active development
- **Major releases:** Aligned with TYPO3 LTS releases (~18 months)

**2025-2026 Schedule:**
- Oct 2025: v3.0.0 ✅
- Nov 2025: v3.1.0 🚧
- Nov/Dec 2025: v3.2.0 📋
- Q1 2026: v3.3.0 📋
- Q2 2026: v4.0.0 📋

## Development Priorities

Our current development priorities (in order):

1. **Stability:** No regressions, all tests pass
2. **Security:** Prompt response to vulnerabilities
3. **Documentation:** Clear guides and examples
4. **Community:** Responsive to issues and PRs
5. **Features:** New functionality based on demand
6. **Performance:** Optimization as usage scales
7. **TYPO3 v14 Preparation:** Early testing and compatibility work

## Version Support Policy

| Version | Status | Support Until | TYPO3 Version | PHP Version |
|---------|--------|---------------|---------------|-------------|
| 3.x     | Active | 2027 (TYPO3 v13 EOL) | 13.x | 8.3+ |
| 4.x     | Planned (Q2 2026) | TBD | 14.x | 8.4+ (TBD) |
| 2.x     | EOL    | Oct 2025 | 13.x | 8.2+ |
| 1.x     | EOL    | Oct 2025 | 12.x | 8.1+ |

**Support Policy:**
- **Active:** New features, bug fixes, security updates
- **Security-only:** Critical security fixes only
- **EOL:** No updates, please upgrade

## TYPO3 Version Alignment

We closely follow the [TYPO3 Release Roadmap](https://typo3.org/cms/roadmap):

- **TYPO3 v13 LTS:** Released October 2024, supported until 2027
- **TYPO3 v14.0:** November 2025
- **TYPO3 v14 LTS:** Q2 2026 (April-June)

Our extension versions align with TYPO3 major versions:
- `dp_t3_toc` v3.x = TYPO3 v13.x
- `dp_t3_toc` v4.x = TYPO3 v14.x

## Questions?

Have questions about the roadmap?
- [Open a discussion](https://github.com/ndrstmr/dp_t3_toc/discussions)
- Check [CONTRIBUTING.md](CONTRIBUTING.md) for how to contribute
- Review [RELEASE_PROCESS.md](docs/RELEASE_PROCESS.md) for release details
- Contact maintainer: **ndrstmr**

---

**Legend:**
- ✅ Released
- 🚧 In Progress
- 📋 Planned
- 💭 Exploration Phase
- ❌ Cancelled

**Last updated:** 2025-10-30
