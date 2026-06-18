<?php
/**
 * Minimal WP_REST_Response shim for unit tests.
 *
 * @package WPDistractionFreeView
 */

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param mixed $data Response data.
		 */
		public function __construct( $data ) {
			$this->data = $data;
		}

		/**
		 * Get response data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}
	}
}
