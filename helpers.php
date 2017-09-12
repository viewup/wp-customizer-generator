<?php
/**
 * Helper functions for designer
 */


/**
 * @return WPCG_Customizer_Generator
 */
function wpcg_get() {
	global $wpcg_customize;

	return $wpcg_customize;
}

/**
 * Output the setting using the defined function
 *
 * @param string $id
 * @param bool $default Setting Default(if not set, uses the defined default)
 *
 * @return string
 */
function wpcg_get_setting( $id = null, $default = false ) {
	return wpcg_get()->get_setting( $id, $default );
}

/**
 * Output the setting using the defined function
 *
 * @param string $id
 * @param bool $default Setting Default(if not set, uses the defined default)
 */
function wpcg_the_setting( $id = null, $default = false ) {
	wpcg_get()->the_setting( $id, $default );
}

/**
 * Output settings attributes
 *
 * @param string|array|null $id The setting ID, or array of settings IDs, or NULL if the current Setting
 */
function wpcg_the_setting_attribute( $id = null ) {
	wpcg_get()->setting_attributes( $id );
}


// smaller alternatives

if ( ! function_exists( 'get_setting' ) ):
	function get_setting( $id = null, $default = false ) {
		return wpcg_get_setting( $id, $default );
	}
endif;

if ( ! function_exists( 'the_setting' ) ):
	function the_setting( $id = null, $default = false ) {
		wpcg_the_setting( $id, $default );
	}
endif;

if ( ! function_exists( 'the_setting_attribute' ) ):
	function the_setting_attribute( $id = null ) {
		wpcg_the_setting_attribute( $id );
	}
endif;
