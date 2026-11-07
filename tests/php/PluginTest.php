<?php
/**
 * Tests for the meta registration.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

use BernskioldMedia\WP\PageSpecificCSS\Plugin;
use BernskioldMedia\WP\PageSpecificCSS\Tests\TestCase;

/**
 * @covers \BernskioldMedia\WP\PageSpecificCSS\Plugin
 */
class PluginTest extends TestCase {

	public function tear_down(): void {
		unregister_post_type( 'bm_test_book' );

		parent::tear_down();
	}

	public function test_meta_is_registered_for_posts_and_pages(): void {
		foreach ( [ 'post', 'page' ] as $post_type ) {
			$keys = get_registered_meta_keys( 'post', $post_type );

			$this->assertArrayHasKey( Plugin::CSS_META_KEY, $keys, "Not registered for {$post_type}." );
			$this->assertTrue( $keys[ Plugin::CSS_META_KEY ]['show_in_rest'] );
			$this->assertTrue( $keys[ Plugin::CSS_META_KEY ]['single'] );
			$this->assertSame( 'string', $keys[ Plugin::CSS_META_KEY ]['type'] );
		}
	}

	public function test_meta_is_not_registered_for_every_post_type_at_once(): void {
		// The old register_meta( 'post', ... ) call registered the key with
		// no subtype, which applied it to all post types.
		$this->assertArrayNotHasKey( Plugin::CSS_META_KEY, get_registered_meta_keys( 'post', '' ) );
	}

	public function test_filter_narrows_the_post_types(): void {
		unregister_post_meta( 'post', Plugin::CSS_META_KEY );
		unregister_post_meta( 'page', Plugin::CSS_META_KEY );

		add_filter(
			'bm_page_specific_css_post_types',
			static function () {
				return [ 'page' ];
			}
		);

		Plugin::register_meta();

		$this->assertArrayHasKey( Plugin::CSS_META_KEY, get_registered_meta_keys( 'post', 'page' ) );
		$this->assertArrayNotHasKey( Plugin::CSS_META_KEY, get_registered_meta_keys( 'post', 'post' ) );
	}

	public function test_custom_post_types_in_rest_are_included(): void {
		register_post_type(
			'bm_test_book',
			[
				'public'       => true,
				'show_in_rest' => true,
				'supports'     => [ 'title', 'editor', 'custom-fields' ],
			]
		);

		Plugin::register_meta();

		$this->assertArrayHasKey( Plugin::CSS_META_KEY, get_registered_meta_keys( 'post', 'bm_test_book' ) );
	}

	public function test_meta_is_exposed_in_rest(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Plugin::CSS_META_KEY, 'h1 { color: red; }' );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post_id}" );
		$request->set_param( 'context', 'edit' );
		$data = rest_do_request( $request )->get_data();

		$this->assertSame( 'h1 { color: red; }', $data['meta'][ Plugin::CSS_META_KEY ] );
	}

	public function test_users_who_cannot_edit_the_post_cannot_set_the_styles(): void {
		$post_id = self::factory()->post->create( [ 'post_author' => self::factory()->user->create( [ 'role' => 'editor' ] ) ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'contributor' ] ) );

		$request = new WP_REST_Request( 'POST', "/wp/v2/posts/{$post_id}" );
		$request->set_body_params( [ 'meta' => [ Plugin::CSS_META_KEY => 'body { display: none; }' ] ] );

		$this->assertGreaterThanOrEqual( 400, rest_do_request( $request )->get_status() );
		$this->assertSame( '', get_post_meta( $post_id, Plugin::CSS_META_KEY, true ) );
	}

	public function test_version_matches_plugin_header(): void {
		$header = get_file_data( BM_PAGE_SPECIFIC_CSS_FILE_PATH, [ 'Version' => 'Version' ] );

		$this->assertSame( $header['Version'], Plugin::get_version() );
	}
}
