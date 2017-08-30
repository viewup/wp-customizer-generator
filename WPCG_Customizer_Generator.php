<?php
/**
 * Customizer Generator Content
 */
if ( class_exists( 'WPCG_Customizer_Generator ' ) ) {
	return null;
}

/**
 * Customizer Generator Class
 *
 * This is an Customizer wrapper.
 * Major customizer creation functions should work in the same way (Not guaranteed),
 * but this wrapper add new easier ways to add content.
 * The returned method data isn't the same as the customizer.
 *
 * Currently, The class uses Kirki project because it automatically makes the css rendering.
 * But in the future, it will be removed. When adding methods, encapsulate Kirki usage.
 *
 * All methods without return should return the instance
 *
 * @link https://en.wikipedia.org/wiki/Fluent_interface
 *
 * Class WPCG_Customizer_Generator
 */
class WPCG_Customizer_Generator {

	/**
	 * The customizer Manager
	 *
	 * @var WP_Customize_Manager
	 */
	public $wp_customize = null;

	/**
	 * The Default Kirki ID
	 * @var string
	 */
	public $kirki_id = 'theme-settings';

	/**
	 * Control ID Mask
	 *
	 * @var string
	 */
	private $control_mask = '%s-control';

	/**
	 * Partial HTML Selector Mask.
	 *
	 * Data attribute is used by default, because don't affect CSS.
	 *
	 * This selector is used because multiple fields can happen on the same selector (may not work on IE8)
	 *
	 * @var string
	 */
	private $partial_selector_mask = '[data-wp-setting~="%s"]';

	private $setting_attribute_mask = 'data-wp-setting="%s"';

	/**
	 * Current Panel ID
	 *
	 * @var string
	 */
	private $current_panel = '';

	/**
	 * Current Section ID
	 *
	 * @var string
	 */
	private $current_section = 'theme-settings';

	/**
	 * Current Setting ID
	 *
	 * @var string
	 */
	private $current_setting = '';
	/**
	 * Registered settings (by the class) indexed by id
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Fields and Methods defaults. Updated on set_defaults
	 *
	 * @var array
	 */
	private $defaults = array();

	/**
	 * WPCG_Customizer_Generator constructor.
	 *
	 * @param WP_Customize_Manager|null $customize
	 * @param array $args
	 */
	public function __construct( $customize = null, $args = array() ) {
		// default settings
		$defaults = array(
			'control_mask'          => $this->control_mask,
			'partial_selector_mask' => $this->partial_selector_mask,
		);

		$settings = self::parse_arguments( $defaults, $args );

		// update settings
		$this->set_wp_customize( $customize );
		$this->control_mask          = $settings['control_mask'];
		$this->partial_selector_mask = $settings['partial_selector_mask'];

		// Will get a real WP_Customize_Manager if not informed
		if ( ! $customize ) {
			add_action( 'customize_register', array( $this, 'set_wp_customize' ) );
		}

		// Add Kirki default config
		Kirki::add_config( $this->kirki_id, array(
			'capability'  => 'edit_theme_options',
			'option_type' => 'theme_mod',
		) );

	}

	/**
	 * Add new Theme Panel
	 *
	 * @param string $id ID or Panel Label
	 * @param array $args
	 * @param callable|null $callback
	 *
	 * @return WPCG_Customizer_Generator
	 */
	public function add_panel( $id = 'theme-panel', $args = array(), $callback = null ) {

		// default values
		$defaults = array(
			'title' => $id,
		);

		$args = self::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			self::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		);

		if ( $args['title'] === $id ) {
			$id = self::sanitize( $id );
		}

		// add new panel
		Kirki::add_panel( $id, $args );

		// update current panel
		$this->current_panel = $id;

		// execute callback
		return $this->execute( $callback );
	}

	/**
	 * Add Theme Section
	 *
	 * @param string $id ID or section Label
	 * @param array $args
	 * @param callable|null $callback
	 *
	 * @return WPCG_Customizer_Generator
	 */
	public function add_section( $id = 'theme-settings', $args = array(), $callback = null ) {

		// default values
		$defaults = array(
			'title' => $id,
			'panel' => $this->current_panel
		);

		$args = self::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			self::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		);

		if ( $args['title'] === $id ) {
			$id = self::sanitize( $id );
		}

		// add new panel
		Kirki::add_section( $id, self::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			self::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		) );

		// update current panel
		$this->current_section = $id;

		// execute callback
		return $this->execute( $callback );
	}

	/**
	 * Get the setting
	 *
	 * @param string $id
	 * @param bool $default Setting Default(if not set, uses the defined default)
	 *
	 * @return string
	 */
	public function get_setting( $id = null, $default = false ) {
		$id = $this->the_current_setting( $id );

		if ( false === $default && isset( $this->settings[ $id ] ) ) {
			$default = $this->settings[ $id ]['default'];
		}

		$this->set_current_field( $id );

		return get_theme_mod( $id, $default );

	}

	/**
	 * Output the setting using the defined function
	 *
	 * @param string $id
	 * @param bool $default Setting Default(if not set, uses the defined default)
	 */
	public function the_setting( $id = null, $default = false ) {
		$id     = $this->the_current_setting( $id );
		$render = array( $this, 'render_text' );
		if ( isset( $this->settings[ $id ], $this->settings[ $id ]['partial_refresh'][ $id ] ) ) {
			$render = $this->settings[ $id ]['partial_refresh'][ $id ]['render_callback'];
		}

		echo call_user_func( $render, $id );
	}

	/**
	 * return settings attributes
	 *
	 * @param string|array|null $setting The setting ID, or array of settings IDs, or NULL if the current Setting
	 *
	 * @return string
	 */
	public function get_setting_attributes( $setting = null ) {
		$setting = self::array_argument( $this->the_current_setting( $setting ) );

		return sprintf( $this->setting_attribute_mask, implode( ' ', $setting ) );
	}

	/**
	 * Output settings attributes
	 *
	 * @param string|array|null $setting The setting ID, or array of settings IDs, or NULL if the current Setting
	 */
	public function setting_attributes( $setting = null ) {
		echo $this->get_setting_attributes( $setting );
	}

	public function add( $id, $args = array() ) {
		$defaults = array(
			'type'            => 'text',
			'section'         => $this->current_section,
			'render_callback' => false,
			'shortcut'        => false,
			'partial_refresh' => array(),
			'js_vars'         => array(),
		);

		$args = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'type', 'label', 'default', 'description' ) )
		);

		if ( true === $args['partial_refresh'] ) {
			$args['partial_refresh'] = array();
//			$args['transport']       = 'postMessage';
			$args['partial_refresh'][ $id ] = array(
				'selector'        => sprintf( $this->partial_selector_mask, $id ),
				'render_callback' => $args['render_callback'] ? $args['render_callback'] : $this->get_render_callback( $args ),
			);
		} else if ( $args['shortcut'] ) {
			// add a fake partial, that return false. this way, will work as a edit shortcut only.
			$args['partial_refresh'][ sprintf( "%s-shortcut", $id ) ] = array(
				'selector'        => is_string( $args['shortcut'] ) ? $args['shortcut'] : sprintf( $this->partial_selector_mask, $id ),
				'render_callback' => '__return_false'
			);
		}

		$args['settings'] = $id;

		$this->settings[ $id ] = $args;

		unset( $args['render_callback'], $args['shortcut'] );

		$this->the_current_setting( $id );

		Kirki::add_field( $this->kirki_id, $args );

		return $this;

	}

	// Base Fields

	public function add_text_field( $id, $args = array() ) {
		$defaults = array(
			'type' => 'text',
		);

		return $this->add( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority' ) )
		) );
	}

	public function add_image_field( $id, $args = array() ) {
		$defaults = array(
			'type' => 'image'
		);

		return $this->add( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority', 'help' ) )
		) );
	}

	public function add_color_field( $id, $args = array() ) {
		$defaults = array(
			'type'    => 'color',
			'choices' => array(),
			'alpha'   => false
		);

		$args = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'alpha', 'description', 'priority' ) )
		);

		$args['choices']['alpha'] = $args['alpha'];
		unset( $args['alpha'] );

		return $this->add( $id, $args );
	}

	// Wrapped Fields

	public function add_color_text( $id, $args = array() ) {
		$defaults = $this->get_output( $id, 'color', array() );

		return $this->add_color_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'alpha', 'description', 'priority' ) )
		) );
	}

	public function add_image_background( $id, $args = array() ) {

		$defaults = $this->get_output( $id, 'background-image', array() );

		return $this->add_image_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority', 'help' ) )
		) );
	}

	public function add_color_background( $id, $args = array() ) {
		$defaults = $this->get_output( $id, 'background-color', array() );

		return $this->add_color_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'alpha', 'description', 'priority' ) )
		) );
	}

	public function add_text( $id, $args = array() ) {
		$defaults = array(
			'partial_refresh' => true,
		);

		return $this->add_text_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority' ) )
		) );
	}

	public function add_textarea( $id, $args = array() ) {
		$defaults = array(
			'type' => 'textarea'
		);

		return $this->add_text( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority' ) )
		) );
	}

	public function add_image( $id, $args = array() ) {

		$defaults = array(
			'partial_refresh' => true
		);

		return $this->add_image_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority', 'help' ) )
		) );
	}

	// Default Customizer Compatibility

	/**
	 * Add Default Customizer Setting
	 *
	 * @param string $id
	 * @param array $args
	 *
	 * @return $this
	 */
	public function add_setting( $id, $args = array() ) {

		$this->wp_customize->add_setting( $id, $args );

		$this->current_setting = $id;

		return $this;
	}

	/**
	 * Add Default Customizer Control
	 *
	 * @param string|bool $id
	 * @param array $args
	 *
	 * @return $this
	 */
	public function add_control( $id = true, $args = array() ) {

		// when true, the current setting will be used
		if ( $id === true ) {
			$id = $this->current_setting;
		}

		// if an custom controller
		if ( $id instanceof WP_Customize_Control ) {
			$this->wp_customize->add_control( $id );

			return $this;
		}

		$defaults = array(
			'title'    => $id,
			'settings' => '',
			'section'  => $this->current_section,
			'type'     => 'text'
		);

		$args = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'title', 'description', 'priority', 'settings' ) )
		);

		// if setting not passed, is presumed that the $id is the setting. The mask is used.
		if ( ! $args['settings'] ) {
			$args['settings'] = $id;
			$id               = sprintf( $this->control_mask, $id );
		}

		$this->wp_customize->add_control( $id, $args );

		return $this;
	}

	/**
	 * Add Default Customizer Partial
	 *
	 * @param bool $id
	 * @param array $args
	 *
	 * @return $this
	 */
	public function add_partial( $id = false, $args = array() ) {
		if ( ! $this->has_partial() ) {
			return $this;
		}

		$defaults = array(
			'render_callback' => 'text',
			'selector'        => sprintf( $this->partial_selector_mask, $id )
		);

		$args = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'render_callback', 'selector' ) )
		);

		$render = $this->get_render_callback( $args['render_callback'] );

		$args['render_callback'] = $render ? $render : $args['render_callback'];

		return $this;
	}

	// Internal and logic functions

	public function the_current_setting( $setting = null ) {
		if ( $setting ) {
			$this->current_setting = $setting;
		}

		return $this->current_setting;

	}

	public function set_wp_customize( $customize = null ) {
		if ( ! $customize ) {
			global $wp_customize;
			$customize = $wp_customize ? $wp_customize : $this->wp_customize;
		}
		$this->wp_customize = $customize;
	}

	public function set_current_field( $id ) {
		$this->current_setting = $id;
	}

	public function get_current_field() {
		return $this->current_setting;
	}

	// Helper Functions

	public function has_partial() {
		return isset( $this->wp_customize->selective_refresh );
	}

	private function get_render_callback( $settings = array() ) {
		if ( is_string( $settings ) ) {
			$settings = array( 'type' => $settings );
		}
		$type = $settings['type'];
		switch ( $type ) {
			case 'image':
				return array( $this, 'render_image' );
			case 'html':
			case 'code':
			case 'shortcode':
				return array( $this, 'render_html' );
			case 'text':
			case 'echo':
			default:
				return array( $this, 'render_text' );
		}
	}

	public function get_output( $id, $output = array(), $merge = false ) {
		// if passed an array of outputs
		if ( self::is_matrix( $output ) ) {
			$fields = array();
			foreach ( $output as $item ) {
				$fields[] = $this->get_output( $id, $item );
			}
			if ( false === $merge ) {
				return $fields;
			}

			return self::parse_arguments( array(
				'transport' => 'auto',
				'output'    => $fields
			), $merge );
		}

		$defaults = array(
			'element' => sprintf( $this->partial_selector_mask, $id ),
			'force'   => false,
			'suffix'  => '',
		);

		$output = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $output, array( 'property', 'units', 'force', 'value_pattern' ) )
		);
		if ( $output['force'] ) {
			$output['suffix'] .= ' !important';
		}
		unset( $output['force'] );

		if ( false === $merge ) {
			return $output;
		}

		return self::parse_arguments( array(
			'transport' => 'auto',
			'output'    => array( $output )
		), $merge );


	}

	public function get_selector( $id ) {
		return sprintf( $this->partial_selector_mask, $id );
	}

	/**
	 * @param callable|null $function
	 *
	 * @return $this
	 */
	private function execute( $function = null ) {
		if ( is_callable( $function ) ) {
			call_user_func( $function, $this );
		}

		return $this;
	}

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
		if ( ! self::is_indexed_array( $matrix ) ) {
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
	 * Parse indexed values
	 *
	 * @param array $values
	 * @param array $keys
	 *
	 * @return array
	 */
	static function parse_indexed_values( $values = array(), $keys = array() ) {
		if ( self::is_indexed_array( $values ) ) {
			return self::parse_indexed_array( $values, $keys );
		}

		return $values;
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

	public function get_partial_setting( $partial ) {
		return $this->get_setting( self::get_partial_id( $partial ) );
	}

	// Render Functions

	/**
	 * Text Partial Render
	 *
	 * @param WP_Customize_Partial $partial
	 *
	 * @return string|false
	 */
	public function render_text( $partial ) {
		return nl2br( $this->get_partial_setting( $partial ) );
	}

	/**
	 * Text Partial Render
	 *
	 * @param WP_Customize_Partial $partial
	 *
	 * @return string|false
	 */
	public function render_image( $partial ) {

		$value = $this->get_partial_setting( $partial );
		if ( ! $value ) {
			return null;
		}

		return sprintf( '<img src="%s">', $value );
	}

	/**
	 * HTML/Shortcode Partial Render
	 *
	 * @param WP_Customize_Partial $partial
	 *
	 * @return string|false
	 */
	public function render_html( $partial ) {
		return do_shortcode( $this->get_partial_setting( $partial ) );
	}

	/**
	 * Debug Partial
	 *
	 * @param WP_Customize_Partial $partial
	 */
	public function render_debug( $partial ) {
		var_dump( $partial );
	}
}