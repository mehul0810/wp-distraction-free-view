# WP Distraction Free View Design Contract

This file defines the product design boundaries for WP Distraction Free View. It is intentionally lightweight: use it to review future UI, copy, WordPress.org asset, and documentation changes without turning the plugin into a broad design-system project.

## Product Principles

- Keep Reader Mode focused on frontend reading. Do not blur the product with WordPress admin editor distraction-free writing workflows.
- Preserve the visitor's content as the primary surface. Controls should be discoverable, compact, and secondary to reading.
- Prefer WordPress-native patterns in admin and block-editor contexts.
- Keep behavior predictable across classic themes, block themes, single templates, Query Loop templates, shortcodes, and automatic placement.
- Avoid visual choices that assume a specific site brand, theme palette, or content type.

## Frontend Reader Mode

- The Reader Mode overlay should feel calm, content-first, and reversible.
- The reading column should prioritize legibility: comfortable line length, sufficient spacing, and clear hierarchy from the rendered post content.
- Header controls should remain compact and consistently positioned. Icon-only controls need accessible labels and visible focus states.
- Reader preferences should affect only the reading experience they describe, such as theme, width, and font size.
- Fullscreen, print, close, and settings controls should not obscure long-form content or create layout shifts while reading.
- URL activation with `?reader-mode=1` should land visitors directly in the same Reader Mode experience as the toggle.

## Admin Settings

- The settings screen lives at Settings > Reader Mode and should follow standard WordPress admin conventions.
- Keep the About, Configure, and More Plugins tab model understandable and stable unless a product issue explicitly changes it.
- Configuration controls should use WordPress component patterns where practical and should map clearly to the frontend behavior they control.
- Defaults should be conservative. New installs should not surprise site owners with automatic frontend insertion unless the setting clearly opts into it.
- Settings copy should explain outcomes, not implementation details.

## Blocks And Shortcodes

- The Reader Mode Toggle block and `[wpdfv]` shortcode should represent the same user-facing action.
- Block labels, placeholders, and editor copy should use "Reader Mode" language consistently.
- Dynamic block behavior should respect the current post context in single templates and Query Loop templates.
- Manual placement should remain safe for users who need precise control over where the Reader Mode toggle appears.

## Accessibility

- Every interactive control needs an accessible name.
- Keyboard users must be able to open Reader Mode, use controls, and exit without becoming trapped.
- Focus states must be visible against light, dark, and sepia reader themes.
- Color must not be the only way to communicate control state.
- Responsive behavior must preserve readable content width, reachable controls, and non-overlapping text on small screens.
- Motion should be subtle and should not be required to understand state changes.
- Print output should prioritize readable content over decorative interface elements.

## Responsive Behavior

- The reading column should adapt to mobile, tablet, and desktop without horizontal scrolling.
- Control groups should wrap or compress before overlapping content.
- Reader preference panels should remain usable on narrow viewports.
- Long labels, translated strings, and custom toggle text must not break layout.

## WordPress.org And Public Assets

- WordPress.org screenshots should show real product surfaces: settings, frontend toggle, Reader Mode overlay, and preference controls.
- Banners and icons should be simple, readable at small sizes, and aligned with a focused reading product.
- Plugin page copy should stay competitor-neutral and should describe the frontend visitor reading use case.
- The readme should remain the source for WordPress.org page content. Deeper implementation or troubleshooting guidance belongs in repo or product docs.

## Copy And Tone

- Use clear product language: "Reader Mode", "reader settings", "toggle", "print", "fullscreen", and "content width".
- Keep admin copy calm and practical. Avoid marketing claims inside settings controls.
- Reader-facing copy should be short enough to fit compact controls and translated interfaces.
- Error and empty states should explain the next useful action when possible.

## Non-Goals

- Do not use this file to redesign the Reader Mode UI by itself.
- Do not introduce a component library, theme framework, or broad visual system.
- Do not require a specific site theme, color palette, font, or brand treatment.
- Do not duplicate the README, WordPress.org readme, changelog, or support documentation.
- Do not change frontend or admin behavior without a separate implementation issue.
