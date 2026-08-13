# Changelog

All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-08-13

### Changed
- Build with `@wordpress/scripts` instead of Laravel Mix. Laravel Mix 5 runs webpack 4, which hashes with MD4 and therefore cannot build on Node 17 or later (`error:0308010C:digital envelope routines::unsupported`).
- The compiled bundle is committed and ships in the release archive. It was gitignored, so `assets/scripts/dist/editor.js` was missing from every Composer install and each consuming site had to build the plugin itself.
- The editor script is registered from the generated `editor.asset.php`, so its dependencies and cache-busting version follow the build instead of a hand-written list.
- Editor sources import from the `@wordpress/*` packages rather than reading the `wp` global.

### Fixed
- The editor script no longer loads without `react` and `react-jsx-runtime`, which the hand-written dependency list had never included.

### Added
- Translations for the editor script are loaded with `wp_set_script_translations()`.
- A build workflow compiles the plugin on pull requests, so a dependency bump that breaks the build is caught before release.

## [1.1.4] - 2026-05-21
### Fixed
- Avoid PHP 8.2 warning by returning early when `global $post` is not set on `wp_enqueue_scripts`.

## [1.1.3] - 2026-05-21
### Fixed
- Fixed release archive


## [Unreleased]

## [1.1.1] - 2023-03-24

### Fixed
- Fixed issue where a red notices was displayed on custom post types that this plugin was not working properly.

## [1.1.0] - 2022-10-02

### Changed

- Added support for all post types.

## [1.0.1] - 2020-07-04

### Changed
- Only load page-specific CSS on pages.
