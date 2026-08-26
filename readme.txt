=== WP Distraction Free View ===
Contributors: mehul0810
Tags: reader mode, reading mode, distraction free, focused reading, accessibility
Donate link: https://buymeacoffee.com/mehulgohil
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds frontend Reader Mode so visitors can focus on posts, pages, and selected public post types.

== Description ==

**WP Distraction Free View** adds a clean frontend Reader Mode to WordPress, helping visitors focus on your content without sidebars, widgets, navigation, and visual clutter.

WordPress core already includes distraction-free writing tools for the admin editor. This plugin is for the frontend visitor reading experience.

Use it to offer focused reading for posts, pages, and selected public custom post types. Visitors can open content in a polished Reader Mode overlay, use the Reader settings control in the modal header to adjust font size, switch between light/dark/sepia themes, choose a comfortable content width, print the reader view, or use browser fullscreen.

= Features =

1. Frontend Reader Mode for posts, pages, and selected public custom post types.
2. Clean reading column with comfortable typography, spacing, and width.
3. Reader Mode Toggle block for posts, pages, custom post types, and Query Loop templates.
4. Shortcode support with `[wpdfv]`.
5. Optional automatic toggle placement before content, after content, or as a floating button.
6. URL activation with `?reader-mode=1`.
7. Visitor preferences for font size, theme, and content width saved in localStorage only.
8. Optional reading progress indicator.
9. Optional estimated reading time.
10. Block-based Reader Mode templates for full site editing themes.
11. Scoped Reader Mode custom CSS for administrators with the WordPress CSS editing capability.
12. WordPress-components settings screen.
13. Lightweight frontend assets with no external tracking, telemetry, or third-party libraries.

= Settings Overview =

Go to **Settings > Reader Mode** to configure:

The settings screen is organized into **About**, **Configure**, and **More Plugins** tabs. Reader Mode configuration lives under **Configure**.

* Enable Reader Mode for selected public post types.
* Toggle placement: manual only, before content, after content, or floating.
* Reading progress.
* Estimated reading time.
* Reader preference controls.
* Default reader theme: light, dark, or sepia.
* Default content width: narrow, default, or wide.
* Default font size: small, default, or large.
* Custom toggle label.
* Custom exit label.
* Reader Mode custom CSS.
* Reader template.

Automatic toggle insertion is disabled for new installs. Existing installs keep their previous automatic insertion behavior during upgrade unless it was already disabled.

= Usage =

Use the Reader Mode Toggle block in posts, pages, public custom post types, single templates, and Query Loop templates.

Use the shortcode:

`[wpdfv]`

Reader Mode can also open from the URL on enabled single content:

`https://example.com/my-post/?reader-mode=1`

Setup and troubleshooting guide:

https://github.com/mehul0810/wp-distraction-free-view/blob/release/1.8.2/docs/reader-mode-setup.md

= Template Customization =

The Reader Mode content is rendered through block-based templates. The plugin registers a default Reader Mode layout and a `WP Distraction Free View` pattern category for block themes.

Themes and site-specific code can add templates with the `wpdfv_modal_templates` filter.

== Installation ==

1. Upload `wp-distraction-free-view` to the `wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure the plugin from **Settings > Reader Mode**.
4. Add the Reader Mode Toggle block or shortcode where needed, or enable automatic toggle insertion.

== Frequently Asked Questions ==

= Is this the same as WordPress editor distraction-free mode? =

No. WordPress core distraction-free mode is for writing in the admin editor. WP Distraction Free View is for the frontend visitor reading experience.

= Can I place the Reader Mode toggle manually? =

Yes. Use the Reader Mode Toggle block or the `[wpdfv]` shortcode.

= Can Reader Mode open from a URL? =

Yes. Add `?reader-mode=1` to enabled single posts, pages, or selected public custom post types.

= Are visitor preferences stored on the server? =

No. Font size, theme, and content width preferences are saved only in the visitor browser with localStorage.

= Does Reader Mode support print and fullscreen? =

Yes. Reader Mode includes icon-only print and fullscreen controls in the modal header.

= Can block themes customize the Reader Mode layout? =

Yes. The plugin registers a default block-based Reader Mode template and a WP Distraction Free View pattern category. Themes and site-specific code can add more templates with the `wpdfv_modal_templates` filter.

= Can I customize Reader Mode styles? =

Yes. Users who can manage plugin settings and have the WordPress `edit_css` capability can add scoped CSS from Settings > Reader Mode > Configure > Custom CSS.

Scope selectors to Reader Mode containers, for example:

`
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
`

= Will old settings keep working? =

Yes. Existing saved settings remain in the `wpdfv_settings` option and old `wpdfv_general` values are migrated during upgrade. The supported shortcode remains `[wpdfv]`.

== Screenshots ==

1. Reader Mode settings screen.
2. Reader Mode toggle on frontend content.
3. Reader Mode modal with reading time, print/fullscreen controls, and the Reader settings side panel.

== Changelog ==

= 1.8.2 =
- Tested: Validated installation, activation, Reader Mode content REST responses, shortcode registration, editor block registration, and RTL/unicode content handling against WordPress 7.1. [#125](https://github.com/mehul0810/wp-distraction-free-view/issues/125)

= 1.8.1 =
- Fixed: Reader Mode launch controls no longer leak into rendered modal content when manual toggles are present. [#99](https://github.com/mehul0810/wp-distraction-free-view/pull/99)
- Fixed: RTL locales now load the generated frontend and admin RTL stylesheets through WordPress's standard RTL replacement behavior. [#105](https://github.com/mehul0810/wp-distraction-free-view/pull/105)

= 1.8.0 =
- Added: Scoped Reader Mode custom CSS controls for administrators with the WordPress CSS editing capability.
- Added: Reader Mode copy/share actions, local resume reading, table of contents navigation, reader-friendly print output, and expanded accessibility-focused typography controls.
- Improved: Reader Mode content reliability for shortcode embeds and complex block output, including support-driven partial or empty content cases.
- Improved: Reader Mode modal compatibility with WordPress core Accordion blocks and Interactivity API script modules.
- Fixed: Reader Mode modal close button, initial keyboard focus, Escape close behavior, page-key scrolling, and tab order reliability.
- Changed: Expanded browser smoke coverage for close behavior, focus, content completeness, accordion interaction, print, sharing, resume reading, and localStorage fallback handling.

= 1.7.1 =
- Fixed: Reader Mode content now strips complete script, style, and noscript blocks before final sanitization so shortcode embed configuration is not displayed as raw text.
- Fixed: Updated the Reader Mode Toggle block metadata to Block API version 3 for current editor compatibility.
- Fixed: Hardened uninstall safety with the required WordPress uninstall guard.
- Changed: Release workflows now replace existing GitHub ZIP assets on rerun and CI validates the generated production package with Plugin Check.

= 1.7.0 =
- Added: Frontend Reader Mode positioning and product copy while keeping the WP Distraction Free View name and slug.
- Added: Reader Mode Toggle block for posts, pages, public custom post types, and Query Loop templates.
- Added: Visitor reader preferences for font size, light/dark/sepia theme, and content width, stored in localStorage.
- Added: Optional reading progress indicator, estimated reading time, and URL activation with `?reader-mode=1`.
- Added: Block-based modal templates with a default template and pattern category for FSE themes.
- Added: More Plugins settings tab with free WordPress.org plugin install/activate actions and paid plugin links.
- Added: Shared Reader Mode defaults, sanitization, and idempotent 1.7.0 upgrade handling from existing 1.6.0 installs.
- Changed: Minimum requirements are now WordPress 6.0 and PHP 8.2.
- Changed: Rebuilt admin settings and Reader Mode modal with WordPress components and REST API.
- Changed: Modernized the asset build around `@wordpress/scripts`.
- Changed: Automatic toggle insertion is disabled for new installs while existing installs keep their previous automatic insertion behavior unless it was already disabled.
- Changed: Kept `[wpdfv]` as the only documented shortcode.
- Changed: Default toggle label for new installs is now "Read in Reader Mode".
- Changed: Settings menu copy now frames the feature as Reader Mode.
- Added: PHPUnit test coverage and CI workflow for PHP, PHPCS, JS/CSS linting, and asset builds.
- Fixed: Activation no longer marks legacy inactive installs as fully upgraded before migrations run.
- Fixed: Reader Mode block output now uses the shared frontend asset handle and settings payload.
- Fixed: Reader content requests now respect the enabled post type configuration.
- Fixed: Reading time is calculated once per Reader Mode response without rendering dynamic blocks.
- Fixed: Shortcode rendering no longer calls a removed helper function.
- Changed: Runtime autoloading now uses Composer's PSR-4 autoloader.

= 1.6.0: 16th May 2021 =
- Refactor: Improved UX for the admin settings UI
- Added: Support for display on post types

= 1.5.0: 10th March 2021 =
- Improvement: Refactor code to use PHP namespaces
- Fix: Resolve issues related to assets not loading
- Feature: Add settings and support links on plugins list

= 1.4.5: 26th November 2019 =
- Ensure that "Read Mode" button text can be changed from settings

= 1.4.4: 23rd November 2019 =
- Updated CSS for the "Read Mode" button to have cursor pointer.

= 1.4.3: 19th November 2019 =
- Included Automated Development Process
- Fix for Fullscreen and Print icon

= 1.4.2: 19th November 2019 =
- Shortcode renamed from `[dfview]` to `[wpdfv]`
- Fix: refactor plugin for stability [#4](https://github.com/mehul0810/wp-distraction-free-view/issues/4)
- Feat: update code to support latest WP 5.3 [#1](https://github.com/mehul0810/wp-distraction-free-view/issues/1)
- Security Improvements
- UI Improvements
- Stability Improvements
- Removed Font Awesome support

= 1.4.0 =
- Removed Genericons Support
- Added Font Awesome Support using CDN

= 1.3.0 =
- Improved Print Support
- Improved Fullscreen support
- Powerful Admin Settings Panel Support Added
- Light weight Adjustments
- Added Flexibility
- Improved User Experience

= 1.2.0 =
- Fixed overlay issues on mostly all the themes

= 1.1.0 =
- Fixed few alignment and validation errors
- Fixed popup inconsistency
- Added Print Support

= 1.0.0: Initial Release =
- Ability to display button at specified location in a post.
- [dfview] Shortcode supported.
- Ability to change "DF View" button text.

== Upgrade Notice ==

= 1.8.2 =
Validated compatibility with WordPress 7.1, including the packaged plugin runtime, Reader Mode content endpoint, settings route registration, shortcode, editor block, and RTL/unicode content handling.

= 1.8.1 =
Prevents Reader Mode toggles from leaking into modal content and ensures frontend and admin RTL stylesheets load for RTL locales.

= 1.8.0 =
Improves Reader Mode reliability, keyboard handling, core Accordion compatibility, content rendering, and browser validation while preserving WordPress 7.0 compatibility.

= 1.7.1 =
Improves Reader Mode compatibility with shortcode embeds that output inline scripts and hardens release/package validation.

= 1.7.0 =
Adds the new frontend Reader Mode experience, visitor preferences, reading progress, reading time, URL activation, shortcode compatibility, and idempotent settings migration from 1.6.0. Existing saved options continue to work, and the supported shortcode remains `[wpdfv]`.
