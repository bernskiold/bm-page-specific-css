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

	public function test_revisions_keep_their_styles_in_rest(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$post_id = $this->create_post();
		$this->save_through_rest( $post_id, '.first { color: blue; }' );
		$this->save_through_rest( $post_id, '.second { color: red; }' );

		// The editor restores a revision by reading its meta from here and
		// saving it onto the post.
		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post_id}/revisions" );
		$request->set_param( 'context', 'edit' );
		$styles = wp_list_pluck( wp_list_pluck( rest_do_request( $request )->get_data(), 'meta' ), Plugin::CSS_META_KEY );

		$this->assertContains( '.first { color: blue; }', $styles );
		$this->assertContains( '.second { color: red; }', $styles );
	}

	public function test_restoring_a_revision_restores_its_styles_and_file(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$post_id = $this->create_post();
		$this->save_through_rest( $post_id, '.first { color: blue; }' );

		$first = null;
		foreach ( wp_get_post_revisions( $post_id ) as $revision ) {
			if ( '.first { color: blue; }' === get_metadata( 'post', $revision->ID, Plugin::CSS_META_KEY, true ) ) {
				$first = $revision;
			}
		}
		$this->assertNotNull( $first, 'No revision holds the first styles.' );

		$this->save_through_rest( $post_id, '.second { color: red; }' );

		// The revisions screen restores through this function.
		wp_restore_post_revision( $first->ID );

		$this->assertSame( '.first { color: blue; }', get_post_meta( $post_id, Plugin::CSS_META_KEY, true ) );
		$this->assertSame( '.first{color:blue}', file_get_contents( Generate_Files::get_file_path( $post_id ) ) );
	}

	public function test_write_file_through_a_stream_wrapper_without_stream_flush(): void {
		// An in-memory wrapper with no stream_flush(), like many host
		// wrappers. On PHP 8.6, file_put_contents() returns false through it
		// even when every byte was written.
		$wrapper = new class() {
			/**
			 * File contents by path.
			 *
			 * @var array<string, string>
			 */
			public static array $files = [];

			/**
			 * Whether writes store nothing.
			 *
			 * @var bool
			 */
			public static bool $fail_writes = false;

			/**
			 * Set by PHP.
			 *
			 * @var resource|null
			 */
			public $context;

			/**
			 * Path of the open file.
			 *
			 * @var string
			 */
			private string $path = '';

			/**
			 * Read position in the open file.
			 *
			 * @var int
			 */
			private int $position = 0;

			public function stream_open( string $path, string $mode ): bool {
				$this->path = $path;
				if ( 'w' === $mode[0] ) {
					self::$files[ $path ] = '';
				}
				return isset( self::$files[ $path ] );
			}

			public function stream_write( string $data ): int {
				if ( self::$fail_writes ) {
					return 0;
				}
				self::$files[ $this->path ] .= $data;
				return strlen( $data );
			}

			public function stream_read( int $count ): string {
				$data            = substr( self::$files[ $this->path ], $this->position, $count );
				$this->position += strlen( $data );
				return $data;
			}

			public function stream_eof(): bool {
				return $this->position >= strlen( self::$files[ $this->path ] );
			}

			public function stream_stat(): array {
				return [ 'size' => strlen( self::$files[ $this->path ] ) ];
			}

			public function url_stat( string $path ): array|false {
				if ( ! isset( self::$files[ $path ] ) ) {
					return false;
				}

				return [
					'size' => strlen( self::$files[ $path ] ),
					'mode' => 0100644,
				];
			}
		};

		stream_wrapper_register( 'bmtest', get_class( $wrapper ) );

		try {
			$this->assertTrue( Generate_Files::write_file( 'bmtest://page-1.css', '.a{color:red}' ) );
			$this->assertSame( '.a{color:red}', $wrapper::$files['bmtest://page-1.css'] );

			// A write that really fails must still report the failure. PHP
			// warns about the short write, which would end the test here.
			$wrapper::$fail_writes = true;
			set_error_handler( static fn(): bool => true, E_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			try {
				$written = Generate_Files::write_file( 'bmtest://page-1.css', '.b{color:blue}' );
			} finally {
				restore_error_handler();
			}
			$this->assertFalse( $written );
		} finally {
			stream_wrapper_unregister( 'bmtest' );
		}
	}

	public function test_minify(): void {
		$this->assertSame(
			'.a,.b{color:#fff;margin:0}',
			Generate_Files::minify( "/* Comment */\n.a,\n.b {\n\tcolor: #ffffff;\n\tmargin: 0px;\n}\n" )
		);
	}
}
