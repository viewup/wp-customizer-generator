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
	 * The current editing type. Can be setting, panel or section.
	 *
	 * Used to modify the current element
	 *
	 * @var string
	 */
	private $current_type = 'setting';

	/**
	 * Registered settings (by the class) indexed by id
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Registered Divisions (panels and sections)
	 * @var array
	 */
	private $divisions = array( 'panels' => array(), 'sections' => array() );

	private $random_counter = 0;

	/**
	 * Array of saved fields
	 * @var array
	 */
	private $saved = array( 'panels' => array(), 'sections' => array(), 'fields' => array() );

	/**
	 * WPCG_Customizer_Generator constructor.
	 *
	 * @param WP_Customize_Manager|null $customize
	 * @param array $args
	 */
	public function __construct( $customize = null, $args = array() ) {
		// default settings
		$defaults = apply_filters( 'wpcg_init_defaults', array(
			'control_mask'          => $this->control_mask,
			'partial_selector_mask' => $this->partial_selector_mask,
		), $this );

		$settings = WPCG_Helper::parse_arguments( $defaults, $args );

		// update settings
		$this->set_wp_customize( $customize );
		$this->control_mask          = $settings['control_mask'];
		$this->partial_selector_mask = $settings['partial_selector_mask'];

		// Will get a real WP_Customize_Manager if not informed
		if ( ! $customize ) {
			add_action( 'customize_register', array( $this, 'set_wp_customize' ) );
		}

		// Add Kirki default config
		self::Kirki( 'add_config', $this->kirki_id, array(
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
		$args = WPCG_Helper::parse_arguments( array( 'title' => $id ),
			WPCG_Helper::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		);

		if ( $args['title'] === $id ) {
			$id = WPCG_Helper::sanitize( $id );
		}

		// add new panel to save
		$this->divisions['panels'][ $id ] = $args;

		// update current panel
		$this->current_panel = $id;

		// Update Current editing type
		$this->current_type = 'panel';

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

		$args = WPCG_Helper::parse_arguments( $defaults,
			// fix indexed arguments and non-array arguments
			WPCG_Helper::parse_indexed_arguments( $args, array( 'title', 'priority', 'description' ) )
		);

		if ( $args['title'] === $id ) {
			$id = WPCG_Helper::sanitize( $id );
		}

		// add new section to save
		$this->divisions['sections'][ $id ] = $args;

		// update current panel
		$this->current_section = $id;

		// Update Current editing type
		$this->current_type = 'section';

		// execute callback
		return $this->execute( $callback );
	}

	/// Edit methods

	public function set_argument( $name, $value, $id = null ) {
		$id = $this->the_current_setting( $id );

		// field not found
		if ( ! isset( $this->settings[ $id ] ) ) {
			return $this;
		}

		$this->settings[ $id ][ $name ] = $value;

		return $this;
	}

	public function push_argument( $name, $value, $id = null, $key = null ) {
		$id = $this->the_current_setting( $id );

		// field not found
		if ( ! isset( $this->settings[ $id ] ) ) {
			return $this;
		}
		if ( null === $key ) {
			$this->settings[ $id ][ $name ][] = $value;
		} else {
			$this->settings[ $id ][ $name ][ $key ] = $value;
		}

		return $this;
	}

	public function capability( $value = 'edit_theme_options', $id = null ) {
		return $this->set_argument( 'capability', $value, $id );
	}

	public function priority( $value = 10, $id = null ) {
		return $this->set_argument( 'priority', $value, $id );
	}

	public function condition( $setting = array(), $id = null ) {
		$defaults = array(
			'setting'  => '',
			'operator' => '!=',
			'value'    => null,
		);

		$setting = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $setting, array_keys( $defaults ) ) );

		return $this->push_argument( 'active_callback', $setting, $id );

	}

	public function set_choices( $value = array(), $id = null ) {
		return $this->set_argument( 'choices', $value, $id );
	}

	public function add_choice( $name, $value = null, $id = null ) {
		$value = ( null === $value ) ? $name : $value;

		return $this->push_argument( 'choices', $value, $id, $name );
	}

	public function set_default( $value, $id = null ) {
		return $this->set_argument( 'default', $value, $id );
	}

	public function label( $value, $id = null ) {
		return $this->set_argument( 'label', $value, $id );
	}

	public function type( $value, $id = null ) {
		return $this->set_argument( 'type', $value, $id );
	}

	public function description( $value, $id = null ) {
		return $this->set_argument( 'description', $value, $id );
	}

	public function tooltip( $value, $id = null ) {
		return $this->set_argument( 'tooltip', $value, $id );
	}

	public function multiple( $value = 1, $id = null ) {
		return $this->set_argument( 'multiple', $value, $id );
	}

	public function sanitize_callback( $value = 1, $id = null ) {
		return $this->set_argument( 'sanitize_callback', $value, $id );
	}

	public function transport( $type = 'auto', $id = null ) {
		return $this->set_argument( 'transport', $type, $id );
	}

	/**
	 * Add edit shortcut for an field
	 *
	 * Can be called multiple times (multiple selectors)
	 * useful when the field doesn't have an render but have some refference to edit it.
	 *
	 * NOTE: doesn't work with repeaters (kirki issue)
	 *
	 * @param string|null $selector - jQuery Selector
	 * @param string|null $id - Field ID
	 *
	 * @return $this|WPCG_Customizer_Generator
	 */
	public function shortcut( $selector = null, $id = null ) {
		$id          = $this->the_current_setting( $id );
		$selector    = is_string( $selector ) ? $selector : sprintf( $this->partial_selector_mask, $id );
		$shortcut_id = sprintf( "%s-shortcut", $id );

		// Kirki incompatibility
		if ( 'repeater' === $this->settings[ $id ]['type'] ) {
			return $this;
		}
		// new shortcut
		if ( ! isset( $this->settings[ $id ]['partial_refresh'][ $shortcut_id ] ) ) {
			$partial = array(
				'selector'        => $selector,
				'render_callback' => '__return_false'
			);

			return $this->push_argument( 'partial_refresh', $partial, $id, $shortcut_id );
		} else {
			// TODO: Validate selector before insert (no-repeat)
			if ( $selector != $this->settings[ $id ]['partial_refresh'][ $shortcut_id ]['selector'] ) {
				$this->settings[ $id ]['partial_refresh'][ $shortcut_id ]['selector'] .= "," . $selector;
			}
		}

		return $this;

	}

	public function partial_refresh( $args = array(), $key = null, $id = null ) {
		$id  = $this->the_current_setting( $id );
		$key = $this->get_random_key( 'partial-%s', $key );

		$defaults = array(
			'render_callback' => '',
			'selector'        => $this->get_selector( $id )
		);


		$args = WPCG_Helper::parse_arguments( $defaults, WPCG_Helper::parse_indexed_arguments( $args, array_keys( $defaults ) ) );


		return $this->push_argument( 'partial_refresh', $args, $id, $key );
	}

	public function output( $args = array(), $id = null ) {
		$id = $this->the_current_setting( $id );

		$defaults = array(
			'property' => 'color',
			'units'    => '',
			'element'  => $this->get_selector( $id ),
			'prefix'   => '',
			'suffix'   => '',
		);

		$args = WPCG_Helper::parse_arguments( $defaults, WPCG_Helper::parse_indexed_arguments( $args, array_keys( $defaults ) ) );

		return $this->push_argument( 'output', $args )->transport();
	}

	public function js_vars( $args = array(), $id = null ) {
		$id       = $this->the_current_setting( $id );
		$defaults = array(
			'function' => 'html',
			'property' => '',
			'element'  => $this->get_selector( $id )
		);
		$args     = WPCG_Helper::parse_arguments( $defaults, WPCG_Helper::parse_indexed_arguments( $args, array_keys( $defaults ) ) );

		return $this->push_argument( 'js_vars', $args, $id )->transport( 'postMessage' );
	}

	/**
	 * Save all fields
	 *
	 * need to be done after insertion. If the hook is used, is automatically done.
	 *
	 * @return $this
	 */
	public function save() {
		$this->save_panels();
		$this->save_sections();
		$this->save_fields();

		return $this;
	}

	// Base Fields
	public function add( $id, $args = array() ) {
		$defaults = apply_filters( 'wpcg_add_defaults', array(
			'type'            => 'text',
			'section'         => $this->current_section,
			'render'          => false,
			'shortcut'        => false,
			'partial'         => array(),
			'partial_refresh' => array(),
			'js_vars'         => array(),
			'output'          => array(),
			'active_callback' => array(),
		), $this, $args );

		$args = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $args, array( 'type', 'label', 'default', 'description' ) )
		);

		// extract values
		$shortcut = $args['shortcut'];

		if ( $args['partial'] || $args['render'] ) {
			$args['partial_refresh'][ $id ] = array(
				'selector'        => sprintf( $this->partial_selector_mask, $id ),
				'render_callback' => $this->get_render_callback( $args ),
			);
		}

		$args['settings'] = $id;

		// remove unecessary fields
		unset( $args['render_callback'], $args['shortcut'], $args['partial'], $args['render'] );

		// update current setting
		$this->the_current_setting( $id );

		// Update Current editing type
		$this->current_type = 'setting';

		// Add field to save
		$this->settings[ $id ] = apply_filters( 'wpcg_add', $args, $this );

		/// automatic edits and inserts
		// shortcut
		if ( $shortcut ) {
			$this->shortcut( $shortcut );
		}

		return $this;
	}

	private function add_field( $id, $args = array(), $defaults = array(), $shortcut = array() ) {
		return $this->add( $id,
			self::parse_field_args( $args, $defaults, $shortcut )
		);
	}

	/**
	 * Add custom message on the current/selected section
	 *
	 * @param string|array $args Message or settings array
	 * @param null $id Id of message
	 *
	 * @return WPCG_Customizer_Generator
	 */
	public function add_message( $args = array(), $id = null ) {
		$id = $this->get_random_message_id( $id );

		return $this->add_field( $id, $args, 'custom', array( 'default', 'label', 'priority', 'description' ) );
	}

	// Text Fields
	public function add_text( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'text' )
		            ->partial_refresh( array( $this->get_render_callback( 'text' ) ), $id )
		            ->js_vars();
	}

	public function add_textarea( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'textarea' )
		            ->partial_refresh( array( $this->get_render_callback( 'editor' ) ), $id );
	}

	public function add_editor( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'editor' )
		            ->partial_refresh( array( $this->get_render_callback( 'editor' ) ), $id );
	}

	public function add_code( $id, $args = array() ) {
		$args = self::parse_field_args( $args, array(
			'type'     => 'code',
			'language' => 'html',
			'theme'    => 'elegant',
			'height'   => null,
		),
			array( 'label', 'default', 'language', 'description', 'priority' ),
			array( 'language', 'theme', 'height' ) );

		return $this->add( $id, $args );
	}

	public function add_number( $id, $args = array() ) {
		$args = self::parse_field_args( $args, array(
			'type' => 'number',
			'min'  => - 999999999,
			'max'  => 999999999,
			'step' => 1,
		),
			array( 'label', 'default', 'min', 'max', 'step', 'description', 'priority' ),
			array( 'min', 'max', 'step' ) );

		return $this->add( $id, $args );
	}

	public function add_dimension( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'dimension' );
	}

	// Choices Fields
	private function add_choices_field( $id, $args = array(), $defaults = array(), $shortcut = array() ) {
		$shortcut = $shortcut ? $shortcut : array( 'choices', 'label', 'default', 'description', 'priority', 'help' );
		$defaults = WPCG_Helper::parse_arguments( array(
			'type'    => 'select',
			'choices' => array()
		), WPCG_Helper::parse_indexed_arguments( $defaults, array( 'type', 'multiple' ) ) );

		return $this->add_field( $id, $args, $defaults, $shortcut );
	}

	public function add_select( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, array( 'select', 1 ) );
	}

	public function add_radio( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'radio' );
	}

	public function add_multicheck( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'multicheck' );
	}

	public function add_radio_image( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'radio-image' );
	}

	public function add_radio_buttonset( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'radio-buttonset' );
	}

	public function add_palette( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'palette' );
	}

	public function add_color_palette( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'color-palette' );
	}

	public function add_sortable( $id, $args = array() ) {
		return $this->add_choices_field( $id, $args, 'sortable' );
	}

	// Boolean Fields
	public function add_checkbox( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'checkbox' );
	}

	public function add_toggle( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'toggle' );
	}

	public function add_switch( $id, $args = array() ) {

		$args = self::parse_field_args( $args, 'switch',
			array( 'label', 'default', 'on', 'off', 'description', 'priority' ), array( 'on', 'off' ) );

		return $this->add( $id, $args );
	}

	// Other Fields

	public function add_image( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'image' )
		            ->partial_refresh( array( $this->get_render_callback( 'image' ) ), $id );
	}

	public function add_image_background( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'image' )
		            ->output( 'background-image' );
	}

	public function add_color( $id, $args = array() ) {
		$args = self::parse_field_args( $args, 'color',
			array( 'label', 'default', 'alpha', 'description', 'priority' ), 'alpha' );

		return $this->add( $id, $args );
	}

	public function add_color_text( $id, $args = array() ) {
		return $this->add_color( $id, $args )->output( 'color' );
	}

	public function add_color_background( $id, $args = array() ) {
		return $this->add_color( $id, $args )->output( 'background-color' );
	}

	public function add_dashicons( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'dashicons' );
	}

	public function add_upload( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'upload' );
	}

	public function add_dropdown_pages( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'dropdown-pages' );
	}

	public function add_slider( $id, $args = array() ) {
		$args = self::parse_field_args( $args, 'slider',
			array( 'label', 'default', 'min', 'max', 'step', 'description', 'priority' ),
			array( 'min', 'max', 'step' ) );

		return $this->add( $id, $args );
	}

	public function add_spacing( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'spacing' );
	}

	public function add_typography( $id, $args = array() ) {
		return $this->add_field( $id, $args, 'typography' );
	}

	public function add_multicolor( $id, $args = array() ) {
		$args = self::parse_field_args( $args, 'multicolor',
			array( 'label', 'default', 'link', 'hover', 'active', 'description', 'priority' ),
			array( 'link', 'hover', 'active' ) );

		return $this->add( $id, $args );
	}

	/// Wrapped Fields

	/// Save methods
	public function save_panels() {
		$panels = $this->divisions['panels'];
		foreach ( $panels as $id => $panel ) {
			if ( ! isset( $this->saved['panels'][ $id ] ) || ! $this->saved['panels'][ $id ] ) {
				self::Kirki( 'add_panel', $id, $panel );
				$this->saved['panels'][ $id ] = true;
			}
		}

		return $this;
	}

	public function save_sections() {
		$sections = $this->divisions['sections'];
		foreach ( $sections as $id => $section ) {
			if ( ! isset( $this->saved['sections'][ $id ] ) || ! $this->saved['sections'][ $id ] ) {
				self::Kirki( 'add_section', $id, $section );
				$this->saved['sections'][ $id ] = true;
			}
		}

		return $this;
	}

	public function save_fields() {
		$fields = $this->settings;
		foreach ( $fields as $id => $field ) {
			if ( ! isset( $this->saved['fields'][ $id ] ) || ! $this->saved['fields'][ $id ] ) {
				self::Kirki( 'add_field', $this->kirki_id, $field );
				$this->saved['fields'][ $id ] = true;
			}
		}

		return $this;
	}
	/// Getters and renders

	/**
	 * Get the setting
	 *
	 * @param string $id
	 * @param bool $default Setting Default(if not set, uses the defined default)
	 *
	 * @return mixed
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
	 */
	public function the_setting( $id = null ) {
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
		$setting = WPCG_Helper::array_argument( $this->the_current_setting( $setting ) );

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

	// Internal and logic functions

	public function the_current_setting( $setting = null ) {
		if ( $setting ) {
			// avoid error when multiple settings given
			$this->current_setting = explode( ' ', $setting )[0];
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

	/**
	 * Parse field args to make field type
	 *
	 * @param array $args - user args
	 * @param array|string $defaults - the field default or type
	 * @param array|true $shortcut - Field shortcut or TRUE to use defaults as shortcut
	 * @param string|array $choices - Custom Choice(s) to insert in 'choices' key.
	 *
	 * @return array parsed field args
	 */
	private static function parse_field_args( $args = array(), $defaults = array(), $shortcut = array(), $choices = array() ) {
		$defaults = WPCG_Helper::parse_arguments( array(
			'type' => 'text',
		), WPCG_Helper::parse_indexed_arguments( $defaults, array( 'type' ) ) );

		$shortcut = $shortcut ? $shortcut : array( 'label', 'default', 'description', 'priority', 'help' );

		if ( true === $shortcut ) {
			$shortcut = array_keys( $defaults );
		}

		$args = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $args, $shortcut )
		);

		// custom choices
		if ( $choices ) {
			// initialize choices
			$args['choices'] = $args['choices'] ? $args['choices'] : array();
			$args['choices'] = WPCG_Helper::parse_arguments(
				WPCG_Helper::extract_values( $choices, $args ), $args['choices'] );
			foreach ( $choices as $field ) {
				unset( $args[ $field ] );
			}
		}

		return $args;
	}

	private static function Kirki( $method, $arg1 = null, $arg2 = null ) {
		if ( class_exists( 'WPCG_Kirki' ) ) {
			call_user_func( "WPCG_Kirki::{$method}", $arg1, $arg2 );
		}
	}

	private function get_random_key( $pattern = 'key-%s', $id = null ) {
		if ( $id ) {
			return $id;
		}
		$this->random_counter ++;

		return sprintf( $pattern, $this->random_counter );
	}

	private function get_random_message_id( $id = null ) {
		return $this->get_random_key( 'message-%s', $id );
	}

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
			case 'code':
			case 'shortcode':
			case 'editor':
				return array( $this, 'render_html' );
			case 'html':
			case 'text':
			case 'echo':
			default:
				return array( $this, 'render_text' );
		}
	}

	public function get_output( $id, $output = array(), $merge = false ) {
		// if passed an array of outputs
		if ( WPCG_Helper::is_matrix( $output ) ) {
			$fields = array();
			foreach ( $output as $item ) {
				$fields[] = $this->get_output( $id, $item );
			}
			if ( false === $merge ) {
				return $fields;
			}

			return WPCG_Helper::parse_arguments( array(
				'transport' => 'auto',
				'output'    => $fields
			), $merge );
		}

		$defaults = array(
			'element' => sprintf( $this->partial_selector_mask, $id ),
			'force'   => false,
			'suffix'  => '',
		);

		$output = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $output, array( 'property', 'units', 'force', 'value_pattern' ) )
		);
		if ( $output['force'] ) {
			$output['suffix'] .= ' !important';
		}
		unset( $output['force'] );

		if ( false === $merge ) {
			return $output;
		}

		return WPCG_Helper::parse_arguments( array(
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


	public function get_partial_setting( $partial ) {
		return $this->get_setting( WPCG_Helper::get_partial_id( $partial ) );
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
		return do_shortcode( shortcode_unautop( $this->get_partial_setting( $partial ) ) );
	}

	/**
	 * Debug Partial
	 *
	 * @param WP_Customize_Partial $partial
	 */
	public function render_debug( $partial ) {
		var_dump( $partial );
	}

	/// Default Customizer Compatibility

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

		$args = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $args, array( 'title', 'description', 'priority', 'settings' ) )
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

		$args = WPCG_Helper::parse_arguments( $defaults,
			WPCG_Helper::parse_indexed_arguments( $args, array( 'render_callback', 'selector' ) )
		);

		$render = $this->get_render_callback( $args['render_callback'] );

		$args['render_callback'] = $render ? $render : $args['render_callback'];

		return $this;
	}

	// Initialize and hook methods

	/**
	 * Get Class Instance
	 *
	 * @param null $customize
	 * @param array $args
	 *
	 * @return WPCG_Customizer_Generator
	 */
	public static function get_instance( $customize = null, $args = array() ) {
		return new WPCG_Customizer_Generator( $customize, $args );
	}

	/**
	 * Main Initializer
	 *
	 * initialize an global instance
	 */
	public static function init() {
		global $wpcg_customize;

		$wpcg_customize = self::get_instance();

		do_action( 'wpcg_customize_register', $wpcg_customize );

		$wpcg_customize->save();
	}

	private static function var_export( $var = null ) {
		if ( is_array( $var ) ) {
			foreach ( $var as $key => $value ) {
				if ( ! $value && $value !== false ) {
					unset( $var[ $key ] );
				}
			}
			ksort( $var );
		}
		ob_start();
		var_export( $var );

		return ob_get_clean();
	}

	/**
	 * Export kirki code function
	 */
	public function export() {
		$export = '<h1>exported WPCG code:</h1>
<pre style="background: #e8e8e8; padding: 1em; max-height: calc(100vh - 300px); overflow: auto;" onclick="prompt(\'Copy to clipboard.\', this.innerText)">
&lt;?php
';
		// panels
		$panels = $this->divisions['panels'];
		if ( count( $panels ) ) {
			$export .= "\n/**\n * Panels\n */\n";
			foreach ( $panels as $id => $panel ) {
				$id     = self::var_export( $id );
				$code   = self::var_export( $panel );
				$export .= "Kirki::add_panel( {$id}, $code);\n\n";
			}
		}

		// sections
		$sections = $this->divisions['sections'];
		if ( count( $sections ) ) {
			$export .= "\n/**\n * Sections\n */\n";
			foreach ( $sections as $id => $section ) {
				$id     = self::var_export( $id );
				$code   = self::var_export( $section );
				$export .= "Kirki::add_section( {$id}, $code);\n\n";
			}
		}

		$fields = $this->settings;
		if ( count( $sections ) ) {
			$export .= "\n/**\n * Fields\n */\n";
			foreach ( $fields as $id => $field ) {
				$id     = self::var_export( $id );
				$code   = self::var_export( $field );
				$export .= "Kirki::add_field( {$id}, $code);\n\n";
			}
		}

		$export .= '</pre><small>Click to copy to clipboard. <br /><strong>Note:</strong>class instances may have been exported.</small>';
		wp_die( $export, 'Exports WPCG code' );
	}
}
