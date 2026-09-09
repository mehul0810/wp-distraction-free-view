# Reader Mode Setup and Troubleshooting

This guide covers the public Reader Mode experience in WP Distraction Free View: where Reader Mode is available, how visitors open it, and what to check when a toggle or embedded content does not behave as expected.

## Quick Setup

1. Activate WP Distraction Free View.
2. Go to **Settings > Reader Mode**.
3. Open the **Configure** tab.
4. Enable Reader Mode for the public post types that should support focused reading.
5. Choose a placement:
   - Use **manual only** when you want to place the toggle with the block or shortcode.
   - Use **before content**, **after content**, or **floating** when the plugin should insert the toggle automatically.
6. Save settings.
7. Visit an enabled single post, page, or custom post type and open Reader Mode from the toggle.

Reader Mode is disabled for new installs until you enable post types or place a manual toggle.

## Block Placement

Use the **WP Distraction Free View / Reader Mode Toggle** block when editing posts, pages, public custom post types, single templates, or Query Loop templates.

The block is dynamic. In a single template or Query Loop, it uses the current post context so each toggle opens Reader Mode for the correct post.

## Shortcode Usage

Use the shortcode when a block is not the right fit:

```text
[wpdfv]
```

The shortcode can be placed in post content, widget areas, template parts, or other shortcode-aware surfaces. Reader Mode assets load when the shortcode renders a valid toggle for an enabled post type.

## Automatic Placement

Automatic placement inserts the Reader Mode toggle on supported single content without adding a block or shortcode manually.

Use automatic placement when you want the same entry point across enabled post types. Use manual placement when a theme, template, or editorial workflow needs precise control over where the toggle appears.

## Supported Post Types

Reader Mode can be enabled for posts, pages, and selected public custom post types. If a toggle does not appear, confirm that the current content type is enabled in **Settings > Reader Mode > Configure**.

Reader Mode is intended for single content views. Archive pages, search results, unsupported post types, and unrelated admin screens should not load the frontend Reader Mode assets.

## URL Activation

Reader Mode can open directly from a URL on enabled single content:

```text
https://example.com/my-post/?reader-mode=1
```

Use this for direct links to a focused reading view. The URL parameter uses the same Reader Mode experience as the toggle.

## Reader Preferences and Privacy

Visitors can adjust font size, theme, and content width when preference controls are enabled. These preferences are stored in the visitor browser with `localStorage`.

WP Distraction Free View does not store Reader Mode preference choices on the server.

## Custom CSS

Users who can manage plugin settings and have the WordPress `edit_css` capability can add Reader Mode CSS from **Settings > Reader Mode > Configure > Custom CSS**.

This CSS is an escape hatch for the Reader Mode output only. Keep selectors scoped to Reader Mode containers so the original theme page is unchanged:

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

The plugin strips pasted `<style>` wrappers before saving and prints the saved CSS through the Reader Mode stylesheet handle only when Reader Mode assets are loaded.

## Shortcode and Embed Troubleshooting

Version 1.7.1 strips complete `script`, `style`, and `noscript` blocks from Reader Mode content before final sanitization. This prevents common shortcode embed configuration from appearing as raw text inside the Reader Mode view.

If embedded content still looks wrong:

1. Confirm the original post content renders correctly outside Reader Mode.
2. Confirm the plugin, theme, and shortcode provider are updated.
3. Check whether the shortcode provider requires inline scripts that only run in the original theme layout.
4. Test whether the issue appears with a default WordPress theme.
5. Share the shortcode provider name, WordPress version, theme, and a minimal example in the support channel.

Do not paste private embed keys, tokens, or account identifiers into public support threads.

## Support Boundaries

Use the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view/) for user support, setup questions, and compatibility reports.

Use [GitHub issues](https://github.com/mehul0810/wp-distraction-free-view/issues) for reproducible development issues, release-train work, and code-level reports.
