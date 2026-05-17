<?php
/**
 * Plugin Name: WP Distraction Free View
 * Plugin URI: https://github.com/mehul0810/wp-distraction-free-view
 * Description: Adds a clean frontend Reader Mode to WordPress so visitors can focus on posts, pages, and selected public post types.
 * Version: 1.7.0
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

// Automatically load plugin classes without requiring Composer at runtime.
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$file           = WPDFV_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
