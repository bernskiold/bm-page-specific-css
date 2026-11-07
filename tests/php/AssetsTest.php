<?php
/**
 * Tests for loading the stylesheets and the editor script.
 *
 * @package BernskioldMedia\WP\PageSpecificCSS
 */

use BernskioldMedia\WP\PageSpecificCSS\Assets;
use BernskioldMedia\WP\PageSpecificCSS\Generate_Files;
use BernskioldMedia\WP\PageSpecificCSS\Plugin;
use BernskioldMedia\WP\PageSpecificCSS\Tests\TestCase;

/**
 * @covers \BernskioldMedia\WP\PageSpecificCSS\Assets
 */
class AssetsTest extends TestCase {

	/**
	 * Post with styles.
	 *
	 * @var int
	 */
	private int $styled_post;

	public function set_up(): void {
		parent::set_up();

		$this->styled_post = self::factory()->post->create();
		update_post_meta( $this->styled_post, Plugin::CSS_META_KEY, 'body { background: hotpink; }' );
		wp_update_post( [ 'ID' => $this->styled_post ] );

		$this->reset_styles();
	}

	public function tear_down(): void {
		$path = Generate_Files::get_file_path( $this->styled_post );

		if ( file_exists( $path ) ) {
			unlink( $path );
		}

		$this->reset_styles();

		parent::tear_down();
	}

	private function reset_styles(): void {
		$GLOBALS['wp_styles']  = null;
		$GLOBALS['wp_scripts'] = null;
	}

	private function handle( int $post_id ): string {
		return 'page-' . $post_id;
	}

	public function test_styles_load_on_the_single_view(): void {
		$this->go_to( get_permalink( $this->styled_post ) );

		Assets::public_styles();

		$this->assertTrue( wp_style_is( $this->handle( $this->styled_post ), 'enqueued' ) );
		$this->assertSame(
			Generate_Files::get_file_url( $this->styled_post ),
			wp_styles()->registered[ $this->handle( $this->styled_post ) ]->src
		);
	}

	public function test_styles_do_not_load_on_other_posts(): void {
		$other = self::factory()->post->create();
		$this->go_to( get_permalink( $other ) );

		Assets::public_styles();

		$this->assertFalse( wp_style_is( $this->handle( $this->styled_post ), 'enqueued' ) );
		$this->assertFalse( wp_style_is( $this->handle( $other ), 'enqueued' ) );
	}

	public function test_styles_do_not_load_on_archives(): void {
		// The styled post is the newest, so it is first in the loop and was
		// the global $post that the old check read.
		$this->go_to( home_url( '/' ) );
		$this->assertTrue( is_home() );

		the_post();
		$this->assertSame( $this->styled_post, get_the_ID() );

		Assets::public_styles();

		$this->assertFalse( wp_style_is( $this->handle( $this->styled_post ), 'enqueued' ) );
	}

	public function test_editor_script_is_enqueued_with_the_built_dependencies(): void {
		$asset_file = Plugin::get_path( 'assets/scripts/dist/editor.asset.php' );
		$this->assertFileExists( $asset_file, 'Run `npm run build` first.' );

		$asset = require $asset_file;

		Assets::block_editor_scripts();

		$this->assertTrue( wp_script_is( 'bm-page-specific-css', 'enqueued' ) );

		$script = wp_scripts()->registered['bm-page-specific-css'];

		$this->assertSame( Plugin::get_assets_url( 'scripts/dist/editor.js' ), $script->src );
		$this->assertSame( $asset['dependencies'], $script->deps );
		$this->assertSame( $asset['version'], $script->ver );
		$this->assertSame( 'bm-page-specific-css', $script->textdomain );
	}
}
