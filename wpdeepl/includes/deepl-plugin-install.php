<?php


function deepl_install_plugin() {
	if ( !get_option( 'deepl_plugin_installed') ) {
		update_option( 'deepl_plugin_installed', 0 );
	}
	if ( !get_option( 'wpdeepl_metabox_post_types') ) {
		update_option( 'wpdeepl_metabox_post_types', array( 'post', 'page' ));
	}

	if ( !get_option( 'wpdeepl_metabox_context') ) {
		update_option('wpdeepl_metabox_context','side');
	}
	if ( !get_option( 'wpdeepl_metabox_priority' ) ) {
		update_option('wpdeepl_metabox_priority','high');
	}
	foreach( array( 'wpdeepl_tpost_title', 'wpdeepl_tpost_content', 'wpdeepl_tpost_excerpt' ) as $key ) {
		if( !get_option( $key ) ) {
			update_option( $key, 1 );
		}
	}

	update_option('wpdeepl_plugin_installed', 1 );
}