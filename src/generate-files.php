<?php
/**
 * Writes each post's styles to a minified stylesheet in wp-content.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

namespace BernskioldMedia\WP\PageSpecificCSS;

use MatthiasMullie\Minify\CSS;

defined( 'ABSPATH' ) || exit;

/**
 * Class Generate_Files
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */
class Generate_Files {

	/**
	 * WordPress Hooks
	 */
	public static function hooks(): void {
		// Not save_post: when the block editor saves, the REST API updates the
		// post (firing save_post) before it writes the meta, so save_post
		// sees the styles from the previous save. wp_after_insert_post fires
		// once the meta is written.
		add_action( 'wp_after_insert_post', [ self::class, 'on_post_save' ] );
		add_action( 'delete_post', [ self::class, 'on_post_delete' ] );
	}

	/**
	 * Create/delete the file when post is saved.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function on_post_save( $post_id ): void {

		// Revisions and autosaves have no stylesheet of their own.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$styles = get_post_meta( $post_id, Plugin::CSS_META_KEY, true );

		/**
		 * If empty or not exists, we try and remove any existing files.
		 */
		if ( ! is_string( $styles ) || '' === trim( $styles ) ) {

			update_post_meta( $post_id, Plugin::HAS_CSS_META_KEY, 'false' );
			self::remove_file( $post_id );

			return;
		}

		self::maybe_create_directory();

		if ( ! self::create_file( $post_id, self::minify( $styles ) ) ) {
			return;
		}

		update_post_meta( $post_id, Plugin::HAS_CSS_META_KEY, 'true' );
	}

	/**
	 * Handle post deletion.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function on_post_delete( $post_id ): void {
		self::remove_file( $post_id );
	}

	/**
	 * Maybe remove the file.
	 *
	 * @param int $post_id Post ID.
	 */
	protected static function remove_file( $post_id ): void {
		$file_path = self::get_file_path( $post_id );

		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Get the path to the storage directory.
	 *
	 * @return string
	 */
	protected static function get_storage_directory(): string {
		return WP_CONTENT_DIR . '/page-specific/css';
	}

	/**
	 * Get the URI to the storage directory.
	 *
	 * @return string
	 */
	protected static function get_storage_directory_uri(): string {
		return content_url( 'page-specific/css' );
	}

	/**
	 * Maybe create the storage directory if it doesn't exist.
	 */
	protected static function maybe_create_directory(): void {
		if ( ! file_exists( self::get_storage_directory() ) ) {
			wp_mkdir_p( self::get_storage_directory() );
		}
	}

	/**
	 * Get the version for the file URL.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_file_version( $post_id ): string {
		return (string) get_post_modified_time( 'U', true, $post_id );
	}

	/**
	 * Get the path to the file.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_file_path( $post_id ): string {
		return self::get_storage_directory() . '/' . self::get_file_name( $post_id );
	}

	/**
	 * Get the URL to the file.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_file_url( $post_id ): string {
		return self::get_storage_directory_uri() . '/' . self::get_file_name( $post_id );
	}

	/**
	 * Get/generate the file name.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_file_name( $post_id ): string {
		$post_type = get_post_type( $post_id );

		return "$post_type-$post_id.css";
	}

	/**
	 * Create the file.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $contents Stylesheet contents.
	 *
	 * @return bool Whether the file was written.
	 */
	protected static function create_file( $post_id, $contents ): bool {
		return false !== file_put_contents( self::get_file_path( $post_id ), $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Minify the styles.
	 *
	 * Falls back to the styles as written when the minifier is not
	 * installed, for example when the site did not run Composer for the
	 * plugin, rather than failing the save with a fatal error.
	 *
	 * @param string $styles Styles as written by the editor.
	 *
	 * @return string
	 */
	public static function minify( string $styles ): string {
		if ( ! class_exists( CSS::class ) ) {
			return $styles;
		}

		return ( new CSS( $styles ) )->minify();
	}
}
