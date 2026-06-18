<?php
/**
 * Plugin Name: WP Distraction Free View
 * Plugin URI: https://github.com/mehul0810/wp-distraction-free-view
 * Description: Adds a clean frontend Reader Mode to WordPress so visitors can focus on posts, pages, and selected public post types.
 * Version: 1.7.1
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Mehul Gohil
 * Author URI: https://mehulgohil.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-distraction-free-view
 * Domain Path: /languages
 *
 * WP Distraction Free View is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 2 of the License, or any later version.
 *
 * WP Distraction Free View is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * WP Distraction Free View. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 *
 * @package    WordPress
 * @subpackage WP Distraction Free View
 * @author     Mehul Gohil <hello@mehulgohil.com>
 */

namespace WPDFV;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Constants.
require_once __DIR__ . '/config/constants.php';

// Load Composer's PSR-4 autoloader for namespaced plugin classes.
$wpdfv_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $wpdfv_autoload ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'WP Distraction Free View requires the Composer autoloader. Run composer install or install the packaged plugin release.', 'wp-distraction-free-view' );
			echo '</p></div>';
		}
	);

	return;
}

require_once $wpdfv_autoload;

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
