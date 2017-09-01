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
