<?php
/*
Plugin Name: WP Customizer Generator
Description: Library for creating Settings in WordPress Customizer
Version: 0.1.0
Plugin URI: https://github.com/viewup/wp-customizer-generator
Author URI: http://viewup.com.br/
Author: Viewup
Text Domain: wpcg
*/

define( 'WPCG_PATH', __DIR__ );

// Kirki dependency
@include_once WPCG_PATH . '/kirki/kirki.php';

// Customizer Helper
require_once WPCG_PATH . '/WPCG_Helper.php';

// Customizer
require_once WPCG_PATH . '/WPCG_Customizer_Generator.php';

// Helpers Methods
require_once WPCG_PATH . '/helpers.php';

// Initializer Hook
add_action( 'init', 'WPCG_Customizer_Generator::init' );
