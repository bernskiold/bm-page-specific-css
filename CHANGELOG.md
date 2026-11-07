# Changelog

All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PHPUnit integration tests on `wp-env`, run in CI for PHP 8.1–8.4 against WordPress 6.6 and the latest release. See `TESTING.md`.

### Changed
- Requires PHP 8.1 (was 7.2).
- `@wordpress/scripts` 34 → 37, Node 24 (`.nvmrc`). The `@wordpress/*` packages the editor script imports are now declared in `package.json`.
- `composer/installers` `^1.0 || ^2.0`.
- Composer's autoloader is loaded only when it exists: from the plugin's own `vendor/` or from `wp-content/vendor/`. The plugin used to `require 'vendor/autoload.php'` unconditionally, which is a fatal error when the plugin is installed as a Composer package of the site and has no `vendor/` of its own. The plugin's own classes always load through `autoloader.php`.
- When `matthiasmullie/minify` is not installed, the styles are written unminified instead of the save failing with a fatal error.
- The editor panel reads the meta through `useSelect`, so it follows undo/redo and other changes to the meta.
- Releases follow the same workflow as bm-block-library: publishing a GitHub release bumps the version, rebuilds, commits to `master`, moves the tag and attaches a plugin ZIP (which also carries the production `vendor/`). The changelog is updated from the release notes.
- PHP is linted with WordPress Coding Standards (`.phpcs.xml.dist`), and the build workflow lints PHP and JavaScript.

### Fixed
- The stylesheet is generated on `wp_after_insert_post` instead of `save_post`. When the block editor saves, the REST API fires `save_post` before it writes the meta, so the file was written from the previous save's styles.
- The stylesheet is only loaded on the single view of its post. It used to check the global `$post`, so archives and the posts page loaded the styles of whichever post came first in the loop.
- The meta is registered per post type with `register_post_meta()`. `register_meta()` was called with the post type as the object type, so only the call for `post` took effect: it registered the key for every post type, and the `bm_page_specific_css_post_types` filter could not narrow anything. Only post types shown in REST are included by default.
- Bundled translations in `languages/` load: `load_plugin_textdomain()` was given an absolute path where it expects one relative to the plugins directory.

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

## [1.1.1] - 2023-03-24

### Fixed
- Fixed issue where a red notices was displayed on custom post types that this plugin was not working properly.

## [1.1.0] - 2022-10-02

### Changed

- Added support for all post types.

## [1.0.1] - 2020-07-04

### Changed
- Only load page-specific CSS on pages.
