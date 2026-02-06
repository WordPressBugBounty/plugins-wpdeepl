<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action( 'init', 'deepl_init_admin', 40 );
function deepl_init_admin() {
	if ( !is_admin() ) {
		return;
	}
	if ( DeepLConfiguration::isPluginInstalled() === false ) {
 		deepl_install_plugin();
 	}

	global $wpdeepl_WP_Improved_Settings_DeepL;

	if ( !$wpdeepl_WP_Improved_Settings_DeepL ) {
		$wpdeepl_WP_Improved_Settings_DeepL = new wpdeepl_WP_Improved_Settings\wpdeepl_WP_Improved_Settings_DeepL();
	}

	global $DeepL_Metabox;
	$DeepL_Metabox = new DeepL_Metabox();
}


add_action( 'admin_enqueue_scripts', 'deepl_load_admin_javascript' );
function deepl_load_admin_javascript( $hook ) {

	if ( $hook == 'settings_page_deepl_settings' ) {
		wp_enqueue_style( 'deepl_admin', WPDEEPL_URL . '/assets/wpdeepl-admin.css', array(), WPDEEPL_VERSION );
 	}
 	if ( $hook == 'settings_page_deepl_settings' || $hook == 'post.php' || $hook == 'post-new.php' ) {
 		wp_enqueue_script( 'deepl_admin', trailingslashit( WPDEEPL_URL ) . 'assets/wpdeepl-metabox.js', false, WPDEEPL_VERSION, true );
 		wp_localize_script( 'deepl_admin', 'DeepLStrings', deepl_get_localized_strings() );
 }
}

function deepl_get_localized_strings() {
	$strings = array();
	$strings = apply_filters( 'deepl_localized_strings', $strings );
	return $strings;
}


add_action('admin_footer', 'deepl_admin_footer');
function deepl_admin_footer() {

	if ( get_current_screen()->base != 'settings_page_deepl_settings' ) {
		return;
	}
	?>
	<script>
		jQuery(document).ready(function() {

			var behaviours_notice = jQuery('input[name="wpdeepl_metabox_behaviour"]').parent().find('p.description');
			var usingMultilingualPlugins = <?php echo DeeplConfiguration::usingMultilingualPlugins() ? 1 : 0; ?>;
			if ( !usingMultilingualPlugins ) {

				jQuery('#wpdeepl_metabox_behaviour_append').attr('disabled','disabled');
				jQuery(behaviours_notice).show();
			}
			else {
				jQuery(behaviours_notice).hide();

			}

		});
	</script>

	<?php
}

//add_filter( 'post_row_actions', 'wpdeepl_modify_list_row_actions', 10, 2 );
function wpdeepl_modify_list_row_actions( $actions, $post ) {
 // Check for your post type.
 $post_types = DeepLConfiguration::getMetaBoxPostTypes();

 if ( in_array( $post->post_type, $post_types )) {
 // Build your links URL.
 	$url = admin_url( 'admin.php?page=deepl&post=' . $post->ID );

 // Maybe put in some extra arguments based on the post status.
 if( $url ) $edit_link = add_query_arg( array( 'action' => 'edit' ), $url );

 // The default $actions passed has the Edit, Quick-edit and Trash links.
 $actions['translate'] = sprintf(
 	'<a href="%1$s">%2$s</a>',
 esc_url( $edit_link ),
 'Test'
 );
 	}
 //wpdeepl_debug_display( $actions );
 return $actions;
}
