<?php
/**
 * Class autoloader for the plugin's own classes.
 *
 * Maps BernskioldMedia\WP\PageSpecificCSS\Generate_Files to
 * src/generate-files.php, following the WordPress file naming convention.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $classname ): void {
		$prefix = 'BernskioldMedia\\WP\\PageSpecificCSS\\';

		if ( 0 !== strpos( $classname, $prefix ) ) {
			return;
		}

		$relative = substr( $classname, strlen( $prefix ) );
		$relative = str_replace( [ '\\', '_' ], [ DIRECTORY_SEPARATOR, '-' ], strtolower( $relative ) );
		$path     = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);
