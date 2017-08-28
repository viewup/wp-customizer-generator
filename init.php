<?php
/**
 * Initialize a global instance and action
 */

function wpcg_init() {
	global $wpcg_customize;

	$wpcg_customize = new WPCG_Customizer_Generator();

	do_action( 'wpcg_customize_register', $wpcg_customize );
}

add_action( 'init', 'wpcg_init' );

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
 */
function the_setting( $id = '', $default = false ) {
	wpcg_get()->the_setting( $id, $default );
}

/**
 * Output settings attributes
 *
 * @param string|array|null $id The setting ID, or array of settings IDs, or NULL if the current Setting
 */
function setting_attributes( $id = null ) {
	wpcg_get()->setting_attributes( $id );
}
