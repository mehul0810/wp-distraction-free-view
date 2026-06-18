<?php
/**
 * Minimal WP_REST_Request shim for unit tests.
 *
 * @package WPDistractionFreeView
 */

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request implements ArrayAccess {
		/**
		 * Request parameters.
		 *
		 * @var array
		 */
		private $params = [];

		/**
		 * Set a request parameter.
		 *
		 * @param string $key   Parameter key.
		 * @param mixed  $value Parameter value.
		 *
		 * @return void
		 */
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}

		/**
		 * Whether an offset exists.
		 *
		 * @param mixed $offset Offset.
		 *
		 * @return bool
		 */
		public function offsetExists( $offset ): bool {
			return array_key_exists( $offset, $this->params );
		}

		/**
		 * Get an offset value.
		 *
		 * @param mixed $offset Offset.
		 *
		 * @return mixed
		 */
		public function offsetGet( $offset ): mixed {
			return $this->params[ $offset ] ?? null;
		}

		/**
		 * Set an offset value.
		 *
		 * @param mixed $offset Offset.
		 * @param mixed $value  Value.
		 *
		 * @return void
		 */
		public function offsetSet( $offset, $value ): void {
			$this->params[ $offset ] = $value;
		}

		/**
		 * Unset an offset value.
		 *
		 * @param mixed $offset Offset.
		 *
		 * @return void
		 */
		public function offsetUnset( $offset ): void {
			unset( $this->params[ $offset ] );
		}
	}
}
