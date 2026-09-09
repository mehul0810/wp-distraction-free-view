<?php
/**
 * Provision a Studio fixture post for RTL and Unicode Reader Mode proof.
 *
 * @package WPDistractionFreeView
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_basename = 'wp-distraction-free-view/wp-distraction-free-view.php';

if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_basename ) && ! is_plugin_active( $plugin_basename ) ) {
	activate_plugin( $plugin_basename );
}

if ( ! is_plugin_active( $plugin_basename ) || ! class_exists( '\WPDFV\Includes\Reader' ) ) {
	fwrite( STDERR, "WP Distraction Free View must be active before provisioning the RTL fixture.\n" );
	exit( 1 );
}

$settings = array_merge(
	\WPDFV\Includes\Reader::get_default_settings(),
	(array) get_option( 'wpdfv_settings', [] )
);

$settings['automatic_button_enabled'] = true;
$settings['display_location']         = 'after_content';
$settings['where_to_display']         = array_values(
	array_unique(
		array_merge(
			(array) $settings['where_to_display'],
			[ 'post' ]
		)
	)
);

update_option( 'wpdfv_settings', $settings, false );
\WPDFV\Includes\Reader::invalidate_request_cache();

$slug        = 'wpdfv-rtl-unicode-reader-fixture';
$plugin_file = defined( 'WPDFV_PLUGIN_FILE' ) ? WPDFV_PLUGIN_FILE : WP_PLUGIN_DIR . '/' . $plugin_basename;
$icon_url    = esc_url( plugins_url( 'assets/dist/images/wpdfv-icon.png', $plugin_file ) );
$embed_slug  = 'wpdfv-rtl-unicode-embed-source';

$embed_post = get_page_by_path( $embed_slug, OBJECT, 'post' );

if ( ! $embed_post instanceof WP_Post ) {
	$embed_post_id = wp_insert_post(
		wp_slash(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'WPDFV Embed Source',
				'post_name'    => $embed_slug,
				'post_content' => '<p>Embedded source for WPDFV Reader Mode RTL proof.</p>',
			]
		),
		true
	);

	if ( is_wp_error( $embed_post_id ) ) {
		fwrite( STDERR, $embed_post_id->get_error_message() . "\n" );
		exit( 1 );
	}

	$embed_post = get_post( $embed_post_id );
}

$pattern_id = 0;

if ( post_type_exists( 'wp_block' ) ) {
	$pattern_existing = get_page_by_path( 'wpdfv-rtl-unicode-synced-pattern', OBJECT, 'wp_block' );
	$pattern_content  = '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group"><!-- wp:paragraph --><p dir="rtl" lang="fa">الگوی همگام Reader Mode برای مقایسه‌ی محتوای همگام‌شده.</p><!-- /wp:paragraph --><!-- wp:list --><ul class="wp-block-list"><li>همگام ۱</li><li>همگام ۲</li></ul><!-- /wp:list --></div><!-- /wp:group -->';

	if ( $pattern_existing instanceof WP_Post ) {
		$pattern_id = wp_update_post(
			wp_slash(
				[
					'ID'           => $pattern_existing->ID,
					'post_content' => $pattern_content,
				]
			),
			true
		);
	} else {
		$pattern_id = wp_insert_post(
			wp_slash(
				[
					'post_type'    => 'wp_block',
					'post_status'  => 'publish',
					'post_title'   => 'WPDFV RTL Unicode Synced Pattern',
					'post_name'    => 'wpdfv-rtl-unicode-synced-pattern',
					'post_content' => $pattern_content,
				]
			),
			true
		);
	}

	if ( is_wp_error( $pattern_id ) ) {
		$pattern_id = 0;
	}
}

$embed_url            = esc_url( get_permalink( $embed_post ) );
$synced_pattern_block = $pattern_id > 0 ? sprintf( "\n<!-- wp:block {\"ref\":%d} /-->\n", $pattern_id ) : '';

$content = sprintf(
	<<<HTML
<!-- wp:shortcode -->
[wpdfv]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">«حالت مطالعه» برای WPDFV 1.8.0</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p dir="rtl" lang="fa">این پاراگراف فارسی برای بررسی Reader Mode ساخته شده است؛ می‌خواهیم اعداد فارسی ۱۲۳۴۵۶۷۸۹۰، اعداد عربی ١٢٣٤٥٦٧٨٩٠، نشانه‌های «، ؛ ؟»، نقل‌قول‌ها، و ایموجی 👩‍💻 🚀 را بدون به‌هم‌ریختگی ببینیم.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p dir="rtl" lang="fa">نمونه‌ی نیم‌فاصله: می‌نویسم و کتاب‌خانه. نمونه‌ی پیوند ZWJ: 👨‍👩‍👧‍👦. عبارت دارای اِعراب: السَّلَامُ عَلَيْكُمْ.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p dir="auto">Mixed LTR/RTL: نسخه WPDFV 1.8.0 در URL https://development.wp.local/?reader-mode=1 با quote "Reader Mode" و کد <code>wpdfv_reader_preferences</code> آزمایش می‌شود.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">زیرعنوان فارسی و English Mix</h2>
<!-- /wp:heading -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote -->
<blockquote class="wp-block-quote"><p dir="rtl" lang="fa">«خواندن متمرکز» باید متن ترکیبی RTL/LTR را سالم نگه دارد.</p></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p dir="auto">Nested block column with URL https://example.com/path?reader-mode=1 and quote “Reader Mode”.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:list -->
<ul class="wp-block-list"><li>فهرست اول با نیم‌فاصله‌ی می‌خوانیم</li><li>Arabic digits: ١٢٣٤٥</li><li>Persian digits: ۱۲۳۴۵</li><li>Emoji sequence: 👩‍💻 👨‍👩‍👧‍👦</li></ul>
<!-- /wp:list -->

<!-- wp:table -->
<figure class="wp-block-table"><table><thead><tr><th>ردیف</th><th>مقدار</th><th>LTR Note</th></tr></thead><tbody><tr><td>۱</td><td>۱۲٬۳۴۵</td><td>Build 1.8.0</td></tr><tr><td>٢</td><td>١٢٬٣٤٥</td><td>reader-mode=1</td></tr></tbody></table></figure>
<!-- /wp:table -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="%1\$s" alt="WPDFV fixture icon" width="128" height="128" /></figure>
<!-- /wp:image -->

<!-- wp:shortcode -->
[caption width="128"]<img src="%2\$s" alt="نماد Reader Mode" width="128" height="128" /> نماد Reader Mode با caption فارسی[/caption]
<!-- /wp:shortcode -->

<!-- wp:embed {"url":"%3\$s","type":"rich"} -->
<figure class="wp-block-embed is-type-rich is-provider-wordpress wp-block-embed-wordpress"><div class="wp-block-embed__wrapper">
%3\$s
</div></figure>
<!-- /wp:embed -->

<!-- wp:code -->
<pre class="wp-block-code"><code lang="text">const wpdfvFixture = "می‌خواهیم";
const mixedSample = "Reader Mode 1.8.0";</code></pre>
<!-- /wp:code -->

<!-- wp:preformatted -->
<pre class="wp-block-preformatted">RTL preformatted: می‌خوانیم، ۱۲۳۴۵، ١٢٣٤٥، 👩‍💻</pre>
<!-- /wp:preformatted -->
%4\$s

<!-- wp:paragraph -->
<p dir="rtl" lang="fa">پاراگراف پایانی برای مقایسه‌ی بالا و پایین دکمه‌ها. اگر Reader Mode درست کار کند، همین متن باید در مودال و اسکرین‌شات موبایل و دسکتاپ دیده شود.</p>
<!-- /wp:paragraph -->
HTML,
	$icon_url,
	$icon_url,
	$embed_url,
	$synced_pattern_block
);

$existing = get_page_by_path( $slug, OBJECT, 'post' );
$postarr  = [
	'post_type'    => 'post',
	'post_status'  => 'publish',
	'post_title'   => 'WPDFV RTL Unicode Reader Fixture',
	'post_name'    => $slug,
	'post_content' => $content,
];

if ( $existing instanceof WP_Post ) {
	$postarr['ID'] = $existing->ID;
	$post_id       = wp_update_post( wp_slash( $postarr ), true );
} else {
	$post_id = wp_insert_post( wp_slash( $postarr ), true );
}

if ( is_wp_error( $post_id ) ) {
	fwrite( STDERR, $post_id->get_error_message() . "\n" );
	exit( 1 );
}

echo wp_json_encode(
	[
		'id'   => (int) $post_id,
		'slug' => $slug,
		'url'  => get_permalink( $post_id ),
	]
) . PHP_EOL;
