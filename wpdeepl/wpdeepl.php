<?php
/**
 * Plugin Name: Translation with DeepL API
 * Description: Get DeepL translation magic right inside your WordPress editor (with a paid DeepL Pro account). This plugin is not affiliated with DeepL SE.
 * Version: 2.5.4
 * Plugin Slug: wpdeepl
 * Author: Fluenx
 * Author URI: https://www.fluenx.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpdeepl
 * Domain Path: /languages
 */

//return;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// exit early
$wpdeepl_allow_front_end = get_option('wpdeepl_allow_front_end');
if( $wpdeepl_allow_front_end != 'yes' ) {
	if ( !function_exists( 'is_admin' ) || !is_admin() )
	return;
}

//defined( 'WPDEEPL_DEBUG' ) 		or define( 'WPDEEPL_DEBUG', false ); // Uncomment AND set to true to force debug mode
defined( 'WPDEEPL_FLAVOR' ) 	or define( 'WPDEEPL_FLAVOR', 	'free' );
defined( 'WPDEEPL_NAME' ) 		or define( 'WPDEEPL_NAME', 		plugin_basename( __FILE__ ) );
defined( 'WPDEEPL_SLUG' ) 		or define( 'WPDEEPL_SLUG', 		'wpdeepl' );
defined( 'WPDEEPL_DIR' ) 		or define( 'WPDEEPL_DIR', 		dirname( __FILE__ ) );
defined( 'WPDEEPL_PATH' ) 		or define( 'WPDEEPL_PATH', 		realpath( __DIR__ ) );
defined( 'WPDEEPL_URL' ) 		or define( 'WPDEEPL_URL', 		plugins_url( '', __FILE__ ) );
	
$wpdeepl_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
defined( 'WPDEEPL_VERSION' ) 	or define( 'WPDEEPL_VERSION', $wpdeepl_plugin_data['Version'] );

defined( 'WPDEEPL_DEBUG' ) 		or define( 'WPDEEPL_DEBUG', false );

$wpdeepl_upload_dir = wp_upload_dir();
defined( 'WPDEEPL_FILES' ) 		or define( 'WPDEEPL_FILES', 		trailingslashit( $wpdeepl_upload_dir['basedir'] ) . 'wpdeepl' );
defined( 'WPDEEPL_FILES_URL' ) 	or define( 'WPDEEPL_FILES_URL', 	trailingslashit( $wpdeepl_upload_dir['baseurl'] ) . 'wpdeepl' );

function wpdeepl_paths( $paths = array() ) {
	$paths['deepl_settings'] = array(
		'files'	=> WPDEEPL_FILES
	);
	return $paths;
}

try {
	if ( is_admin() || $wpdeepl_allow_front_end == 'yes' ) {

		
		include_once  trailingslashit( WPDEEPL_PATH ) . 'deepl-configuration.class.php';
		
		include_once  trailingslashit( WPDEEPL_PATH ) . 'includes/deepl-functions.php';
		include_once  trailingslashit( WPDEEPL_PATH ) . 'includes/deepl-plugin-install.php';
 		

 		include_once  trailingslashit( WPDEEPL_PATH ) . 'modules/deepl-translate-post.php';

 		include_once  trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings-api.class.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings.class.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings-wpdeepl.class.php';

 		include_once  trailingslashit( WPDEEPL_PATH ) . 'client/deepl-data.class.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-functions.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi.class.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-translate.class.php';
 		include_once  trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-usage.class.php';

 		if( is_admin() ) {
	 		include_once  trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-admin-hooks.php';
	 		include_once  trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-admin-functions.php';
	 		include_once  trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-metabox.php';
	 		//if( file_exists( trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-elementor.php' )	 			include_once  trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-elementor.php';

 		}
 		
		add_filter('zebench_plugins_paths', 'wpdeepl_paths');

		$wpdeepl_customisation_file = trailingslashit( WPDEEPL_PATH ) . 'custom-integration.php';
		//echo "\n file $wpdeepl_customisation_file / exists ? " . file_exists( $wpdeepl_customisation_file );
		if ( file_exists( $wpdeepl_customisation_file ) ) {
			include_once  $wpdeepl_customisation_file;
		}
		else {
			
		}

	}
} catch ( Exception $exception ) {
	if ( current_user_can( 'manage_options' ) ) {
		// En mode debug seulement, logger les erreurs
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug mode only
			error_log( 'WPDeepL Fatal Error: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() );
		}
		wp_die( 
            esc_html__( 'Error loading WPDeepL', 'wpdeepl' ), 
            array( 'response' => 500 ) 
        );
	}
    return;
}


//global $DeepLForWordPress; $DeepLForWordPress = new DeepLForWordPress();
function deepl_is_plugin_fully_configured() {
	$WP_Error = new WP_Error();

	if ( count( $WP_Error->get_error_messages() ) ) {
		return $WP_Error;
	}
	return true;
}

if ( !function_exists( 'wpdeepl_debug_display' ) ) {
	function wpdeepl_debug_display( $e, $txt = '' ) {
		
		// En mode debug seulement
		if ( 1 == 1  ) {
			if ( $txt != '' ) echo esc_html(  "<br />\n$txt" );
			?>
			<pre><?php  
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug mode seulement
				print_r( $e ); 
			?>
			</pre><?php 
		}
	}
}

add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpdeepl_plugin_action_links' );
function wpdeepl_plugin_action_links( $links ) {
 $links = array_merge(
 	array(
 		'<a href="' . esc_url( admin_url( '/options-general.php?page=deepl_settings' ) ) . '">' . __( 'Settings', 'wpdeepl' ) . '</a>'
 	),
 	$links
 );
 return $links;
}

register_activation_hook( __FILE__, 'deepl_plugin_activate' );
function deepl_plugin_activate() {
	deepl_install_plugin();
}

register_deactivation_hook( __FILE__, 'deepl_plugin_deactivate' );
function deepl_plugin_deactivate() {
}

add_action( 'init', 'deepl_init' );
function deepl_init() {
	if ( !is_admin() ) {
		return;
	}
}