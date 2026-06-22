# WP Distraction Free View

WP Distraction Free View adds a clean frontend Reader Mode to WordPress, helping visitors focus on your content without sidebars, widgets, navigation, comments, and visual clutter.

[Download WP Distraction Free View on WordPress.org](https://wordpress.org/plugins/wp-distraction-free-view/)

![WordPress version](https://img.shields.io/wordpress/plugin/v/wp-distraction-free-view.svg)
![WordPress Rating](https://img.shields.io/wordpress/plugin/r/wp-distraction-free-view.svg)
![WordPress Downloads](https://img.shields.io/wordpress/plugin/dt/wp-distraction-free-view.svg)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](https://github.com/mehul0810/wp-distraction-free-view/blob/master/license.txt)

## Positioning

WordPress core already includes distraction-free writing tools for the admin editor. This plugin is for the frontend visitor reading experience: posts, pages, and selected public custom post types can open in a focused Reader Mode overlay.

## Features

- Frontend Reader Mode for posts, pages, and enabled public custom post types.
- Manual placement with the Reader Mode Toggle block or the `[wpdfv]` shortcode.
- Optional automatic toggle insertion before content, after content, or as a floating button.
- URL activation with `?reader-mode=1` on enabled single content.
- Visitor preferences for font size, theme, and content width from the Reader settings control in the modal header, stored in localStorage.
- Optional reading progress and estimated reading time.
- Block-based Reader Mode template support for full site editing themes.
- WordPress-components settings screen and REST-backed settings persistence.

## Requirements

- WordPress 6.0 or later
- Tested up to WordPress 7.0
- PHP 8.2 or later
- Node.js 24.15.0
- npm 11 or later
- Composer for PHP autoloading and development tooling

The Node version is pinned in `.nvmrc` and `.node-version`.

## Local Development

Clone the repository into your local WordPress plugins directory:

```bash
cd /path/to/wp-content/plugins
git clone https://github.com/mehul0810/wp-distraction-free-view.git
cd wp-distraction-free-view
```

Install dependencies:

```bash
composer install
npm install
```

Build assets:

```bash
npm run build
```

Start the watch build:

```bash
npm run start
```

Run checks:

```bash
composer validate --strict
composer lint
composer test
npm run lint:js
npm run lint:css
npm run build
```

Run the WordPress integration suite against the WordPress test library:

```bash
bash bin/install-wp-tests.sh wordpress_test root root localhost latest
WP_TESTS_DIR=/tmp/wordpress-tests-lib composer test:integration
WP_TESTS_DIR=/tmp/wordpress-tests-lib WP_MULTISITE=1 composer test:integration
```

The integration suite is intentionally separate from `composer test`. It boots real WordPress APIs for REST routing, block registration/rendering, options, migrations, and multisite activation coverage.

## Release Workflow

The WordPress.org release workflow generates `wp-distraction-free-view.zip` through the 10up deploy action and uploads that ZIP to the matching GitHub release. Uploads use `gh release upload --clobber`, so rerunning a release or prerelease workflow replaces the existing ZIP asset instead of failing when the asset name already exists.

CI also builds the production package from `.distignore`, validates the package shape, and runs Plugin Check against the generated package directory rather than the raw source checkout.

## Settings

The settings page is available at **Settings > Reader Mode**.

The screen is organized into **About**, **Configure**, and **More Plugins** tabs. Reader Mode configuration lives under **Configure**.

Available settings:

- Enable Reader Mode for selected public post types.
- Toggle placement: manual only, before content, after content, or floating.
- Reading progress.
- Estimated reading time.
- Reader preference controls.
- Default reader theme: light, dark, or sepia.
- Default content width: narrow, default, or wide.
- Default font size: small, default, or large.
- Custom toggle label.
- Custom exit label.
- Reader Mode custom CSS for scoped modal styling.
- Reader template.

Automatic insertion is disabled for new installs. Existing installs keep their previous automatic insertion behavior during upgrade unless it was already disabled.

## Shortcodes

Use the shortcode:

```text
[wpdfv]
```

## Blocks

Use the **WP Distraction Free View / Reader Mode Toggle** block in posts, pages, public custom post types, single templates, and Query Loop templates.

The block is dynamic and uses the current post context, so it opens the correct post when placed in single templates or inside Query Loop templates.

## Setup and Troubleshooting

For step-by-step placement guidance, URL activation, preference privacy notes, and shortcode/embed troubleshooting, see [Reader Mode Setup and Troubleshooting](docs/reader-mode-setup.md).

## Modal Templates

The Reader Mode content is rendered through block-based templates. The plugin registers a default Reader Mode layout and a `WP Distraction Free View` pattern category for block themes.

Block themes and site-specific code can register additional Reader Mode templates with the `wpdfv_modal_templates` filter.

## Reader Mode Custom CSS

Administrators who can manage plugin settings and have the WordPress `edit_css` capability can add scoped CSS from **Settings > Reader Mode > Configure > Custom CSS**. The saved CSS is printed only with Reader Mode frontend assets and is attached to the plugin stylesheet handle.

Keep selectors scoped to Reader Mode containers:

```css
.wpdfv-reader-modal .wpdfv-reader-content {
	font-family: Georgia, serif;
}

.wpdfv-reader-modal .wpdfv-reader-content h1,
.wpdfv-reader-modal .wpdfv-reader-content h2 {
	color: #1f2937;
}

.wpdfv-reader-modal {
	--wpdfv-reader-accent-color: #3858e9;
}
```

Developers can filter the final CSS with `wpdfv_custom_css` or change the required editing capability with `wpdfv_custom_css_capability`.

## Upgrade Notes

Version `1.7.0` adds normalized Reader Mode settings while keeping the existing `wpdfv_settings` option. The upgrade routine is incremental and idempotent:

- `wpdfv_version` stores the installed plugin version.
- Legacy `wpdfv_general` values are migrated into `wpdfv_settings`.
- Legacy `display_location = disable` becomes `manual_only`.
- Existing custom button labels and enabled post types are preserved.
- New Reader Mode defaults are added without destructive migrations.
- Development-only `2.0.0` to `2.2.0` version markers are normalized to the 1.7.0 upgrade path.
- If an upgrade fails, existing settings remain unchanged and an admin notice is shown.

## Public Compatibility

Preserved public surfaces:

- Option names: `wpdfv_settings`, `wpdfv_version`, and legacy `wpdfv_general` reads during upgrade.
- Shortcode: `[wpdfv]`.
- Filters: `wpdfv_should_enqueue_frontend_assets`, `wpdfv_modal_templates`, `wpdfv_modal_template_content`, and `wpdfv_reading_time_words_per_minute`.
- Constants: existing `WPDFV_*` constants.

## Manual Test Checklist

- Activate plugin on latest WordPress.
- Activate plugin on a site with existing old plugin options.
- Confirm legacy settings still work or migrate correctly.
- Enable Reader Mode for posts and pages.
- Visit a single post.
- Enter and exit Reader Mode.
- Test query param activation with `?reader-mode=1`.
- Test shortcode `[wpdfv]`.
- Test mobile viewport.
- Test dark, sepia, and light themes.
- Test font size and width preferences.
- Test reading progress.
- Confirm assets are not loaded on irrelevant admin screens or unsupported frontend pages.
- Confirm no PHP warnings with `WP_DEBUG` enabled.
- Run the automated test suite locally.

## Support

This repository is for development. For user support, use the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view).

## Development Notes

- Product UI and asset decisions should follow the lightweight design contract in [DESIGN.md](DESIGN.md).
- Commit `package-lock.json` whenever npm dependency metadata changes.
- Built assets are generated into `assets/dist`.
- Runtime plugin code uses Composer's PSR-4 autoloader. Production packages include the no-dev Composer autoload files generated from `composer.json`.
- The plugin is licensed under GPL-2.0-or-later.
