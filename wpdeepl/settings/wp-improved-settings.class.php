<?php
/**
 * Improved Settings
 *
 * @package WP_Improved_Settings
 * @version 20221115
 *
 * 20221115 esc all
 * 20210111 ajout wpimpsettings_find_option_like
 * 20200320 ajout des setting en tableau
 * 20190705 footer actions prise en compte des méthodes
 * 201991125 plugin_text_domain supprimé
 * 20191201 plugin paths
 */

namespace WP_Improved_Settings;

if ( !function_exists( 'WP_Improved_Settings\zebench_get_plugin_paths' ) ) {
	function zebench_get_plugin_paths() {
		$array = apply_filters( 'zebench_get_plugin_paths', array() );
		return $array;
	}
}

if( !function_exists('WP_Improved_Settings\wpimpsettings_find_all_options_like') ){
	function wpimpsettings_find_all_options_like( $string ) {
		// ugly hack to fetch plugin_name_index options
		global $wpdb;
		$search = $wpdb->_real_escape( $string );
		$sql = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '%$search%'";
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		return $results;
	}
}

if( !function_exists('WP_Improved_Settings\wpimpsettings_find_option_like') ){
	function wpimpsettings_find_option_like( $string ) {
		// ugly hack to fetch plugin_name_index options
		global $wpdb;
		$search = $wpdb->_real_escape( $string );
		$sql = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '$search%'";
		
		$results = $wpdb->get_results( $sql, ARRAY_A );
		$return = array();
		if( $results ) foreach ( $results as $result ) {
			$name = str_replace($string .'_', '', $result['option_name'] );
			$explode = explode('_', $name );

			$value = $result['option_value'];
			$return[$explode[0]][$explode[1]] = $value;
		}
		return $return;
	}
}

if ( !class_exists( 'WP_Improved_Settings\WP_Improved_Settings' ) ) {
class WP_Improved_Settings {
	// loosely based on wc-dynamic-pricing-and-discounts/classes/rp-wcdpd-settings.class.php
	public $settingsStructure = array();
	public $extendedActions = array();


	public $plugins_paths = array();

	public $WC_Improved_Settings_API;

	public $isMainMenu = false;
	public $plugin_id;
	public $menu_order = 20;
	public $minimum_capability = 'manage_options';
	public $option_page = '';
	public $defaultSettingsTab = '';
	public $parent_menu = '';
	public $post_type = false;


/*Dashboard: 'index.php'
Posts: 'edit.php'
Media: 'upload.php'
Pages: 'edit.php?post_type=page'
Comments: 'edit-comments.php'
Custom Post Types: 'edit.php?post_type=your_post_type'
Appearance: 'themes.php'
Plugins: 'plugins.php'
Users: 'users.php'
Tools: 'tools.php'
Settings: 'options-general.php'
Network Settings: 'settings.php'
WooCommerce : 'woocommerce'
*/

	public function __construct() {
		// Register settings

		add_action( 'admin_init', array( $this, 'loadSettings' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		$this->plugins_paths = apply_filters('zebench_plugins_paths', array() );

		// Add link to menu

		global $wp_filter;
		$real_order = $this->menu_order;
		while( isset( $wp_filter['admin_menu']->callbacks[$real_order] ) ) {
			$real_order++;
		}
		add_action( 'admin_menu', array( $this, 'addToMenu' ), $real_order );

		// Pass configuration to Javascript
		//add_action( 'admin_enqueue_scripts', array( $this, 'configuration_to_javascript' ), 999 );

		// Enqueue templates to be rendered in footer
		//add_action( 'admin_footer', array( $this, 'render_templates_in_footer' ) );

		// Settings export call
		if ( !empty( $_REQUEST['export_settings'] ) ) {
			add_action( 'wp_loaded', array( $this, 'export' ) );
		}

		// Settings import call
		if ( !empty( $_FILES[$this->option_page]['name']['import'] ) ) {
			add_action( 'wp_loaded', array( $this, 'import' ) );
		}

		// Print settings import notice
		if ( isset( $_REQUEST[$this->option_page .'_imported'] ) ) {
			add_action( 'admin_notices', array( $this, 'print_import_notice' ) );
		}

		if ( !class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' )) {
			require_once( dirname( __FILE__ ) . '/wp-improved-settings-api.class.php' );
		}
		
/*
		$this->WC_Improved_Settings_API = new WC_Improved_Settings_API( $this->getPluginID(), $this->getSettingsStructure() );


		$key_name = $this->plugin_id . '_options_save';
		if ( isset( $_REQUEST['save'] ) && isset( $_REQUEST[$key_name] ) && $_REQUEST[$key_name] ) {
			$this->saveSettings();
//			echo " saving";
			if ( method_exists( $this, 'on_save' ) ) {
//				echo "on save update";
				$this->on_save();
			}

			add_action( 'admin_notices', array( $this, 'print_saved_notice' ) );
		}

		add_action( 'admin_notices', array( $this, 'maybe_print_notices' ) );
*/
		// Migration notices
		//add_action( 'admin_notices', array( $this, 'maybe_display_migration_notice' ), 1 );

		// Delete migration notice
		//$this->hide_migration_notice();
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

	function saveSettings() {
		$nonce = isset( $_REQUEST['_zenonce'] ) ? $_REQUEST['_zenonce'] : false;
		if ( ! wp_verify_nonce( $nonce, 'zesave_settings' ) ) {
			$error_msg = __( 'Unable to submit this form, please refresh and try again.' );
			return;
		} 

		$this->WC_Improved_Settings_API->process_admin_options();
		//$this->process_admin_options();
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
		<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.' ); ?></strong></p></div>
		<?php
	}

	public function loadSettings() {
		$this->settingsStructure =  $this->getSettingsStructure();
		$this->WC_Improved_Settings_API = new WC_Improved_Settings_API( $this->getPluginID(), $this->settingsStructure );
		// load settings into $this sttings ?
		//plouf( $this->settingsStructure );		die( 'oka6z4e4z64ze4' );
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
		$current_tab = ( isset( $_GET['tab'] ) ) ? htmlspecialchars( $_GET['tab'] ) : $this->defaultSettingsTab;

//		plouf( $_POST );

		// Print header
		$this->printHeader();

		$this->printFields();

		if ( count( $this->extendedActions ) ) foreach ( $this->extendedActions as $action => $function ) {
			if ( isset( $_REQUEST[$action] ) ) {
				if ( function_exists( $function) ) {
					$function();
				}
				else {
					printf( __( 'Attention, fonction non définie %s' ), $function );
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

		$key_name = $this->plugin_id . '_options_save';
		if ( isset( $_REQUEST['save'] ) && isset( $_REQUEST[$key_name] ) && $_REQUEST[$key_name] ) {
			$this->saveSettings();
//			echo " saving";
			if ( method_exists( $this, 'on_save' ) ) {
//				echo "on save update";
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
				array( $this, 'validateSettings' )
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

	function validateSettings() {
		return true;
	}

	function getActiveTab() {
		if ( isset( $_GET[ 'tab' ] ) ) {
			$active_tab = htmlspecialchars( $_GET[ 'tab' ] );
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
		$parsed_url = parse_url( $parent_menu );
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

		
		//plouf($tab_data, " T AB DATA");


		foreach ( $tab_data['sections'] as $section_id => $section ) {
			$defaults = array(
				'title'			=> '',
				'class'			=> '',
				'description'	=> '',
				'fields'		=> array(),
				'html'			=> false,
			);
			$section_data = wp_parse_args( $section, $defaults );

			//plouf( $section );
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

			//plouf($fields, "on a fields");

			$this->WC_Improved_Settings_API->generate_settings_html( $fields );
			?>
			</table>
			<?php
			 if ( isset( $section['html'] ) && $section['html'] ) {
			 	// not escaped 
			 	echo ( $section['html'] );
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
						printf( __( 'Attention, fonction non définie %s' ), $action );
					}
			}

				?>


			<?php if ( count( $fields ) ) : ?>

			<p class="submit">
				<?php wp_nonce_field('zesave_settings', '_zenonce' ); ?>
				<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
					<button name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Update' ); ?>"><?php _e( 'Update' ); ?></button>
				<?php endif; ?>
				<?php
				// wp_nonce_field( 'woocommerce-settings' );
				?>
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

	function tabFooter( $tab_id, $tab_data ) {
		if ( isset( $tab_data['footer'] ) ) {
			if ( isset( $tab_data['footer']['html'] ) ) foreach ( $tab_data['footer']['html'] as $raw_html ) {
				//  not escaped
				echo ( $raw_html );
			}
			if ( isset( $tab_data['footer']['actions'] ) ) {
				echo '<hr />';
				foreach ( $tab_data['footer']['actions'] as $action ) {
		//				echo " ACTION = $action";
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
						printf( __( 'Attention, fonction non définie %s' ), $action );
					}
					//plouf($action, "action");
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
		echo '<h2>' . __('Server information', '' ) . '</h2>';

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
			'Server time'	=> date('d/m/Y H:i:s'),
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
			printf( "<p><strong>%s</strong>&nbsp;%s</p>", $label, $value );
		}


	}



	public function showLogs() {

		if ( !is_admin() ) {
			return false;
		}
		/*$current_plugin_page = $_REQUEST['page'];

		plouf($this->plugin_paths);
		if ( !isset( $this->plugin_paths[$current_plugin_page] ) ) {
			echo "no path";
			return false;
		}
		$path = $this->plugin_paths[$current_plugin_page];*/
		$path = $this->log_folder;

		$logs = glob( trailingslashit( $path ) . '*.log');
		//plouf($logs, "LOGS");
		if ($logs) foreach ($logs as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if (preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				echo '<h3>';
				printf(
					__("Fichier '%s' pour %s" ),
					$match[3],
					$date
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
					// not escaped
					echo "<br /><br />" . ( $line ) . "\n";
				}
				//plouf($contents);

			}
		}

	}
}
}