# Changelog

All notable changes to this project will be documented in this file. This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.0.0 - 2026-11-07

### Requirements

- **PHP 8.2** or later and **WordPress 7.0** or later are now required. Tested up to WordPress 7.1.

### Changed

- The stylesheet URL uses `content_url()`, so it follows the `content_url` filter (CDNs, HTTPS rewrites).
- Composer's autoloader is loaded only when present (the plugin's own `vendor/` or `wp-content/vendor/`), avoiding a fatal error when installed as a site Composer package.
- When `matthiasmullie/minify` isn't installed, styles are written unminified instead of failing.
- The editor panel reads the meta through `useSelect`, so it follows undo/redo.
- Release ZIPs include the production `vendor/`.

### Fixed

- The stylesheet is generated on `wp_after_insert_post` instead of `save_post`; it was previously written from the previous save's styles.
- The stylesheet only loads on the single view of its post; archives no longer load the first post's styles.
- Meta is registered per post type with `register_post_meta()`, so the `bm_page_specific_css_post_types` filter works. Only post types shown in REST are included by default.
- Bundled translations in `languages/` now load.

### Development

- PHPUnit integration tests on PHP 8.2–8.5 × WordPress 7.0 and latest; `@wordpress/scripts` 37 and Node 26; WPCS linting; shared Bernskiold workflows.

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
