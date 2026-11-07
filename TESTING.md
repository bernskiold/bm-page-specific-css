# Testing

PHPUnit integration tests run against a real WordPress, so they cover saving
through the REST API the way the block editor does, writing and removing the
stylesheets, and loading them on the front end.

They run in CI (`.github/workflows/tests.yml`) on every pull request, for PHP
8.2–8.5 against WordPress 7.0 (the minimum) and the latest release.

## Running locally

Requires a MySQL server. Docker is the quickest way to get one:

```bash
docker run -d --name wptests -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=wordpress_tests -p 3306:3306 mysql:8.0
```

Then install WordPress and its test library, and run the tests:

```bash
composer install
eval "$(bin/install-wp-tests.sh latest wordpress_tests root root 127.0.0.1 | sed 's/^/export /')"
composer test
```

Pass a version such as `7.0` instead of `latest` to test the minimum.
`bin/install-wp-tests.sh` prints the `WP_PHPUNIT__DIR` and
`WP_PHPUNIT__TESTS_CONFIG` variables PHPUnit needs.

### With wp-env

```bash
npm install
composer install
npm run env:start   # boot WordPress in Docker (the first run pulls images)
npm run test:php    # run PHPUnit inside the tests-cli container
npm run env:stop
```

Pick other versions with environment variables when starting wp-env:

```bash
WP_ENV_PHP_VERSION=8.4 WP_ENV_CORE=WordPress/WordPress#7.0 npm run env:start
```

Tests live in `tests/php/`. They extend `Tests\TestCase`, which registers the
plugin's meta again before each test because `WP_UnitTestCase` unregisters all
meta keys after every test.
