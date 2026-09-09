=== WP Distraction Free View ===
Contributors: mehul0810
Tags: reader mode, reading mode, distraction free, focused reading, accessibility
Donate link: https://buymeacoffee.com/mehulgohil
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.8.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds frontend Reader Mode so visitors can focus on posts, pages, and selected public post types.

== Description ==

**WP Distraction Free View** gives visitors a focused Reader Mode for posts, pages, and selected public custom post types. It removes surrounding theme chrome such as sidebars, widgets, and navigation while keeping the article content available in a readable overlay.

Visitors can open Reader Mode with a toggle block, shortcode, automatic placement, or `?reader-mode=1`. The modal provides controls for font size, light/dark/sepia themes, content width, print, and fullscreen.

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
12. Settings screen built with native WordPress controls.
13. Lightweight frontend assets; the plugin itself adds no tracking or telemetry.

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

https://github.com/mehul0810/wp-distraction-free-view/blob/release/1.8.3/docs/reader-mode-setup.md

= Template Customization =

The Reader Mode content is rendered through block-based templates. The plugin registers a default Reader Mode layout and a `WP Distraction Free View` pattern category for block themes.

Themes and site-specific code can add templates with the `wpdfv_modal_templates` filter.

== Installation ==

1. Upload `wp-distraction-free-view` to the `wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure the plugin from **Settings > Reader Mode**.
4. Add the Reader Mode Toggle block or shortcode where needed, or enable automatic toggle insertion.

== Support ==

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view/) for setup questions, compatibility reports, and user support.

Use [GitHub issues](https://github.com/mehul0810/wp-distraction-free-view/issues) for reproducible development issues and code-level reports. Include the WordPress version, theme, provider name, and a minimal example. Do not post private embed keys, tokens, or account identifiers.

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

= What happens to YouTube and Spotify embeds? =

Supported YouTube and Spotify embeds remain interactive in Reader Mode. If an interactive player cannot be preserved, supported provider content is reduced to a labelled link; unsupported or unsafe iframe markup is removed. Loading a supported player can contact that provider from the visitor's browser. Review [YouTube's privacy policy](https://policies.google.com/privacy) and [Spotify's privacy policy](https://www.spotify.com/legal/privacy-policy/) for their data practices. WP Distraction Free View does not add its own tracking or telemetry.

= Can block themes customize the Reader Mode layout? =

Yes. The plugin registers a default block-based Reader Mode template and a WP Distraction Free View pattern category. Themes and site-specific code can add more templates with the `wpdfv_modal_templates` filter.

= Can I customize Reader Mode styles? =

Yes. Users who can manage plugin settings and have the WordPress `edit_css` capability can add scoped CSS from Settings > Reader Mode > Configure > Custom CSS.

Scope selectors to Reader Mode containers, for example:

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

= Will existing settings keep working after an upgrade? =

Yes. Existing settings are migrated during upgrade, and the supported shortcode remains `[wpdfv]`.

== Screenshots ==

1. Reader Mode settings screen.
2. Reader Mode toggle on frontend content.
3. Reader Mode modal with reading time, print/fullscreen controls, and the Reader settings side panel.

== Changelog ==

= 1.8.3 =
- Fixed: Reader Mode now preserves and renders trusted YouTube and Spotify embeds with a safe iframe allowlist while keeping arbitrary iframe, script, style, and event-handler content blocked. [#137](https://github.com/mehul0810/wp-distraction-free-view/issues/137)

For earlier releases, see the [full changelog](https://github.com/mehul0810/wp-distraction-free-view/blob/release/1.8.3/changelog.txt).

== Upgrade Notice ==

= 1.8.3 =
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
