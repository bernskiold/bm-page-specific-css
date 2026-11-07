<?php
/**
 * Base test case.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

namespace BernskioldMedia\WP\PageSpecificCSS\Tests;

use BernskioldMedia\WP\PageSpecificCSS\Plugin;
use WP_UnitTestCase;

/**
 * WP_UnitTestCase unregisters every meta key after each test, so the plugin's
 * meta, which it registers once on init, is registered again before each test.
 */
abstract class TestCase extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		Plugin::register_meta();
	}
}
