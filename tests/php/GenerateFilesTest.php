<?php
/**
 * Tests for writing, updating and removing the generated stylesheets.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

use BernskioldMedia\WP\PageSpecificCSS\Generate_Files;
use BernskioldMedia\WP\PageSpecificCSS\Plugin;
use BernskioldMedia\WP\PageSpecificCSS\Tests\TestCase;

/**
 * @covers \BernskioldMedia\WP\PageSpecificCSS\Generate_Files
 */
class GenerateFilesTest extends TestCase {

	/**
	 * Posts created by a test, whose files are removed afterwards.
	 *
	 * @var int[]
	 */
	private array $post_ids = [];

	public function tear_down(): void {
		foreach ( $this->post_ids as $post_id ) {
			$path = Generate_Files::get_file_path( $post_id );

			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}

		$this->post_ids = [];

		parent::tear_down();
	}

	private function create_post( array $args = [] ): int {
		$post_id          = self::factory()->post->create( $args );
		$this->post_ids[] = $post_id;

		return $post_id;
	}

	/**
	 * Save the post the way the block editor does: one REST request that
	 * updates the post and its meta together.
	 */
	private function save_through_rest( int $post_id, string $css ): WP_REST_Response {
		$route   = 'page' === get_post_type( $post_id ) ? 'pages' : 'posts';
		$request = new WP_REST_Request( 'POST', "/wp/v2/{$route}/{$post_id}" );
		$request->set_body_params(
			[
				'title' => 'Updated',
				'meta'  => [ Plugin::CSS_META_KEY => $css ],
			]
		);

		return rest_do_request( $request );
	}

	public function test_file_name_uses_post_type_and_id(): void {
		$post_id = $this->create_post( [ 'post_type' => 'page' ] );

		$this->assertSame( "page-{$post_id}.css", Generate_Files::get_file_name( $post_id ) );
		$this->assertStringEndsWith( "/page-specific/css/page-{$post_id}.css", Generate_Files::get_file_path( $post_id ) );
		$this->assertSame( content_url( "page-specific/css/page-{$post_id}.css" ), Generate_Files::get_file_url( $post_id ) );
	}

	public function test_file_url_follows_the_content_url_filter(): void {
		$post_id = $this->create_post( [ 'post_type' => 'page' ] );

		// A CDN or an HTTPS rewrite filters content_url(); the raw
		// WP_CONTENT_URL constant would bypass it.
		$filter = static fn( string $url, string $path ): string => 'https://cdn.example.org/content/' . $path;
		add_filter( 'content_url', $filter, 10, 2 );

		try {
			$this->assertSame( "https://cdn.example.org/content/page-specific/css/page-{$post_id}.css", Generate_Files::get_file_url( $post_id ) );
		} finally {
			remove_filter( 'content_url', $filter, 10 );
		}
	}

	public function test_saving_through_rest_writes_the_new_styles(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$post_id = $this->create_post();
		update_post_meta( $post_id, Plugin::CSS_META_KEY, '.old { color: blue; }' );

		$response = $this->save_through_rest( $post_id, ".new {\n\tcolor: red;\n}\n" );

		$this->assertSame( 200, $response->get_status() );

		// The file must hold the styles from this save, not the previous one.
		$this->assertFileExists( Generate_Files::get_file_path( $post_id ) );
		$this->assertSame( '.new{color:red}', file_get_contents( Generate_Files::get_file_path( $post_id ) ) );
		$this->assertSame( 'true', get_post_meta( $post_id, Plugin::HAS_CSS_META_KEY, true ) );
	}

	public function test_clearing_the_styles_removes_the_file(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$post_id = $this->create_post();
		$this->save_through_rest( $post_id, 'body { margin: 0; }' );
		$this->assertFileExists( Generate_Files::get_file_path( $post_id ) );

		$this->save_through_rest( $post_id, '   ' );

		$this->assertFileDoesNotExist( Generate_Files::get_file_path( $post_id ) );
		$this->assertSame( 'false', get_post_meta( $post_id, Plugin::HAS_CSS_META_KEY, true ) );
	}

	public function test_saving_without_styles_writes_no_file(): void {
		$post_id = $this->create_post();

		$this->assertFileDoesNotExist( Generate_Files::get_file_path( $post_id ) );
		$this->assertSame( 'false', get_post_meta( $post_id, Plugin::HAS_CSS_META_KEY, true ) );
	}

	public function test_deleting_the_post_removes_the_file(): void {
		$post_id = $this->create_post();
		update_post_meta( $post_id, Plugin::CSS_META_KEY, 'p { color: green; }' );
		wp_update_post( [ 'ID' => $post_id ] );

		$path = Generate_Files::get_file_path( $post_id );
		$this->assertFileExists( $path );

		wp_delete_post( $post_id, true );

		$this->assertFileDoesNotExist( $path );
	}

	public function test_revisions_get_no_file(): void {
		$post_id = $this->create_post();
		update_post_meta( $post_id, Plugin::CSS_META_KEY, 'p { color: green; }' );
		wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => 'Changed, so a revision is stored.',
			]
		);

		$revisions = wp_get_post_revisions( $post_id );
		$this->assertNotEmpty( $revisions );

		foreach ( $revisions as $revision ) {
			$this->assertFileDoesNotExist( Generate_Files::get_file_path( $revision->ID ) );
		}
	}

	public function test_minify(): void {
		$this->assertSame(
			'.a,.b{color:#fff;margin:0}',
			Generate_Files::minify( "/* Comment */\n.a,\n.b {\n\tcolor: #ffffff;\n\tmargin: 0px;\n}\n" )
		);
	}
}
