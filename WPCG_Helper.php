<?php
/**
 * Customizer Generator Helpers
 */
if ( class_exists( 'WPCG_Helper ' ) ) {
	return null;
}

class WPCG_Helper {

	/**
	 * Detects if an array is indexed
	 *
	 * @param array $array
	 *
	 * @return bool
	 */
	static function is_indexed_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		if ( array() === $array ) {
			return true;
		}

		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	/**
	 * Detects if an variable is an array of arrays
	 *
	 * @param array $matrix
	 *
	 * @return bool
	 */
	static function is_matrix( $matrix = array() ) {
		if ( ! WPCG_Helper::is_indexed_array( $matrix ) ) {
			return false;
		}
		foreach ( $matrix as $array ) {
			if ( ! is_array( $array ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Combine arguments array
	 *
	 * @param array $default
	 * @param array $args
	 *
	 * @return array
	 */
	static function parse_arguments( $default = array(), $args = array() ) {
		return array_merge( $default, $args );
	}

	/**
	 * Parse a indexed array into a associative
	 *
	 * @param array $values indexed array values
	 * @param array $keys associative array keys
	 *
	 * @return array
	 */
	static function parse_indexed_array( $values = array(), $keys = array() ) {
		$args = array();
		foreach ( $keys as $index => $key ) {
			if ( isset( $values[ $index ] ) ) {
				$args[ $key ] = $values[ $index ];
			}
		}

		return $args;
	}

	/**
	 * Parse indexed and non array arguments
	 *
	 * @param array|mixed $values
	 * @param array $keys
	 *
	 * @return array
	 */
	static function parse_indexed_arguments( $values = array(), $keys = array() ) {
		return self::parse_indexed_values( self::array_argument( $values ), $keys );
	}

	/**
	 * Extract key values from array and make an array of the extracted values
	 *
	 * @param array $keys
	 * @param array $values
	 *
	 * @return array
	 */
	static function extract_values( $keys = array(), $values = array() ) {
		$extracted = array();
		$keys      = WPCG_Helper::array_argument( $keys );

		foreach ( $keys as $key ) {
			if ( isset( $values[ $key ] ) ) {
				$extracted[ $key ] = $values[ $key ];
			}
		}

		return $extracted;
	}

	/**
	 * Parse non-array argument to an array
	 *
	 * @param mixed $arg
	 * @param string $key associative key. if not set, an indexed array will be created
	 *
	 * @return array
	 */
	static function array_argument( $arg, $key = null ) {
		if ( ! is_array( $arg ) ) {
			if ( $key ) {
				return array( $key => $arg );
			}

			return array( $arg );
		}

		return $arg;
	}

	/**
	 * Parse indexed values
	 *
	 * @param array $values
	 * @param array $keys
	 *
	 * @return array
	 */
	static function parse_indexed_values( $values = array(), $keys = array() ) {
		if ( WPCG_Helper::is_indexed_array( $values ) ) {
			return WPCG_Helper::parse_indexed_array( $values, $keys );
		}

		return $values;
	}

	/**
	 * Detect if is a partial and get the setting ID
	 *
	 * @param WP_Customize_Partial|string $partial
	 *
	 * @return mixed
	 */
	static function get_partial_id( $partial ) {
		if ( is_string( $partial ) ) {
			return $partial;
		}

		return $partial->primary_setting;
	}

	/**
	 * Sanitize strings and fields ids
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	static function sanitize( $string = '' ) {
		return sanitize_title( $string );
	}

	// Kirki Wrapped methods
	static function get_posts( $args ) {
		return Kirki_Helper::get_posts( $args );
	}

	static function get_image_id( $url ) {
		return Kirki_Helper::get_image_id( $url );
	}

	static function get_image_from_url( $url ) {
		return Kirki_Helper::get_image_from_url( $url );
	}

	static function get_post_types() {
		return Kirki_Helper::get_post_types();
	}

	static function get_terms( $taxonomies ) {
		return Kirki_Helper::get_terms( $taxonomies );
	}

}
