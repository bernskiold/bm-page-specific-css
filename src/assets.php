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
	 * We want to allow the use of good script debugging here too,
	 * so be mindful and use the SCRIPTS_DEBUG constant
	 * to load both minified for production and non-minified files
	 * for testing purposes.
	 **/
	public static function block_editor_scripts() {

		wp_enqueue_script( 'bm-page-specific-css', Plugin::get_assets_url( 'scripts/dist/editor.js' ), [
			'wp-editor',
			'wp-components',
			'wp-i18n',
			'wp-data',
			'wp-edit-post',
			'wp-plugins',
		], Plugin::get_version() );

	}
}
