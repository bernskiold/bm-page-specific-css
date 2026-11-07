# Testing

PHPUnit integration tests run against a real WordPress, so they cover saving
through the REST API the way the block editor does, writing and removing the
stylesheets, and loading them on the front end.

They run in CI (`.github/workflows/tests.yml`) on every pull request, for PHP
8.1–8.4 against WordPress 6.6 (the minimum) and the latest release.

## Running locally

Requires Docker for [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

```bash
npm install
composer install
npm run env:start   # boot WordPress in Docker (the first run pulls images)
npm run test:php    # run PHPUnit inside the tests-cli container
npm run env:stop
```

Pick other versions with environment variables when starting wp-env:

```bash
WP_ENV_PHP_VERSION=8.4 WP_ENV_CORE=WordPress/WordPress#6.6 npm run env:start
```

Tests live in `tests/php/`. They extend `Tests\TestCase`, which registers the
plugin's meta again before each test because `WP_UnitTestCase` unregisters all
meta keys after every test.

Without Docker, point `WP_TESTS_DIR` at the `tests/phpunit` folder of a
[wordpress-develop](https://github.com/WordPress/wordpress-develop) checkout
with a `wp-tests-config.php`, and run `composer test`.
