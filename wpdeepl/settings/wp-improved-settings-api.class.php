<?php
namespace WP_Improved_Settings;
/**
 * Abstract Settings API Class
 * From abstract-wc-settings-api.php
 *
 *
 * @package WP_Improved_Settings
 * @version 20200320
 *
 * 20200320 ajout des setting en tableau
 *  * 20190705 : default value for text field
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Improved_Settings_API class.
 */
if ( !class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' ) ) {
class WC_Improved_Settings_API {
	/**
	 * The plugin ID. Used for option names.
	 *
	 * @var string
	 */
	public $plugin_id = 'woocommerce_';

	/**
	 * ID of the class extending the settings API. Used in option names.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Validation errors.
	 *
	 * @var array of strings
	 */
	public $errors = array();

	/**
	 * Setting values.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Form option fields.
	 *
	 * @var array
	 */
	public $form_fields = array();

	/**
	 * The posted settings data. When empty, $_POST data will be used.
	 *
	 * @var array
	 */
	protected $data = array();

	public $settingsStructure = array();
	
	public function __construct( $plugin_id , $settingsStructure ) {
		$this->plugin_id = $plugin_id .'_';
		$this->settingsStructure = $settingsStructure;
		$this->init_form_fields();
		//add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'process_admin_options' ) );
//				add_action( 'woocommerce_update_options', array( $this, 'test_action' ) );
	}

/**
	 * Initialise settings form fields.
	 *
	 * Add an array of fields to be displayed on the gateway's settings screen.
	 *
	 * @since 1.0.0
	 */


	public function init_form_fields() {
		if ( !$this->settingsStructure || !count($this->settingsStructure) ) {
			return false;
		}
		//plouf( $this->settingsStructure );die('okozerkozerk');
		foreach ( $this->settingsStructure as $tab_id => $tab_data ) {
			foreach ( $tab_data['sections'] as $section_id => $section_data ) {
				if( isset( $section_data['fields'] ) ) foreach ( $section_data['fields'] as $field ) {
					$this->id = $tab_id;
					$field_key = $this->get_field_key( $field['id'] );

					$field['tab'] = $tab_id;
					$field['section'] = $section_id;
					if ( isset( $this->form_fields[$field_key] ) ) {
						// happens on parameter[\d]
						continue;
					}
					$this->form_fields[$field_key] = $field;
				}
			}
		}
	}

	function process_admin_options() {
		$post_data = $this->get_post_data();

		$current_tab = $post_data['tab'];

		if ( method_exists( $this, 'before_save' ) ) {
			$this->before_save();
		}

		//plouf($this->form_fields, "fields");		plouf($post_data, "POST");		die('oaz4ea6zea4e6a846ek');


		foreach ( $this->form_fields as $field_key => $field ) {
			if ($field['tab'] != $current_tab) {
				continue;
			}

			//echo "\n tab ok ... key $field_key ";			plouf($post_data[$field_key]);
			if ( isset( $post_data[$field_key] ) ) {
				$field_value = $post_data[$field_key];

				if ( $field['type'] == 'checkbox' && $field_value == 1 ) {
					$field_value = 'yes';
				}
				if ( $field['type'] == 'array' ) {
					//plouf($post_data);
					foreach ( $field_value as $i => $field_input ) {
						if ( strlen( implode( '', $field_input )) == 0 ) {
							unset( $field_value[$i] );
						}
					}

				}
				if ( $field['type'] == 'textarea' || $field['type'] == 'text' ) {
					$field_value = stripslashes( $field_value );
				}
				update_option( $field_key, $field_value );
			}
			else {
				update_option($field_key, false);
			}
		}

		//plouf($post_data, " post data");				plouf( $this->form_fields );die('okaz8e7aze7a57ea65e');

		if ( method_exists( $this, 'on_save' ) ) {
			$this->on_save();
		}
		//plouf( $post_data );				plouf( $this->form_fields );				die( 'okzeprjio' );
	}

/**
 * Prefix key for settings.
 *
 * @param string $key Field key.
 * @return string
 */
	public function get_field_key( $key ) {
		//echo "<br /> KEY de $key = " . $this->plugin_id . $this->id . '_' . $key;
			//return $this->plugin_id . $this->id . '_' . $key;

		if ( preg_match('#^([\w_]+)\[\d+\]$#', $key, $match ) ) {
			$key = $match[1];
		}
		return $this->plugin_id . $key;
	//	return $this->plugin_id . $this->id . '_' . $key;
	}

	public function get_sub_field_key( $key ) {
		if ( preg_match('#^([\w_]+)\[(\d+)\]$#', $key, $match ) ) {
			return $match[2];
		}
		return false;

	}
	public function get_raw_field_key( $key ) {
		return $this->plugin_id . $key;
	}

	public function update_option( $key, $value = '' ) {
		//echo "\n UPDATING '$key', OPTION = '" . $this->get_field_key( $key ) . "' avec VALUE = '" . $value ."'";			die( 'ozaezeaz4e68a4z4e6ek' );
		/*	if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		$this->settings[ $key ] = $value;

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );*/
		return update_option( $this->get_field_key( $key ), $value );
	}

/**
 * Get option from DB.
 *
 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
 *
 * @param string $key Option key.
 * @param mixed $empty_value Value when empty.
 * @return string The value specified for the option or a default value for the option.
 */

	public function get_option( $key, $empty_value = null ) {
		//echo "\n<br /> vALUE de '$key' ( option = '" . $this->get_field_key( $key ) . '" ) = "' . get_option( $this->get_field_key( $key ) ).'"';
		return get_option( $this->get_field_key( $key ) );

		/*	if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->settings[ $key ] ) ) {
			$form_fields = $this->get_form_fields();
			$this->settings[ $key ] = isset( $form_fields[ $key ] ) ? $this->get_field_default( $form_fields[ $key ] ) : '';
		}

		if ( ! is_null( $empty_value ) && '' === $this->settings[ $key ] ) {
			$this->settings[ $key ] = $empty_value;
		}

		return $this->settings[ $key ];*/
	}

	public function generate_radio_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'label' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php
					$stored_value = $this->get_option( $key );
					if ( !$stored_value && isset( $data['default'] ) ) {
						$stored_value = $data['default'];
					}

					if ( isset( $data['values'] ) ) foreach ( $data['values'] as $value => $label ) :
						//echo " V $value / S $stored_value";

					 ?>
					<input <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="radio" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ) . '_' . sanitize_title( $value );?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr($value); ?>" <?php checked( $stored_value, $value ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $label ); ?></label>
					<br />
				<?php endforeach; ?>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	public function generate_raw_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'label' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php
					$stored_value = $this->get_option( $key );
					if ( !$stored_value && isset( $data['default'] ) ) {
						$stored_value = $data['default'];
					}

					echo ( $data['raw_html'] ); ?>
					<br/>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	/**
	 * Get the form fields after they are initialized.
	 *
	 * @return array of options
	 */
	public function get_form_fields() {
		return apply_filters( 'woocommerce_settings_api_form_fields_' . $this->id, array_map( array( $this, 'set_defaults' ), $this->form_fields ) );
	}

	/**
	 * Set default required properties for each field.
	 *
	 * @param array $field Setting field array.
	 * @return array
	 */
	protected function set_defaults( $field ) {
		if ( ! isset( $field['default'] ) ) {
			$field['default'] = '';
		}
		return $field;
	}

	/**
	 * Output the admin options table.
	 */
	public function admin_options() {
		echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>';
	}



	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . $this->id . '_settings';
	}

	/**
	 * Get a fields type. Defaults to "text" if not set.
	 *
	 * @param array $field Field key.
	 * @return string
	 */
	public function get_field_type( $field ) {
		return empty( $field['type'] ) ? 'text' : $field['type'];
	}

	/**
	 * Get a fields default value. Defaults to "" if not set.
	 *
	 * @param array $field Field key.
	 * @return string
	 */
	public function get_field_default( $field ) {
		return empty( $field['default'] ) ? '' : $field['default'];
	}

	/**
	 * Get a field's posted and validated value.
	 *
	 * @param string $key Field key.
	 * @param array $field Field array.
	 * @param array $post_data Posted data.
	 * @return string
	 */
	public function get_field_value( $key, $field, $post_data = array() ) {
		$type = $this->get_field_type( $field );
		$field_key = $this->get_field_key( $key );
		$post_data = empty( $post_data ) ? $_POST : $post_data; // WPCS: CSRF ok, input var ok.
		$value = isset( $post_data[ $field_key ] ) ? $post_data[ $field_key ] : null;

		if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			return call_user_func( $field['sanitize_callback'], $value );
		}

		// Look for a validate_FIELDID_field method for special handling.
		if ( is_callable( array( $this, 'validate_' . $key . '_field' ) ) ) {
			return $this->{'validate_' . $key . '_field'}( $key, $value );
		}

		// Look for a validate_FIELDTYPE_field method.
		if ( is_callable( array( $this, 'validate_' . $type . '_field' ) ) ) {
			return $this->{'validate_' . $type . '_field'}( $key, $value );
		}

		// Fallback to text.
		return $this->validate_text_field( $key, $value );
	}

	/**
	 * Sets the POSTed data. This method can be used to set specific data, instead of taking it from the $_POST array.
	 *
	 * @param array $data Posted data.
	 */
	public function set_post_data( $data = array() ) {
		$this->data = $data;
	}

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 *
	 * @return array
	 */
	public function get_post_data() {
		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			return $this->data;
		}

		return $_POST; // WPCS: CSRF ok, input var ok.
	}

	/**
	 * Update a single option.
	 *
	 * @since 3.4.0
	 * @param string $key Option key.
	 * @param mixed $value Value to set.
	 * @return bool was anything saved?
	 */

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	/*
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		//plouf($post_data);		die('ijzeirj');

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
*/
	/**
	 * Add an error message for display in admin on save.
	 *
	 * @param string $error Error message.
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Get admin error messages.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Display admin error messages.
	 */
	public function display_errors() {
		if ( $this->get_errors() ) {
			echo '<div id="woocommerce_errors" class="error notice is-dismissible">';
			foreach ( $this->get_errors() as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Initialise Settings.
	 *
	 * Store all settings in a single database entry
	 * and make sure the $settings array is either the default
	 * or the settings stored in the database.
	 *
	 * @since 1.0.0
	 * @uses get_option(), add_option()
	 */
	public function init_settings() {
		$this->settings = get_option( $this->get_option_key(), null );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$form_fields = $this->get_form_fields();
			$this->settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}

	/**
	 * Generate Settings HTML.
	 *
	 * Generate the HTML for the fields on the "settings" screen.
	 *
	 * @param array $form_fields ( default: array() ) Array of form fields.
	 * @param bool $echo Echo or return.
	 * @return string the html for the settings
	 * @since 1.0.0
	 * @uses method_exists()
	 */
	public function generate_settings_html( $form_fields = array(), $echo = true ) {
		if ( empty( $form_fields ) ) {
			//$form_fields = $this->get_form_fields();
		}

		//plouf($form_fields, " form fields");

		$html = '';
		foreach ( $form_fields as $field ) {
			$type = $this->get_field_type( $field );
			//plouf($field, " DONC TYPE = '$type'");

			if ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
				$html .= $this->{'generate_' . $type . '_html'}( $field['id'], $field );
			} else {
				$html .= $this->generate_text_html( $field['id'], $field );
			}
		}

		if ( $echo ) {
			// not escaped
			echo ( $html );
		} else {
			return $html;
		}
	}

	/**
	 * Get HTML for tooltips.
	 *
	 * @param array $data Data for the tooltip.
	 * @return string
	 */
	public function get_tooltip_html( $data ) {
		if ( true === $data['desc_tip'] ) {
			$tip = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$tip = $data['desc_tip'];
		} else {
			$tip = '';
		}

		return $tip ? $tip : '';
	}

	/**
	 * Get HTML for descriptions.
	 *
	 * @param array $data Data for the description.
	 * @return string
	 */
	public function get_description_html( $data ) {
		if ( true === $data['desc_tip'] ) {
			$description = '';
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
		} else {
			$description = '';
		}

		return $description ? '<p class="description">' . wp_kses_post( $description ) . '</p>' . "\n" : '';
	}

	/**
	 * Get custom attributes.
	 *
	 * @param array $data Field data.
	 * @return string
	 */
	public function get_custom_attribute_html( $data ) {
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) ) {
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		return implode( ' ', $custom_attributes );
	}

	/**
	 * Generate Text Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_text_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'default'	=> false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php
					if ( esc_attr( $this->get_option( $key ) ) ) {
						echo esc_attr( $this->get_option( $key ) );
					}
					elseif ( $data['default'] ) {
						// not escaped
						echo ( $data['default'] );
					} ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Text Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_array_html( $key, $data ) {

		$field_key = $this->get_field_key( $key );
		//plouf($data, "data pzeijezpi");
		//plouf($key, "key, field key $field_key");
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'default'	=> false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );


		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<?php foreach ( $data['keys'] as $input_key	=> $input_type ) :
				$main_class = "input-" . $input_type;
				$main_type = $input_type;
				if ( $main_type == 'decimal' ) {
					$main_type = 'text';
				}
				?>

			<td class="forminp <?php echo sanitize_key( $field_key ); ?> <?php echo sanitize_key( $field_key ) .'_' . $input_key; ?>">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="<?php echo esc_attr( $main_class );; ?> regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $input_type ); ?>" name="<?php echo esc_attr( $field_key ); ?>[<?php echo esc_attr( $data['index'] ); ?>][<?php echo esc_attr( $input_key );  ?>]" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php
					if ( isset( $data['values'][$input_key] ) ) {
						echo esc_attr( $data['values'][$input_key] );
					}
					elseif ( $data['default'] ) {
						echo esc_attr( $data['default'] );
					} ?>" placeholder="<?php echo isset( $data['placeholders'][$input_key] ) ? esc_attr( $data['placeholders'][$input_key] ) : ''; ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
	
			</td>
		<?php endforeach; ?>
			<td><a href="#" class="delete_row" onclick="jQuery(this).parent('td').parent('tr').remove(); return false;"><?php _e('Delete' ); ?></a></td>
		</tr>
		<?php

		return ob_get_clean();
	}
	/**
	 * Generate Price Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_price_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="wc_input_price input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $this->get_option( $key ) ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Decimal Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_decimal_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="wc_input_decimal input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( wc_format_localized_decimal( $this->get_option( $key ) ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Password Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_password_html( $key, $data ) {
		$data['type'] = 'password';
		return $this->generate_text_html( $key, $data );
	}

	/**
	 * Generate Color Picker Input HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_color_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<span class="colorpickpreview" style="background:<?php echo esc_attr( $this->get_option( $key ) ); ?>;">&nbsp;</span>
					<input class="colorpick <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> />
					<div id="colorPickerDiv_<?php echo esc_attr( $field_key ); ?>" class="colorpickdiv" style="z-index: 100; background: #eee; border: 1px solid #ccc; position: absolute; display: none;"></div>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Textarea HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_textarea_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<textarea rows="3" cols="20" class="input-text wide-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?>><?php echo $this->get_option( $key ) ? esc_textarea( $this->get_option( $key ) ) : esc_attr( $data['placeholder']); ?></textarea>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Checkbox HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_checkbox_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'label' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<label for="<?php echo esc_attr( $field_key ); ?>">
					<input <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="1" <?php checked( $this->get_option( $key ), 'yes' ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?> /> <?php echo wp_kses_post( $data['label'] ); ?></label><br/>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Select HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_select_html( $key, $data ) {
		$field_key = $this->get_raw_field_key( $key );
		$sub_field_key = $this->get_sub_field_key( $key );
		$selected_value = $this->get_option($key);
		if ( $sub_field_key ) {
			$selected_value = $selected_value[$sub_field_key];
		}
		//plouf($selected_value, " seleizehri pour key $key / field key $field_key");
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
			'options' => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		//plouf($data, "pour key $key");


		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?>>
						<?php foreach ( ( array ) $data['options'] as $option_key => $option_value ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( ( string ) $option_key, esc_attr( $selected_value ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data );  ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Multiselect HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_multiselect_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => array(),
			'select_buttons' => false,
			'options' => array(),
		);

		$data = wp_parse_args( $data, $defaults );
		$value = ( array ) $this->get_option( $key, array() );

		//plouf($data['options'], 'options');

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select multiple="multiple" class="multiselect <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>[]" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data );  ?>>
						<?php foreach ( ( array ) $data['options'] as $option_key => $option_value ) : ?>
							<?php if ( is_array( $option_value ) ) : ?>
								<optgroup label="<?php echo esc_attr( $option_key ); ?>">
									<?php foreach ( $option_value as $option_key_inner => $option_value_inner ) : ?>
										<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( ( string ) $option_key_inner, $value, true ), true ); ?>><?php echo esc_attr( $option_value_inner ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php else : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( ( string ) $option_key, $value, true ), true ); ?>><?php echo esc_attr( $option_value ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data );  ?>
					<?php if ( $data['select_buttons'] ) : ?>
						<br/><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'default' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'default' ); ?></a>
					<?php endif; ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	function generate_file_upload_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'class' => '',
			'page'	=> $this->option_page,
			'action'	=> '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:'); ?></p>
			<p><strong><?php echo esc_html( $upload_dir['error'] ); ?></strong></p></div><?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-adr-form" method="post" class="wp-upload-form" action="?page='<?php echo esc_attr( $data['page'] ); ?>'">

			<input type="hidden" name="page" value="<?php echo esc_attr( $data['page'] ); ?>" />
			<h3><?php echo esc_html( $data['title'] ); ?></h3>
			<p>
			<label for="filename"><?php _e( 'Choose a file from your computer:' ); ?></label> (<?php printf( __('Maximum size: %s' ), $size ); ?>)
			<input type="file" id="filename" name="filename" size="25" />
			</p>

			<input type="hidden" name="action" value="<?php echo esc_attr( $data['action'] ) ; ?>" />
			<input type="hidden" name="max_file_size" value="<?php echo esc_attr( $bytes ); ?>" />
			</p>
			<?php submit_button( __('Upload file and import'), 'primary' ); ?>
			</form>
			<?php
				endif;

		return ob_get_clean();
	}

	/**
	 * Generate Title HTML.
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_title_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults = array(
			'title' => '',
			'class' => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
			</table>
			<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
			<?php if ( ! empty( $data['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $data['description'] ); ?></p>
			<?php endif; ?>
			<table class="form-table">
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate Text Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_text_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate Price Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_price_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return ( '' === $value ) ? '' : wc_format_decimal( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate Decimal Field.
	 *
	 * Make sure the data is escaped correctly, etc.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_decimal_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return ( '' === $value ) ? '' : wc_format_decimal( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate Password Field. No input sanitization is used to avoid corrupting passwords.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_password_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return trim( stripslashes( $value ) );
	}

	/**
	 * Validate Textarea Field.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_textarea_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses( trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array(
						'src' => true,
						'style' => true,
						'id' => true,
						'class' => true,
					),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	}

	/**
	 * Validate Checkbox Field.
	 *
	 * If not set, return "no", otherwise return "yes".
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_checkbox_field( $key, $value ) {
		return ! is_null( $value ) ? 'yes' : 'no';
	}

	/**
	 * Validate Select Field.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string
	 */
	public function validate_select_field( $key, $value ) {
		$value = is_null( $value ) ? '' : $value;
		return wc_clean( stripslashes( $value ) );
	}

	/**
	 * Validate Multiselect Field.
	 *
	 * @param string $key Field key.
	 * @param string $value Posted Value.
	 * @return string|array
	 */
	public function validate_multiselect_field( $key, $value ) {
		return is_array( $value ) ? array_map( 'wc_clean', array_map( 'stripslashes', $value ) ) : '';
	}

	/**
	 * Validate the data on the "Settings" form.
	 *
	 * @deprecated 2.6.0 No longer used.
	 * @param array $form_fields Array of fields.
	 */
	public function validate_settings_fields( $form_fields = array() ) {
		wc_deprecated_function( 'validate_settings_fields', '2.6' );
	}

	/**
	 * Format settings if needed.
	 *
	 * @deprecated 2.6.0 Unused.
	 * @param array $value Value to format.
	 * @return array
	 */
	public function format_settings( $value ) {
		wc_deprecated_function( 'format_settings', '2.6' );
		return $value;
	}
}
}