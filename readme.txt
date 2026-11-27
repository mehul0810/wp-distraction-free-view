=== WP Distraction Free View ===
Contributors: mehul0810
Tags: reader mode, reading mode, distraction free, focused reading, accessibility
Donate link: https://buymeacoffee.com/mehulgohil
Requires at least: 6.0
Tested up to: 7.2
Requires PHP: 8.2
Stable tag: 1.9.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds frontend Reader Mode so visitors can focus on posts, pages, and selected public post types.

== Description ==

Give visitors a **Read in Reader Mode** button that opens the article over the current page. They can read without the theme’s navigation or sidebar, then close the reader to return to the page.

Readers can adjust the theme, text size, and width, hide media, embeds, or comments when controls are enabled, and print or use fullscreen. Optional reading tools include progress, reading time, a table of contents, resume reading, and browser read-aloud. Read-aloud requires a matching local browser voice.

Add a Reader Mode Toggle block or `[wpdfv]` shortcode, choose an automatic button placement, or open enabled content with `?reader-mode=1`. New installations start with manual placement.

Under **Settings > Reader Mode > Configure**, use **General** to choose post types and button placement, **Appearance** for reader defaults, **Reading tools** for optional controls, and **Advanced** for scoped custom CSS. Individual content can override availability and the reader template. Block themes can also use Reader Mode templates.

See the [setup and troubleshooting guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/reader-mode-setup.md) or the [developer API guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/developer-api.md) for integration details.

== Installation ==

1. Install and activate WP Distraction Free View through WordPress, or upload the plugin directory to `wp-content/plugins/` and activate it.
2. Open **Settings > Reader Mode > Configure > General** and select the post types where Reader Mode should be available. Save your changes.
3. Add the **Reader Mode Toggle** block or `[wpdfv]` shortcode to content, or choose an automatic placement under **General**.
4. Visit an enabled post or page and select the toggle to open Reader Mode.

== Frequently Asked Questions ==

= Why is the toggle missing? =

Check the enabled post types and button placement in **Settings > Reader Mode > Configure > General**. Automatic placement is disabled on new installations, so add a block or shortcode or choose an automatic placement. Existing installations retain their previous automatic placement setting during upgrade unless it was already disabled.

= Can I open Reader Mode with a link? =

Yes. Open Reader Mode on an enabled post or page and use **Copy Reader Mode link** in the toolbar. You can also add `?reader-mode=1` to a permalink, or `&reader-mode=1` if the URL already has a query string.

= Where are visitor preferences saved? =

Theme, text size, width, and content visibility preferences are saved in the visitor's browser using localStorage when available. When resume reading is enabled, reading position is also stored there. A visitor's saved choices take precedence over the administrator's defaults while reader preference controls are enabled.

= Can I change the Reader Mode design? =

Choose defaults under **Configure > Appearance**. Administrators with the WordPress CSS editing capability can add scoped styles under **Configure > Advanced > Custom CSS**. Block themes and site-specific code can also add templates; see the [developer API guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/developer-api.md).

= Where can I get help? =

See the [setup and troubleshooting guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/reader-mode-setup.md) or ask in the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view/).

== Screenshots ==

1. Choose which content types offer Reader Mode and where the button appears under Configure > General.
2. The Read in Reader Mode button on a published article, placed with the shortcode.
3. The same article in the sepia reader theme, with reading time, reader settings, link, print, and fullscreen controls in the toolbar.

== Changelog ==

= 1.9.0 =
- Added: Visitor controls for media, embeds, and comments, optional browser read-aloud, per-content availability and template overrides, structured public content, WordPress Abilities API support, and opt-in discovery metadata.
- Added: PreviewShare to the free companion plugin catalog.
- Improved: Redesigned the About and Configure screens with clearer setup guidance, block and shortcode information, admin notices below the header, and a version badge.
- Improved: More Plugins now uses locally bundled plugin icons and compact, responsive three-column cards on desktop.
- Improved: The selected settings tab and Configure section remain open after a page refresh.
- Improved: Formalized and documented the Reader Mode template registry, and split the settings API, plugin catalog, and plugin actions into focused services.

= 1.8.4 =
- Fixed: Scoped Reader Mode component styles to the plugin root so WordPress block editor controls retain their core styling. [#146](https://github.com/mehul0810/wp-distraction-free-view/issues/146)

= 1.8.3 =
- Fixed: Direct-access guard in the upgrades handler now matches the rest of the plugin instead of exposing a hardcoded string. [#126](https://github.com/mehul0810/wp-distraction-free-view/pull/126)
- Fixed: Uninstall now also removes the legacy `wpdfv_general` option left behind by pre-1.6.0 installs, and cleans up every site on a multisite network instead of only the current one. [#126](https://github.com/mehul0810/wp-distraction-free-view/pull/126)
- Fixed: Reader Mode now preserves and renders trusted YouTube and Spotify embeds with a safe iframe allowlist while keeping arbitrary iframe, script, style, and event-handler content blocked. [#137](https://github.com/mehul0810/wp-distraction-free-view/issues/137)

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

= 1.9.0 =
Adds the redesigned settings screen, per-content overrides, reader content controls, and integration APIs. Existing settings are preserved. Read-aloud and discovery metadata remain opt-in.

= 1.8.4 =
Restores WordPress block editor control styling by scoping Reader Mode component styles to the plugin root.

= 1.8.3 =
Hardens the direct-access guard in the upgrades handler and completes uninstall cleanup, including multisite networks.
Preserves supported YouTube and Spotify embeds inside Reader Mode while keeping untrusted embed markup blocked.

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
