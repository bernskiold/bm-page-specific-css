# BM: Page Specific CSS
Adds a Gutenberg panel where you can add specific page-specific CSS.
This is then saved into files, minified and included only only on the very specific page it is created for.

## Requirements
- WordPress 7.0 or later
- PHP 8.2 or later

## Installation
Install with Composer (`bernskioldmedia/bm-page-specific-css`). The plugin uses
`matthiasmullie/minify` to minify the generated stylesheets, so the site's
Composer autoloader must be loaded, or Composer must be run inside the plugin
folder. Without it, the styles are saved unminified.

## Custom Post Types
In order to use this plugin on custom post type, the post type must be shown in
the REST API and have the 'custom-fields' support.

Use the `bm_page_specific_css_post_types` filter to choose which post types the
styles are available for.

## Development
Requires Node 26 (see `.nvmrc`) and Composer.

```bash
npm run setup   # npm install, composer install and build
npm start       # rebuild on change
npm run build   # production build into assets/scripts/dist
npm run lint    # ESLint and PHP_CodeSniffer
```

See [TESTING.md](TESTING.md) for the test suite.

The built editor script in `assets/scripts/dist` is committed, because the
release archive is built from the repository. Commit it with the source change.

## Releasing
Run *Actions → Release → Run workflow* with the version (for example `1.3.0`,
without a `v`), or `gh workflow run release.yml -f version=1.3.0`. The workflow:

- sets the version in `package.json`, the plugin header and `Plugin::VERSION`
- adds the notes to `CHANGELOG.md` and rebuilds
- makes a single "Release 1.3.0" commit on `master` and tags it
- publishes the release with `bm-page-specific-css.zip`
- asks Satis to rebuild

The notes come from a draft release tagged `1.3.0` if one exists, otherwise from
the `notes` input, otherwise GitHub generates them. Don't publish releases or
push tags by hand. Packagist only indexes a tag once, so the tag has to be
created on the final release commit.

## Coding Style
We exclusively try and use the WordPress coding practices for all languages,
which includes PHP, HTML, CSS and JavaScript.

This base plugin is in part OOP based to both live in its own namespace,
and to make extending, updating and maintaining code simple and easy.

## Open Source
The reason we are open sourcing it is because it's the right thing to do.
Perhaps someone finds a use out of it all, or parts. This is not going to be
an actively developed "master plugin base" that is set out to be the next
greatest thing in the WordPress community. It's just going to be what
we use internally, just published for the world to see and use as you see fit.
