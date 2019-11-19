<?php
/**
 * Plugin Name: WP Distraction Free View
 * Plugin URI: https://wordpress.org/plugins/wp-distraction-free-view/
 * Description: "WP Distraction Free View" plugin provides distraction free viewing mode to the users of the website/blog.
 * Version: 1.4.2
 * Author: Mehul Gohil
 * Author URI: http://mehulgohil.in/
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

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WPDFV' ) ) {

	/**
	 * Class WPDFV
     *
     * @since 1.0.0
	 */
    final class WPDFV {

	    /** Singleton *************************************************************/

	    /**
	     * Instance
	     *
	     * @since  1.0.0
	     * @access protected
	     *
	     * @var    WPDFV() The one true instance
	     */
	    protected static $_instance;

	    /**
	     * Main Instance
	     *
	     * Ensures that only one instance exists in memory at any one
	     * time. Also prevents needing to define globals all over the place.
	     *
	     * @since  1.0.0
	     * @access public
	     *
	     * @static
	     * @see    WPDFV()
	     *
	     * @return WPDFV()
	     */
	    public static function instance() {

	        if ( is_null( self::$_instance ) ) {
			    self::$_instance = new self();
		    }

		    return self::$_instance;
	    }

	    /**
	     * Throw error on object clone
	     *
	     * The whole idea of the singleton design pattern is that there is a single
	     * object, therefore we don't want the object to be cloned.
	     *
	     * @since  1.0
	     * @access protected
	     *
	     * @return void
	     */
	    public function __clone() {
		    // Cloning instances of the class is forbidden.
		    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpdfv' ), '1.0.0' );
	    }
	    /**
	     * Disable unserializing of the class
	     *
	     * @since  1.0
	     * @access protected
	     *
	     * @return void
	     */
	    public function __wakeup() {
		    // Unserializing instances of the class is forbidden.
		    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wpdfv' ), '1.0.0' );
	    }

	    /**
	     * WPDFV constructor.
         *
         * @since  1.0.0
         * @access public
         *
         * @return void
	     */
	    public function __construct() {

	        $this->setup_constants();
		    $this->includes();
		    $this->init_hooks();
	    }

	    /**
	     * Setup Constants
         *
         * @since  1.0.0
         * @access public
         *
         * @return void
	     */
	    public function setup_constants() {
		    if ( ! defined( 'WPDFV_VERSION' ) ) {
			    define( 'WPDFV_VERSION', '1.4.2' );
		    }

		    if ( ! defined( 'WPDFV_PLUGIN_FILE' ) ) {
			    define( 'WPDFV_PLUGIN_FILE', __FILE__ );
		    }

		    if ( ! defined( 'WPDFV_PLUGIN_URL' ) ) {
			    define( 'WPDFV_PLUGIN_URL', plugin_dir_url( WPDFV_PLUGIN_FILE ) );
		    }

		    if ( ! defined( 'WPDFV_PLUGIN_DIR' ) ) {
			    define( 'WPDFV_PLUGIN_DIR', plugin_dir_path( WPDFV_PLUGIN_FILE ) );
		    }
	    }

	    /**
	     * Include required files.
         *
         * @since  1.0.0
         * @access public
	     */
        public function includes() {

            if ( is_admin() ) {
		        require_once WPDFV_PLUGIN_DIR . 'includes/admin/admin-actions.php';
		        require_once WPDFV_PLUGIN_DIR . 'includes/admin/class-wpdfv-admin-settings-api.php';
		        require_once WPDFV_PLUGIN_DIR . 'includes/admin/class-wpdfv-admin-settings.php';
	        }

            require_once WPDFV_PLUGIN_DIR . 'includes/install.php';
            require_once WPDFV_PLUGIN_DIR . 'includes/helpers.php';
            require_once WPDFV_PLUGIN_DIR . 'includes/filters.php';
            require_once WPDFV_PLUGIN_DIR . 'includes/actions.php';
	        require_once WPDFV_PLUGIN_DIR . 'includes/class-wpdfv-shortcodes.php';

        }

	    /**
	     * Initialize Essential Hooks
	     *
	     * @since  1.0.0
	     * @access public
	     *
	     * @return void
	     */
        public function init_hooks() {

            // Trigger Plugin Activation Hook.
            register_activation_hook( WPDFV_PLUGIN_FILE, 'wpdfv_install' );

            // Initialize Plugin.
            add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
        }

	    /**
	     * Init Plugin.
	     *
	     * @since  1.0.0
	     * @access public
	     *
	     * @return void
	     */
        public function init() {

	        // Set up localization.
	        $this->load_textdomain();

        }

	    /**
	     * Loads the plugin language files.
	     *
	     * @since  1.0.0
	     * @access public
	     *
	     * @return void
	     */
	    public function load_textdomain() {

	        // Set filter for languages directory.
		    $lang_dir = dirname( plugin_basename( WPDFV_PLUGIN_FILE ) ) . '/languages/';
		    $lang_dir = apply_filters( 'wpdfv_languages_directory', $lang_dir );

		    // Traditional WordPress plugin locale filter.
		    $locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		    $locale = apply_filters( 'plugin_locale', $locale, 'wpdfv' );

		    // Unload textdomain.
		    unload_textdomain( 'wpdfv' );

		    // Load textdomain.
		    load_textdomain( 'wpdfv', WP_LANG_DIR . '/wpdfv/wpdfv-' . $locale . '.mo' );
		    load_plugin_textdomain( 'wpdfv', false, $lang_dir );
	    }
    }

	/**
     * Start WP Distraction Free View plugin.
     *
     * @since 1.0.0
     *
	 * @return WPDFV
	 */
    function wpdfv() {
        return WPDFV::instance();
    }

    wpdfv();
}
