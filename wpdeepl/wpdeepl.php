<?php
/**
 * Plugin Name: DeepL for WordPress : translation plugin
 * Description: Get DeepL translation magic right inside your WordPress editor (with a paid DeepL Pro account)
 * Version: 2.4.3.12
 * Plugin Slug: wpdeepl
 * Author: Fluenx
 * Author URI: https://www.fluenx.com/
 * Requires at least: 4.0.0
 * Tested up to: 6.6.1
 * Stable tag: 2.4.3.12
 * Text Domain: wpdeepl
 * Domain Path: /languages
 */

//return;


$allow_front_end = get_option('wpdeepl_allow_front_end');

if( $allow_front_end != 'yes' ) {
	if ( !function_exists( 'is_admin' ) || !is_admin() )
	return;

}


defined( 'WPDEEPL_FLAVOR' ) 	or define( 'WPDEEPL_FLAVOR', 'free' );
defined( 'WPDEEPL_NAME' ) 		or define( 'WPDEEPL_NAME', 		plugin_basename( __FILE__ ) );
defined( 'WPDEEPL_SLUG' ) 		or define( 'WPDEEPL_SLUG', 		'wpdeepl' );
defined( 'WPDEEPL_DIR' ) 		or define( 'WPDEEPL_DIR', 		dirname( __FILE__ ) );
defined( 'WPDEEPL_PATH' ) 		or define( 'WPDEEPL_PATH', 		realpath( __DIR__ ) );
defined( 'WPDEEPL_URL' ) 		or define( 'WPDEEPL_URL', 		plugins_url( '', __FILE__ ) );
	
$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
defined( 'WPDEEPL_VERSION' ) 	or define( 'WPDEEPL_VERSION', $plugin_data['Version'] );

defined( 'WPDEEPL_DEBUG' ) 		or define( 'WPDEEPL_DEBUG', 0 );

$wp_upload_dir = wp_upload_dir();
defined( 'WPDEEPL_FILES' ) 		or define( 'WPDEEPL_FILES', 		trailingslashit( $wp_upload_dir['basedir'] ) . 'wpdeepl' );
defined( 'WPDEEPL_FILES_URL' ) 	or define( 'WPDEEPL_FILES_URL', 	trailingslashit( $wp_upload_dir['baseurl'] ) . 'wpdeepl' );
if ( !is_dir( WPDEEPL_FILES ) ) mkdir( WPDEEPL_FILES );

// obs 20210422 v1.7 defined( 'DEEPL_API_URL' ) or define( 'DEEPL_API_URL',	'https://api.deepl.com/v2/' );

function zebench_wpdeepl_paths( $paths = array() ) {
	$paths['deepl_settings'] = array(
		'files'	=> WPDEEPL_FILES
	);
	return $paths;
}

try {
	if ( is_admin() || $allow_front_end == 'yes' ) {

		
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
 		
		add_filter('zebench_plugins_paths', 'zebench_wpdeepl_paths');

		$customisation_file = trailingslashit( WPDEEPL_PATH ) . 'custom-integration.php';
		if ( file_exists( $customisation_file ) ) {
			include_once  $customisation_file;
		}

	}
} catch ( Exception $exception ) {
	if ( current_user_can( 'manage_options' ) ) {
		print_r( $exception );
		die( __( 'Error loading WPDeepL','wpdeepl' ) );
	}
}


//global $DeepLForWordPress; $DeepLForWordPress = new DeepLForWordPress();
function deepl_is_plugin_fully_configured() {
	$WP_Error = new WP_Error();

	if ( count( $WP_Error->get_error_messages() ) ) {
		return $WP_Error;
	}
	return true;
}

if ( !function_exists( 'plouf' ) ) {
	function plouf( $e, $txt = '' ) {
		if ( $txt != '' ) echo "<br />\n$txt";
		echo '<pre>';
		print_r( $e );
		echo '</pre>';
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

	load_plugin_textdomain( 'wpdeepl', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}