<?php
/**
 * Improved Settings
 *
 * @package wpdeepl_WP_Improved_Settings
 * @version 20251205
 *
 * 20251205 Version 2.0 - Adaptée à l'API v2.0, PCP compliant
 * 20221115 esc all
 * 20210111 ajout wpimpsettings_find_option_like
 * 20200320 ajout des setting en tableau
 * 20190705 footer actions prise en compte des méthodes
 * 201991125 plugin_text_domain supprimé
 * 20191201 plugin paths
 */

namespace wpdeepl_WP_Improved_Settings;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !function_exists( 'wpdeepl_WP_Improved_Settings\zebench_get_plugin_paths' ) ) {
	function zebench_get_plugin_paths() {
		$array = apply_filters( 'wpdeepl_zebench_get_plugin_paths', array() );
		return $array;
	}
}

if( !function_exists('wpdeepl_WP_Improved_Settings\wpimpsettings_find_all_options_like') ){
	function wpimpsettings_find_all_options_like( $string ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared statement, admin-only utility function
		$results = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",
				'%' . $wpdb->esc_like( $string ) . '%' // Assainissement (esc_like) et wildcards injectés ici
			) 
		, ARRAY_A );
		
		return $results;
	}
}
if( !function_exists('wpdeepl_WP_Improved_Settings\wpimpsettings_find_option_like') ){
	function wpimpsettings_find_option_like( $string ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}
		global $wpdb;
        $sanitized_string = sanitize_key( $string );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared statement, admin-only utility function
		$results = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s",
				$wpdb->esc_like( $sanitized_string ) . '%' // [sanitized_string]%
			) 
		, ARRAY_A );

		$return = array();
		if( $results ) {
            foreach ( $results as $result ) {
                $name = str_replace( $sanitized_string . '_', '', $result['option_name'] );
				$explode = explode('_', $name );
				$value = $result['option_value']; 
				if ( ! empty( $explode[0] ) && ! empty( $explode[1] ) ) {
				    $return[ sanitize_key( $explode[0] ) ][ sanitize_key( $explode[1] ) ] = $value;
				}
			}
		}
		return $return;
	}
}
if ( !class_exists( 'wpdeepl_WP_Improved_Settings\wpdeepl_WP_Improved_Settings' ) ) {
class wpdeepl_WP_Improved_Settings {
	/**
	 * Settings structure from configuration
	 *
	 * @var array
	 */
	protected $settingsStructure = array();
	
	/**
	 * Extended actions for maintenance tasks
	 *
	 * @var array
	 */
	public $extendedActions = array();

	/**
	 * Plugin paths cache
	 *
	 * @var array
	 */
	protected $plugins_paths = array();

	/**
	 * Settings API instance
	 *
	 * @var WC_Improved_Settings_API
	 */
	protected $WC_Improved_Settings_API;

	// Configuration properties
	public $isMainMenu = false;
	public $plugin_id;
	public $menu_order = 20;
	public $minimum_capability = 'manage_options';
	public $option_page = '';
	public $defaultSettingsTab = '';
	public $parent_menu = '';
	public $post_type = false;


	public function __construct() {
		add_action( 'admin_init', array( $this, 'loadSettings' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		$this->plugins_paths = apply_filters('wpdeepl_zebench_plugins_paths', array() );

		add_action( 'admin_menu', array( $this, 'addToMenu' ), $this->menu_order );

		if ( !class_exists( 'wpdeepl_WP_Improved_Settings\WC_Improved_Settings_API' )) {
			require_once( dirname( __FILE__ ) . '/wp-improved-settings-api.class.php' );
		}
	}

	function getPluginID() {
		return $this->plugin_id;
	}

	function getOptionPage() {
		return $this->option_page;
	}

	function getMinimumCapability() {
		return $this->minimum_capability;
	}

	protected function saveSettings() {
		// Vérification nonce (sera revérifiée dans l'API)
		$nonce = isset( $_REQUEST['_wpdeepl_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpdeepl_nonce'] ) ) : false;
		if ( ! wp_verify_nonce( $nonce, 'wpdeepl_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'wpdeepl' ) );
		} 

		// L'API gère sa propre vérification nonce
		$this->WC_Improved_Settings_API->process_admin_options();
	}
/*
	function me() {
		$this->loadSettings();
				$this->WC_Improved_Settings_API = WC_Improved_Settings_API( $this->getPluginID(), $this->getSettingsStructure() );
		return $this->WC_Improved_Settings_API->process_admin_options();
	}*/

	function maybe_print_notices() {
	}

	function print_saved_notice() {
		?>
		<div id="message" class="updated notice is-dismissible"><p><strong><?php esc_html_e( 'Settings saved.', 'wpdeepl' ); ?></strong></p></div>
		<?php
	}

	public function loadSettings() {
		$this->settingsStructure =  $this->getSettingsStructure();
		$this->WC_Improved_Settings_API = new \wpdeepl_WP_Improved_Settings\WC_Improved_Settings_API( $this->getPluginID(), $this->settingsStructure );
		// load settings into $this sttings ?
		//wpdeepl_debug_display( $this->settingsStructure );		die( 'oka6z4e4z64ze4' );
	}

	/**
	 * Add Settings link to menu
	 *
	 * @access public
	 * @return voidaddToMenu
	 */
	public function addToMenu() {
		//die( 'menu to '.$this->parent_menu . ' page title = ' . $this->getPageTitle() .' menu = ' . 	$this->getMenuTitle() . ' cap ' . 	'manage_options' . ' option page = ' .			$this->getOptionPage() );
		add_submenu_page(
			$this->parent_menu,
			$this->getPageTitle(),
			$this->getMenuTitle(),
			$this->getMinimumCapability(),
			$this->getOptionPage(),
			array( $this, 'settingsPage' )
		);
	}

	/**
	 * Print settings page
	 *
	 * @access public
	 * @return void
	 */
	public function settingsPage() {
		// Get current tab
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation tab, no data processing
		$current_tab = ( isset( $_GET['tab'] ) ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $this->defaultSettingsTab;

		// Print header
		$this->printHeader();

		$this->printFields();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- extendedActions is not defined by user input. 
		if ( count( $this->extendedActions ) ) foreach ( $this->extendedActions as $action => $function ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The action name is from the class definitions, not user input. 
			if ( isset( $_REQUEST[$action] ) ) {
				if ( function_exists( $function) ) {
					$function();
				}
				else {
					/* translators: function does not exist */
					printf( esc_html__( 'Undefined function: %s', 'wpdeepl' ), esc_html( $function ) );
				}
			}
		}
		$this->printFooter();
	}

	public function registerSettings() {
		// Check if current user can manage plugin settings
		if ( !is_admin() ) {
			return;
		}
		
		$key_value = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- plugin_id is hardcoded.
		if( isset( $_REQUEST[$this->plugin_id . '_options_save'] )  ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- plugin_id is hardcoded.
			$key_value = sanitize_text_field( wp_unslash( $_REQUEST[$this->plugin_id . '_options_save'] ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in saveSettings()
		if ( isset( $_REQUEST['save'] ) && $key_value ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in saveSettings()
			$this->saveSettings();
			if ( method_exists( $this, 'on_save' ) ) {
				$this->on_save();
			}

			add_action( 'admin_notices', array( $this, 'print_saved_notice' ) );
		}
		add_action( 'admin_notices', array( $this, 'maybe_print_notices' ) );

		// Iterate over tabs
		foreach ( $this->settingsStructure as $tab_key => $tab ) {
			// Register tab
			register_setting(
				$this->option_page .'_group_' . $tab_key,
				$this->option_page,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( $this, 'sanitizeSettings' ),
				)
			);

			// Iterate over sections
			foreach ( $tab['sections'] as $section_key => $section ) {
				$settings_page_id = $this->plugin_id . '-admin-' . str_replace( '_', '-', $tab_key );

				// Register section
				add_settings_section(
					$section_key,
					$section['title'],
					array( $this, 'print_section_info' ),
					$settings_page_id
				);

				// Iterate over fields
				if( isset( $section['fields'] ) ) foreach ( $section['fields'] as $field_key => $field ) {
					// Register field
					add_settings_field(
						$this->plugin_id . '_' . $field_key,
						$field['title'],
						array( $this, 'print_field_' . $field['type'] ),
						$settings_page_id,
						$section_key,
						array(
							'field_key'			 => $field_key,
							'field'				 => $field,
							'data-' . $this->plugin_id . '-setting-hint'	=> !empty( $field['hint'] ) ? $field['hint'] : null,
						)
					);
				}
			}
		}
	}

	/**
	 * Sanitize settings before saving
	 *
	 * @param mixed $input The input value to sanitize.
	 * @return mixed Sanitized value.
	 */
	public function sanitizeSettings( $input ) {
		if ( ! is_array( $input ) ) {
			return sanitize_text_field( $input );
		}

		$sanitized = array();
		foreach ( $input as $key => $value ) {
			$sanitized_key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$sanitized[ $sanitized_key ] = $this->sanitizeSettings( $value );
			} else {
				$sanitized[ $sanitized_key ] = sanitize_text_field( $value );
			}
		}
		return $sanitized;
	}

	protected function getActiveTab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in saveSettings()
		if ( isset( $_GET[ 'tab' ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation tab, sanitized, no data processing
			$active_tab = sanitize_key( wp_unslash( $_GET[ 'tab' ] ) );
		}
		elseif ( $this->defaultSettingsTab != '' ) {
			$active_tab = $this->defaultSettingsTab;
		}

		if ( !isset( $this->settingsStructure[$active_tab] ) ) {
			return false;
		}
		return $active_tab;
	}

	function printHeader() {
		$tabs = array();
		foreach ( $this->settingsStructure as $setting_tab_slug => $setting_data ) {
			$tabs[$setting_tab_slug] = $setting_data['title'];
		}
		//echo '<div class="wrap woocommerce"><form method="post" action="options.php" enctype="multipart/form-data">';
		?>

			<div class="wrap">

		<div id="icon-themes" class="icon32"></div>
		<h2><?php echo esc_html( $this->getPageTitle() ); ?></h2>
		<?php
		settings_errors();

		$active_tab = $this->getActiveTab();

		$parent_menu = $this->parent_menu;
		$parsed_url = wp_parse_url( $parent_menu );
		$extended_url = '';
		if ( isset( $parsed_url['query'] ) && strlen( $parsed_url['query'] ) ) {
			$extended_url = '&' . $parsed_url['query'];
		}

		if ( property_exists($this, 'post_type' ) && $this->post_type ) {
			$action = 'edit.php';
		}
		else {
			$action = 'admin.php';
		}
		$action .= '?';

		if ( $this->post_type ) {
			$action .= 'post_type=' . $this->post_type .'&';
		}
		$action .= 'page=' . $this->getOptionPage();
		if ($active_tab) {
			$action .= '&tab=' . $active_tab;
		}

		$page_link = '?';
		if ( $this->post_type ) {
			$page_link .= 'post_type=' . $this->post_type .'&';
		}
		$page_link .= 'page=' . $this->getOptionPage();



		?>
		<form method="post" id="mainform" action="<?php echo esc_attr( $action ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo esc_attr( $this->plugin_id ); ?>_options_save" value="1">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">

		 <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php foreach ( $tabs as $tab => $label ) :
			$nav_tab_id = $this->getOptionPage() . '-' . $tab; ?>
			<a id="<?php echo esc_attr( $nav_tab_id ); ?>" href="<?php echo esc_url( $page_link ) .  '&tab=' . esc_attr( $tab . $extended_url ); ?>" class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		 </nav>

		 <?php
		if ( !$active_tab = $this->getActiveTab() ) {
			return false;
		}

/*
		$tab_data = $this->settingsStructure[$active_tab];
		$defaults = array(
			'title'			=> '',
			'class'			=> '',
			'description'	=> '',
			'sections'		=> array(),
		);
		$tab_data = wp_parse_args( $tab_data, $defaults );

		$this->displayTabTitle( $active_tab, $tab_data );
*/
	}

	function printFields() {
		if (!$this->getActiveTab()) {
			return false;
		}
		$active_tab = $this->getActiveTab();
		$tab_data = $this->settingsStructure[$active_tab];

		
		//wpdeepl_debug_display($tab_data, " T AB DATA");


		foreach ( $tab_data['sections'] as $section_id => $section ) {
			$defaults = array(
				'title'			=> '',
				'class'			=> '',
				'description'	=> '',
				'fields'		=> array(),
				'html'			=> false,
			);
			$section_data = wp_parse_args( $section, $defaults );

			//wpdeepl_debug_display( $section );
			?>
			<h3 class="wc-settings-sub-title <?php echo esc_attr( $section_data['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>"><?php echo wp_kses_post( $section_data['title'] ); ?></h3>
			<?php if ( ! empty( $section_data['description'] ) ) : ?>
					<p><?php echo wp_kses_post( $section_data['description'] ); ?></p>
			<?php endif; ?>

			<table class="form-table">

			<?php

			$this->WC_Improved_Settings_API->id = $active_tab;

			$section_fields = $section_data['fields'];

			$fields = array();
			foreach ( $section_fields as $field ) {
				$fields[] = $field;
			}

			//wpdeepl_debug_display($fields, "on a fields");

			$this->WC_Improved_Settings_API->generate_settings_html( $fields );
			?>
			</table>
			<?php
			 if ( isset( $section['html'] ) && $section['html'] ) {
			 	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML already escaped, we need to ouput raw HTML
			 	//echo ( $section['html'] );
			 	echo wp_kses_post( $section['html'] );
			}
			?>

			<?php if ( isset( $section['actions'] ) && $section['actions'] ) foreach ( $section['actions'] as $action ) {
					$param = false;
					if ( is_array( $action ) ) {
						list($action, $param) = $action;
						if ( function_exists( $action ) ) {
							$action( $param );
						}
					}
					elseif ( function_exists( $action ) ) {
						$action( $param );
					}

					else {
						/* translators: name of the function not found */
						printf( esc_html__( 'Undefined function: %s', 'wpdeepl' ), esc_html( $action ) );
					}
			}

				?>


			<?php if ( count( $fields ) ) : ?>

			<p class="submit">
				<?php wp_nonce_field('wpdeepl_save_settings', '_wpdeepl_nonce' ); ?>
				<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
					<button name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Update', 'wpdeepl' ); ?>"><?php esc_html_e( 'Update', 'wpdeepl' ); ?></button>
				<?php endif; ?>
			</p>
			<?php endif; ?>

		<?php
		}
		?>

		<?php
	}

	function displayTabTitle( $tab_id, $tab_data ) {
		?>
		<h2 class="wc-settings-sub-title <?php echo esc_attr( $tab_data['class'] ); ?>" id="<?php echo esc_attr( $tab_id ); ?>"><?php echo wp_kses_post( $tab_data['title'] ); ?></h2>
		<?php if ( ! empty( $tab_data['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $tab_data['description'] ); ?></p>
		<?php endif;
	}

	protected function tabFooter( $tab_id, $tab_data ) {
		if ( isset( $tab_data['footer'] ) ) {
			if ( isset( $tab_data['footer']['html'] ) ) foreach ( $tab_data['footer']['html'] as $raw_html ) {
				// HTML déjà échappé dans la configuration
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $raw_html;
			}
			if ( isset( $tab_data['footer']['actions'] ) ) {
				echo '<hr />';
				foreach ( $tab_data['footer']['actions'] as $action ) {
					$param = false;

					if ( is_array( $action ) ) {
						list($object, $method) = $action;
						if ( method_exists( $object, $method ) ) {
							$object->$method();
						}
					}
					elseif ( function_exists( $action ) ) {
						$action( $param );
					}
					else {
						/* translators: name of the function not found */
						printf( esc_html__( 'Undefined function: %s', 'wpdeepl' ), esc_html( $action ) );
					}
				}
			}
		}
	}

	function printFooter() {
?>
		<?php
		$active_tab = $this->getActiveTab();
		if ( $active_tab ) {
			$tab_data = $this->settingsStructure[$active_tab];
			$this->tabFooter( $active_tab, $tab_data );
		}
			?>

		</form>

	</div>
	<?php
	}


	public function showServerInfo() {
		echo '<h2>' . esc_html__('Server information', 'wpdeepl' ) . '</h2>';

		if( function_exists('ini_get_all' ) )  {

			$ini_values = ini_get_all();
			$timeout = $ini_values['max_execution_time']['local_value'];
		}
		else {
			$timeout = '';
		}

		$bytes = memory_get_usage();
		$s = array('o', 'Ko', 'Mo', 'Go', 'To', 'Po');
		$e = floor(log($bytes)/log(1024));
		$memory_usage = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
	 

		
		if ($timeout > 1000 ) {
			$timeout = round($timeout /1000,1);
		}

		$informations = array(
			'Server time'	=> gmdate('d/m/Y H:i:s'),
			'Real path'		=> get_home_path(),
			'PHP version'	=> phpversion(),
			'Timeout'		=> $timeout .' s',
			'Memory usage'	=> $memory_usage,
		);

		if ( function_exists( 'sys_getloadavg') ) {
			$sys_getloadavg = sys_getloadavg();
			if( is_array( $sys_getloadavg ) ){
				$informations['Load'] = implode(', ', $sys_getloadavg );
			}
			else {
				$informations['Load'] = $sys_getloadavg;
			}
		}

		foreach ($informations as $label => $value) {
			printf( "<p><strong>%s</strong>&nbsp;%s</p>", esc_html( $label ), esc_html( $value ) );
		}


	}



	public function showLogs() {

		if ( !is_admin() ) {
			return false;
		}
		$path = $this->log_folder;

		$logs = glob( trailingslashit( $path ) . '*.log');
		//wpdeepl_debug_display($logs, "LOGS");
		if ($logs) foreach ($logs as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if (preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				echo '<h3>';
				printf(
					/* translators: 1. file name 2. month */
					esc_html__("File '%1\$s' for %2\$s", 'wpdeepl' ),
					esc_html( $match[3] ),
					esc_html( $date )
				);
				echo '</h3>';
				$lines = explode( "\n", $contents);
				foreach ( $lines as $line ) {
					//$line = preg_replace('#\{"body":".*?"},#ism', '-body-', $line);
					//$line = preg_replace( '#"raw":".*?","headers#ism', 'headers', $line );
					$line = preg_replace( '#"body":"<!DOCTYPE.*?","headers#ism', '"body":"PROBLEME COTE INSURED (disponible dans les logs complets)", "headers', $line);
					if ( stripos( $line, '<!DOCTYPE html>' ) ) {
						continue;
					}
				// Contenu log potentiellement dangereux - échapper
				echo "<br /><br />" . esc_html( $line ) . "\n";
				}
				//wpdeepl_debug_display($contents);

			}
		}

	}
}
}
