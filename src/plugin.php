<?php
/**
 * Main plugin class.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

namespace BernskioldMedia\WP\PageSpecificCSS;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */
class Plugin {

	/**
	 * Version
	 *
	 * @var string
	 */
	protected const VERSION = '1.2.0';

	/**
	 * Plugin Textdomain
	 *
	 * @var string
	 */
	public const TEXTDOMAIN = 'bm-page-specific-css';

	/**
	 * Meta Key for the CSS code.
	 *
	 * @var string
	 */
	public const CSS_META_KEY = 'page_specific_styles';

	/**
	 * Meta key flagging whether a post has a generated stylesheet.
	 *
	 * @var string
	 */
	public const HAS_CSS_META_KEY = 'has_page_specific_css';

	/**
	 * Plugin Class Instance Variable
	 *
	 * @var self|null
	 */
	protected static ?self $instance = null;

	/**
	 * Plugin Instantiator
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'init', [ self::class, 'load_languages' ] );
		add_action( 'init', [ self::class, 'register_meta' ], 20 );

		Assets::hooks();
		Generate_Files::hooks();
	}

	/**
	 * Register the styles as post meta.
	 *
	 * Registered per post type with register_post_meta(). The previous
	 * register_meta( $post_type, ... ) calls used the post type as the meta
	 * *object type*: only the call for "post" ever took effect, and it
	 * registered the key for every post type, so the filter below could not
	 * narrow anything.
	 */
	public static function register_meta(): void {
		$post_types = get_post_types( [ 'show_in_rest' => true ] );

		/**
		 * Filter the post types that page specific CSS is available for.
		 *
		 * The post type must also support "custom-fields" for the editor to
		 * read and save the meta through the REST API.
		 *
		 * @param string[] $post_types Post type names.
		 */
		$post_types = (array) apply_filters( 'bm_page_specific_css_post_types', $post_types );

		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				self::CSS_META_KEY,
				[
					'show_in_rest'  => true,
					'type'          => 'string',
					'single'        => true,
					'default'       => '',
					'auth_callback' => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				]
			);
		}
	}

	/**
	 * Load plugin translations.
	 */
	public static function load_languages(): void {

		// Core's filter, applied the same way load_plugin_textdomain() does.
		$locale = apply_filters( 'plugin_locale', determine_locale(), self::TEXTDOMAIN ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Start checking in the main language dir.
		load_textdomain( self::TEXTDOMAIN, WP_LANG_DIR . '/' . self::TEXTDOMAIN . '/' . self::TEXTDOMAIN . '-' . $locale . '.mo' );

		// Otherwise, load from the plugin. The path is relative to the
		// plugins directory: the absolute path passed before never matched.
		load_plugin_textdomain( self::TEXTDOMAIN, false, dirname( plugin_basename( BM_PAGE_SPECIFIC_CSS_FILE_PATH ) ) . '/languages' );
	}

	/**
	 * Get the path to the plugin folder, or the specified
	 * file relative to the plugin folder home.
	 *
	 * @param string $file Path relative to the plugin folder.
	 *
	 * @return string
	 */
	public static function get_path( $file = '' ): string {
		return untrailingslashit( plugin_dir_path( BM_PAGE_SPECIFIC_CSS_FILE_PATH ) ) . '/' . $file;
	}

	/**
	 * Get the URL to the plugin folder, or the specified
	 * file relative to the plugin folder home.
	 *
	 * @param string $file Path relative to the plugin folder.
	 *
	 * @return string
	 */
	public static function get_url( $file = '' ): string {
		return untrailingslashit( plugin_dir_url( BM_PAGE_SPECIFIC_CSS_FILE_PATH ) ) . '/' . $file;
	}

	/**
	 * Get the URL to the assets folder, or the specified
	 * file relative to the assets folder home.
	 *
	 * @param string $file Path relative to the assets folder.
	 *
	 * @return string
	 */
	public static function get_assets_url( $file = '' ): string {
		return self::get_url( 'assets/' . $file );
	}

	/**
	 * Get the Plugin's Version
	 *
	 * @return string
	 */
	public static function get_version(): string {
		return self::VERSION;
	}
}
