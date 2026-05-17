=== WP Distraction Free View ===
Contributors: mehul0810
Tags: reader mode, reading mode, distraction free, focused reading, accessibility
Donate link: https://buymeacoffee.com/mehulgohil
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 2.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WP Distraction Free View adds a clean frontend Reader Mode to WordPress, helping visitors focus on your content without sidebars, widgets, navigation, and visual clutter.

== Description ==

**WP Distraction Free View** adds a clean frontend Reader Mode to WordPress, helping visitors focus on your content without sidebars, widgets, navigation, and visual clutter.

WordPress core already includes distraction-free writing tools for the admin editor. This plugin is for the frontend visitor reading experience.

Use it to offer focused reading for posts, pages, and selected public custom post types. Visitors can open content in a polished Reader Mode overlay, adjust font size, switch between light/dark/sepia themes, choose a comfortable content width, print the reader view, or use browser fullscreen.

= Features =

1. Frontend Reader Mode for posts, pages, and selected public custom post types.
2. Clean reading column with comfortable typography, spacing, and width.
3. Reader Mode Toggle block for posts, pages, custom post types, and Query Loop templates.
4. Shortcode support with `[wpdfv_reader_toggle]`, `[wpdfv]`, and legacy `[dfview]`.
5. Optional automatic toggle placement before content, after content, or as a floating button.
6. URL activation with `?reader-mode=1`.
7. Visitor preferences for font size, theme, and content width saved in localStorage only.
8. Optional reading progress indicator.
9. Optional estimated reading time.
10. Block-based Reader Mode templates for full site editing themes.
11. WordPress-components settings screen.
12. Lightweight frontend assets with no external tracking, telemetry, or third-party libraries.

= Settings Overview =

Go to **Settings > Reader Mode** to configure:

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
* Reader template.

Automatic toggle insertion is disabled for new installs. Existing installs keep their previous automatic insertion behavior during upgrade unless it was already disabled.

= Usage =

Use the Reader Mode Toggle block in posts, pages, public custom post types, single templates, and Query Loop templates.

Use the preferred shortcode:

`[wpdfv_reader_toggle]`

Legacy shortcodes still work:

`[wpdfv]`

`[dfview]`

Reader Mode can also open from the URL on enabled single content:

`https://example.com/my-post/?reader-mode=1`

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

Yes. Use the Reader Mode Toggle block, `[wpdfv_reader_toggle]`, `[wpdfv]`, or legacy `[dfview]`.

= Can Reader Mode open from a URL? =

Yes. Add `?reader-mode=1` to enabled single posts, pages, or selected public custom post types.

= Are visitor preferences stored on the server? =

No. Font size, theme, and content width preferences are saved only in the visitor browser with localStorage.

= Does Reader Mode support print and fullscreen? =

Yes. Reader Mode includes icon-only print and fullscreen controls in the modal header.

= Can block themes customize the Reader Mode layout? =

Yes. The plugin registers a default block-based Reader Mode template and a WP Distraction Free View pattern category. Themes and site-specific code can add more templates with the `wpdfv_modal_templates` filter.

= Will old settings and shortcodes keep working? =

Yes. Existing saved settings remain in the `wpdfv_settings` option, old `wpdfv_general` values are migrated during upgrade, and legacy `[wpdfv]` and `[dfview]` shortcodes continue to render the Reader Mode toggle.

== Screenshots ==

1. Reader Mode settings screen.
2. Reader Mode toggle on frontend content.
3. Fullscreen Reader Mode with reading preferences.

== Changelog ==

= 2.2.0 =
- Added: Frontend Reader Mode positioning and product copy while keeping the WP Distraction Free View name and slug
- Added: Visitor reader preferences for font size, light/dark/sepia theme, and content width, stored in localStorage
- Added: Optional reading progress indicator and estimated reading time
- Added: URL activation with `?reader-mode=1`
- Added: Toggle placement setting for manual only, before content, after content, or floating button
- Added: Custom exit label setting
- Added: Preferred `[wpdfv_reader_toggle]` shortcode and restored legacy `[dfview]` compatibility
- Added: Shared Reader Mode defaults, sanitization, and idempotent upgrade handling
- Added: PHPUnit test coverage and CI workflow for PHP, PHPCS, JS/CSS linting, and asset builds
- Changed: Default toggle label for new installs is now "Read in Reader Mode"
- Changed: Settings menu copy now frames the feature as Reader Mode
- Changed: Release workflows now use the pinned Node version from `.nvmrc`

= 2.1.0 =
- Added: Reader Button block for posts, pages, public custom post types, and Query Loop templates
- Added: Block-based modal templates with a default template and pattern category for FSE themes
- Added: Setting to disable automatic reader button insertion, disabled by default for new installs
- Changed: Existing installs keep automatic reader button insertion enabled during upgrade unless they had disabled it
- Changed: Reader modal now opens as a full-width viewport modal

= 2.0.0 =
- Changed: Minimum requirements are now WordPress 6.0 and PHP 8.2
- Changed: Rebuilt admin settings with WordPress components and REST API
- Changed: Rebuilt distraction free view modal with WordPress components
- Changed: Modernized the asset build around @wordpress/scripts
- Fixed: Shortcode rendering no longer calls a removed helper function
- Fixed: Runtime autoloading no longer depends on Composer vendor files

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

= 2.2.0 =
Adds the new frontend Reader Mode experience, visitor preferences, reading progress, reading time, URL activation, shortcode compatibility, and idempotent settings migration. Existing saved options and legacy shortcodes continue to work.

= 2.1.0 =
Automatic button insertion is disabled for new installs. Existing installs keep their previous automatic insertion behavior unless they had disabled it.
