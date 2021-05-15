<?php
/**
 * Plugin Name: WP Distraction Free View
 * Plugin URI: https://wordpress.org/plugins/wp-distraction-free-view/
 * Description: "WP Distraction Free View" plugin provides distraction free viewing mode to the users of the website/blog.
 * Version: 1.6.0
 * Author: Mehul Gohil
 * Author URI: https://mehulgohil.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wpdfv
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

// Automatically loads files used throughout the plugin.
require_once WPDFV_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin.
$plugin = new Plugin();
$plugin->register();
