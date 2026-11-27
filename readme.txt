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

Give visitors a distraction-free way to read posts and pages, with adjustable text size, reading width, and light, dark, or sepia themes.

== Description ==

Give visitors a **Read in Reader Mode** button that opens the article over the current page. They can read without the theme’s navigation or sidebar, then close the reader to return to the page.

Visitors can choose a light, dark, or sepia theme, adjust text size and reading width, and show or hide media, embeds, and comments when those controls are available. They can also print the article or read in fullscreen.

Under **Settings > Reader Mode > Configure > Reading tools**, you can show reading progress and estimated reading time, add a table of contents for articles with two or more headings, or let visitors resume an article where they left off. You can also enable read-aloud for browsers that have a local voice matching the article’s language.

On a new installation, Reader Mode is enabled for posts and pages. Add the **Reader Mode Toggle** block or the `[wpdfv]` shortcode wherever you want a button. For automatic placement or other public post types, go to **Settings > Reader Mode > Configure > General**. In the editor, you can turn Reader Mode on or off for one post or page and choose its reader template.

== Installation ==

1. Install and activate **WP Distraction Free View** from **Plugins > Add Plugin**.
2. Edit a post or page. Add the **Reader Mode Toggle** block or place `[wpdfv]` in a **Shortcode** block. Update or publish the post or page.
3. Open the published page and select **Read in Reader Mode**. Close the reader to return to the page.

To add the button automatically, open **Settings > Reader Mode > Configure > General**. Under **Toggle placement**, choose **Before content**, **After content**, or **Floating button**, then select **Save settings**. Visit an individual post of a selected content type to see the button.

== Frequently Asked Questions ==

= Why is the Reader Mode button missing? =

New installations start at **Manual only**. Upgrades keep your saved placement. Check **Settings > Reader Mode > Configure > General** for the selected content types and placement. If placement is **Manual only**, check that the post or its template contains the **Reader Mode Toggle** block or `[wpdfv]`.

If the button is missing on just one post or page, edit it and open **Reader Mode > Availability**. Choose **Use global settings** to follow the site setting or **Enabled for this content** to turn Reader Mode on for that item. Save the post or page.

= Can I open Reader Mode with a link? =

Yes. Open Reader Mode on an enabled post or page and use **Copy Reader Mode link** in the toolbar. You can also add `?reader-mode=1` to a permalink, or `&reader-mode=1` if the URL already has a query string.

= Where are visitor preferences saved? =

Reader Mode remembers appearance choices, such as theme and text size, in the visitor’s browser when local storage is available. It also remembers which media, embeds, and comments the visitor chose to hide, and applies those choices when the site enables the corresponding controls. If **Resume reading** is enabled, visitors can pick up an unfinished article in the same browser.

= Can I change the Reader Mode design? =

Choose the default theme, text size, and reading width under **Settings > Reader Mode > Configure > Appearance**. Administrators with permission to edit CSS can add styles under **Configure > Advanced > Custom CSS**. Start each selector with `.wpdfv-reader-modal` to limit its effect to the open reader. For custom reader templates in a theme or plugin, see the [developer API guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/developer-api.md).

= Where can I get help? =

See the [setup and troubleshooting guide](https://github.com/mehul0810/wp-distraction-free-view/blob/main/docs/reader-mode-setup.md) or ask in the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view/).

== Screenshots ==

1. Choose which content types offer Reader Mode and where the button appears under Configure > General.
2. The Read in Reader Mode button on a published article, placed with the shortcode.
3. The same article in the sepia reader theme, with reading time, reader settings, link, print, and fullscreen controls in the toolbar.

== Changelog ==

= 1.9.1 =
- Reader Mode preserves heading attributes that contain angle brackets and keeps the surrounding paragraphs intact. Contents links take you to the correct heading when names repeat.
- If you switch articles while Reader Mode is loading, the last article you opened stays in view. Reader Mode checks the article ID before loading content and leaves scripts inside HTML comments inactive.
- If you edit a setting while a save is in progress, your newer edit stays on screen and is marked as unsaved.
- Reader Mode skips repeated checks when hiding media in large galleries. Automatic buttons appear on individual content pages, and pages with several buttons load custom CSS once.
- Companion plugins run their setup when activated. On multisite, Reader Mode uses the current site’s settings and templates, and network activation applies only to sites in that network.
- The structured content API keeps angle brackets in code examples and separates adjacent paragraphs. Built-in discovery metadata uses the post’s written excerpt for its optional description.
- Tested with WordPress 7.2 RC3 on PHP 8.2 through 8.6.

= 1.9.0 =
- Added: Visitor controls for media, embeds, and comments, optional browser read-aloud, per-content availability and template overrides, structured public content, WordPress Abilities API support, and opt-in discovery metadata.
- Added: PreviewShare to the free companion plugin catalog.
- Improved: Redesigned the About and Configure screens with clearer setup guidance, block and shortcode information, admin notices below the header, and a version badge.
- Improved: More Plugins now uses locally bundled plugin icons and compact, responsive three-column cards on desktop.
- Improved: The selected settings tab and Configure section remain open after a page refresh.
- Improved: Formalized and documented the Reader Mode template registry, and split the settings API, plugin catalog, and plugin actions into focused services.

Earlier versions are listed in the [changelog and upgrade notices through 1.9.0](https://github.com/mehul0810/wp-distraction-free-view/blob/1.9.0/readme.txt#L158).

== Upgrade Notice ==

= 1.9.1 =
This update fixes article formatting and heading links. Edits made while settings are saving stay on screen for your next save. If you use discovery metadata, add an excerpt to posts that need a description.

= 1.9.0 =
Adds the redesigned settings screen, per-content overrides, reader content controls, and integration APIs. Existing settings are preserved. Read-aloud and discovery metadata remain opt-in.
