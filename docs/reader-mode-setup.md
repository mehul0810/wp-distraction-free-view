# Reader Mode Setup and Troubleshooting

Use this guide to put a Reader Mode button on your first article, choose where it appears across your site, or troubleshoot a missing button.

## Quick Setup

On a new installation, WP Distraction Free View enables Reader Mode for posts and pages and sets button placement to **Manual only**. Existing installations keep their saved content types and placement when you upgrade.

1. Edit a post or page. Add the **Reader Mode Toggle** block where you want the button, or add a **Shortcode** block containing `[wpdfv]`.
2. Update or publish the post or page, then open it on your site.
3. Select **Read in Reader Mode**. The article opens over the page. Close the reader to return to the page.

To add the button across a whole content type, use [automatic placement](#automatic-placement).

## Block Placement

Search for **Reader Mode Toggle** in the block inserter. You can place it in a post, page, enabled public custom post type, single template, or Query Loop’s Post Template block.

In a Query Loop, each button opens the article for that entry.

## Shortcode Usage

In the classic editor or a Shortcode block, enter the following shortcode on its own line.

```text
[wpdfv]
```

The shortcode can be placed in post content, widget areas, template parts, or other shortcode-aware surfaces. Reader Mode assets load when the shortcode renders a valid toggle for an enabled post type.

## Automatic Placement

1. Open **Settings > Reader Mode > Configure > General**.
2. Under **Where Reader Mode is available**, select the content types that should offer the reader.
3. Under **Toggle placement**, choose **Before content**, **After content**, or **Floating button**. Select **Save settings**.

Open an individual post or page of a selected type to see the button. For a button on each entry in an archive built with a Query Loop, use [block placement](#block-placement).

## Supported Post Types

Reader Mode supports posts, pages, and selected public custom post types. If the button is missing across a content type, check that the type is selected under **Configure > General**. For **Manual only**, also check that the content or its template includes the block or shortcode.

If the button is missing on just one item, edit it and open **Reader Mode > Availability**. Choose **Use global settings** to follow the site setting or **Enabled for this content** to turn Reader Mode on for that item. Save the post or page. The same panel lets you choose a reader template for that item.

## URL Activation

Open the reader on an enabled post or page and select **Copy Reader Mode link** in the toolbar. To build a link like the one below, append `?reader-mode=1` to the permalink.

```text
https://example.com/my-post/?reader-mode=1
```

If the permalink already has a query string, append `&reader-mode=1`.

## Reader Preferences and Privacy

Set the default theme, font size, and content width under **Configure > Appearance**. When **Show reader preference controls** is enabled, visitors can change their own reading layout. The plugin saves those choices only in the visitor’s browser, using local storage when available. Previously saved appearance choices still apply if you later hide the controls.

Visitors can also hide media, embeds, or comments when you offer those controls. Reader Mode applies saved visibility choices only while the corresponding control is available.

Under **Configure > Reading tools**, you can enable **Resume reading** so visitors can return to their place in an unfinished article in the same browser. This section also offers read-aloud. To use it, the visitor’s browser must have a local voice that matches the article’s language.

## Custom CSS

Users who can manage plugin settings and edit CSS can add styles under **Settings > Reader Mode > Configure > Advanced > Custom CSS**. The stylesheet loads on pages that use Reader Mode. A selector such as `h2` can also affect headings on the surrounding page. Start each selector with `.wpdfv-reader-modal` to limit its effect to the open reader.

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

Enter CSS rules directly and select **Save settings**. Open an article in Reader Mode to check the result.

## Shortcode and Embed Troubleshooting

If a shortcode, video, or other embed looks different in Reader Mode, use these checks to narrow down the cause.

1. Confirm the original post content renders correctly outside Reader Mode.
2. Confirm the plugin, theme, and shortcode provider are updated.
3. Check whether the shortcode provider requires inline scripts that only run in the original theme layout.
4. Test whether the issue appears with a default WordPress theme.
5. Share the shortcode provider name, WordPress version, theme, and a minimal example in the support channel.

Do not paste private embed keys, tokens, or account identifiers into public support threads.

## Support Boundaries

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view/) for user support, setup questions, and compatibility reports.

Use [GitHub issues](https://github.com/mehul0810/wp-distraction-free-view/issues) for reproducible bugs or code-level reports.
