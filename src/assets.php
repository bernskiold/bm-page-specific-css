<?php
/**
 * Handles the loading of scripts and styles for the
 * theme through the proper enqueuing methods.
 *
 * @author  Bernskiold Media <info@bernskioldmedia.com>
 * @package BernskioldMedia\WP\PageSpecificCSS
 * @since   1.0.0
 **/

namespace BernskioldMedia\WP\PageSpecificCSS;

defined( 'ABSPATH' ) || exit;

/**
 * Assets Class
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */
class Assets {

	/**
	 * Assets Constructor
	 */
	public static function hooks(): void {

		add_action( 'wp_enqueue_scripts', [ self::class, 'public_styles' ], 999 );

		// Scripts.
		add_action( 'enqueue_block_editor_assets', [ self::class, 'block_editor_scripts' ] );

	}

	/**
	 * Enqueue Styles on public side
	 **/
	public static function public_styles() {

		global $post;

		if ( ! $post ) {
			return;
		}

		if ( 'true' === get_post_meta( $post->ID, 'has_page_specific_css', true ) ) {
			wp_enqueue_style( 'page-' . $post->ID, Generate_Files::get_file_url( $post->ID ), [], Generate_Files::get_file_version( $post->ID ), 'all' );
		}

	}

	/**
	 * Enqueue Scripts on admin side
	 *
	 * The build writes a .asset.php next to the bundle holding the exact
	 * script handles it was compiled against and a content hash, so we
	 * read the dependencies from there instead of listing them by hand.
	 **/
	public static function block_editor_scripts() {

		$asset_file = Plugin::get_path( 'assets/scripts/dist/editor.asset.php' );

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'bm-page-specific-css',
			Plugin::get_assets_url( 'scripts/dist/editor.js' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'bm-page-specific-css', Plugin::TEXTDOMAIN, Plugin::get_path( 'languages' ) );

	}
}
