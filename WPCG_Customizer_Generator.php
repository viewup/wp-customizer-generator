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
	 * @var string
	 */
	private $partial_selector_mask = '[data-wp-setting="%s"]';

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

	public function __construct( $customize = null, $args = array() ) {
		global $wp_customize;
		// use global customizer if not set
		if ( ! $customize ) {
			$customize = $wp_customize;
		}

		// default settings
		$defaults = array(
			'control_mask'          => $this->control_mask,
			'partial_selector_mask' => $this->partial_selector_mask,
		);

		$settings = array_merge( $defaults, $args );


		// update settings
		$this->wp_customize          = $wp_customize;
		$this->control_mask          = $settings['control_mask'];
		$this->partial_selector_mask = $settings['partial_selector_mask'];

	}

	public function add_panel( $id = 'theme-panel', $args = array(), $callback = null ) {

		// default values
		$defaults = array(
			'title' => __( 'Theme' ),
		);

		// add new panel
		Kirki::add_panel( $id, self::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			self::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		) );

		// update current panel
		$this->current_panel = $id;

		// execute callback
		return $this->execute( $callback );
	}

	public function add_section( $id = 'theme-settings', $args = array(), $callback ) {

		// default values
		$defaults = array(
			'title' => __( 'Theme Settings' ),
			'panel' => $this->current_panel
		);

		// add new panel
		Kirki::add_section( $id, self::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			self::parse_indexed_arguments( $args, array( 'title', 'description', 'priority' ) )
		) );

		// update current panel
		$this->current_section = $id;

		// execute callback
		return $this->execute( $callback );
	}

	public function add_setting( $id, $args = array() ) {

		$this->wp_customize->add_setting( $id, $args );

		$this->current_setting = $id;

		return $this;
	}

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


	// Base Fields
	public function add( $id, $args = array() ) {
		$defaults = array(
			'type'            => 'text',
			'settings'        => $this->kirki_id,
			'label'           => $id,
			'section'         => $this->current_section,
			'render_callback' => false,
			'partial_refresh' => array()
		);

		$args = self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'type', 'label', 'default', 'description' ) )
		);

		if ( true === $args['partial_refresh'] ) {
			$args['partial_refresh'] = array(
				'default' => array(
					'selector'        => sprintf( $this->partial_selector_mask, $id ),
					'render_callback' => $args['render_callback'] ? $args['render_callback'] : $this->get_render_callback( $args ),
				),
			);
		}

		unset( $args['render_callback'] );

		Kirki::add_field( $id, $args );

		$this->settings[ $id ] = $args;

		$this->current_setting = $id;

		return $this;

	}

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
		$defaults = array(
			'output' => array(
				array(
					'element'  => sprintf( $this->partial_selector_mask, $id ),
					'property' => 'color',
				)
			),

		);

		return $this->add_color_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'alpha', 'description', 'priority' ) )
		) );
	}

	public function add_image_background( $id, $args = array() ) {
		$defaults = array(
			'output' => array(
				array(
					'element'  => sprintf( $this->partial_selector_mask, $id ),
					'property' => 'background-image',
				)
			),
		);

		return $this->add_image_field( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority', 'help' ) )
		) );
	}

	public function add_color_background( $id, $args = array() ) {
		$defaults = array(
			'output' => array(
				array(
					'element'  => sprintf( $this->partial_selector_mask, $id ),
					'property' => 'background-color',
				)
			),
		);

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

		return $this->add( $id, self::parse_arguments( $defaults,
			self::parse_indexed_arguments( $args, array( 'label', 'default', 'description', 'priority', 'help' ) )
		) );
	}

	// internal and logic functions

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
				return array( $this, 'render_text' );
		}

		return '';
	}

	private function execute( $function = null ) {
		if ( is_callable( $function ) ) {
			$function( $this );
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
		return self::parse_indexed_arguments( self::array_argument( $values ), $keys );
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

	// Render Functions

	/**
	 * Text Partial Render
	 *
	 * @param WP_Customize_Partial $partial
	 *
	 * @return string|false
	 */
	public function render_text( $partial ) {

		return get_theme_mod( self::get_partial_id( $partial ) );
	}

	/**
	 * Text Partial Render
	 *
	 * @param WP_Customize_Partial $partial
	 *
	 * @return string|false
	 */
	public function render_image( $partial ) {

		$value = get_theme_mod( self::get_partial_id( $partial ) );
		if ( ! $value ) {
			return false;
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

		return do_shortcode( self::get_partial_id( $partial ) );
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