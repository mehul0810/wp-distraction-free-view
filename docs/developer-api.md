# Developer API

This document describes public extension points for Reader Mode. Public
content returned by these APIs uses the same post-type setting, password
protection, publication status, and public-visibility checks as the Reader Mode
interface.

## Structured Reader Mode content

GET /wp-json/wp-distraction-free-view/v1/structured-content/{post_id} returns
a JSON representation of content that is available in Reader Mode. The
response includes:

- canonicalUrl, title, excerpt, text, and sanitized rendered html.
- readingTime with minutes and a localized label.
- language, publishedAt, modifiedAt, and postType.
- author, containing the public display name when present, or null.
- featuredImage, containing url and alt when the post has a featured image,
  or null.

The route returns the same 404 and 403 errors as the interactive Reader Mode
content route. It does not include extracted scripts or raw stored post content.
Use the wpdfv_structured_reader_content filter to add or adjust public fields.
Do not add private metadata, credentials, or information that is not intended
for public readers.

## Reader Mode templates

The wpdfv_modal_templates filter registers block-markup templates keyed by a
sanitized template slug. A definition must include non-empty string content.
Label and description are plain-text UI fields and default to the slug and an
empty string when omitted. Optional category sets the block pattern category;
it defaults to wpdfv-modal-templates.

Optional preview metadata is an array with an image URL and alt text. The image
URL is sanitized before it is returned to the settings UI. Invalid or missing
preview URLs are ignored.

The existing filter remains backward compatible: missing optional fields use
their defaults, invalid definitions are skipped, and the built-in default
template is always available.

## Per-content settings

Public post editors include a Reader Mode panel with an availability override
and an optional template override. Availability accepts inherit, enabled, or
disabled. Inherit uses the global post-type configuration. An explicit
enabled override can enable an individual item whose public post type is not
globally enabled; it does not make private, password-protected, or
non-publicly-viewable content public.

The corresponding single post meta keys are _wpdfv_reader_mode and
_wpdfv_reader_template. The value of the availability key is inherit, enabled,
or disabled. A blank template key inherits the global selected template; a
non-empty value must match a registered template slug.

Use the wpdfv_is_post_enabled_for_post filter to adjust the final per-post
availability result.

## Visitor content preferences

The Reader settings panel lets visitors hide images and media, embedded
content, or comments from the rendered Reader view. These controls are enabled
by default and are stored only in the visitor's browser preference storage.
They do not change stored post content; media alt attributes and figure
captions remain in the rendered document, and restoring a control reveals the
original output.

Use the wpdfv_reader_content_controls filter to hide one or more of these
controls. Return an array with media, embeds, and comments boolean values. A
missing key keeps that control available.

Read-aloud controls are disabled by default. Administrators can enable them in
Settings > Reader Mode > Configure > Reading tools. When enabled and supported
by the visitor's browser, speech uses the browser's built-in speech synthesis
for the rendered Reader Mode content only.

## Discovery metadata

Reader Mode discovery metadata is disabled by default. Integrations can opt in
for eligible public singular content with the
wpdfv_discovery_metadata_enabled filter. When enabled, the plugin emits an
alternate JSON link to the structured content route and JSON-LD describing that
public content. The wpdfv_discovery_metadata filter can adjust the generated
metadata.

## Abilities API

On WordPress 6.9 and later, the plugin registers a read-only
wp-distraction-free-view/get-reader-content ability when the Abilities API is
available. It accepts a post ID and returns only content available through the
public Reader Mode contract. On earlier WordPress versions, the ability is not
registered and the plugin continues to work normally.
