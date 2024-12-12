<?php

/**
 * 
 * To add specific data / unique custom meta to the translation, add this code to your theme/functions.php. Or rename this file to "custom-integration.php" in the wpdeepl folder.
 * Sample : the metakey is 'hotelwp_meta' and the value is encoded in json, we need to translate 'tagline' and 'alt_title' from the array of data
 * */

/**
 * First add the original strings to the array to be translated
 * */
add_filter( 'deepl_translate_post_link_strings', 'mytheme_deeplpro_translate_post_link_strings', 80, 4 );
function mytheme_deeplpro_translate_post_link_strings( $strings_to_translate, $WP_Post, $target_lang, $source_lang ) {

	$custom_data = json_decode( get_post_meta( $WP_Post->ID, 'hotelwp_meta', true ), true );
	if( !empty( $custom_data['tagline'] ) ) {
		$strings_to_translate['mytheme_tagline'] = $custom_data['tagline'];
	}
	if( !empty( $custom_data['alt_title'] ) ) {
		$strings_to_translate['mytheme_alt_title'] = $custom_data['alt_title'];	
	}
	return $strings_to_translate;
}


/**
 * Then get the translated data after translation and post update
 */
add_action( 'deepl_translate_after_post_update', 'mytheme_deeplpro_translate_post_link_after', 80, 5 );
function mytheme_deeplpro_translate_post_link_after( $post_array, $strings_to_translate, $response, $WP_Post, $no_translation ) {

	$new_post_custom_data = json_decode( get_post_meta( $WP_Post->ID, 'hotelwp_meta', true), true );

	$changed = false;
	if( $response['translations'] ) foreach( $response['translations'] as $key => $translation ) {
		if( substr( $key, 0, 8 ) == 'mytheme_' ) {
			$real_key = substr( $key, 8 );
			$new_post_custom_data[$real_key] = $translation;
			$changed  = true;

		}
	}
	if( $changed ) {
		update_post_meta( $WP_Post->ID, 'hotelwp_meta', json_encode( $new_post_custom_data, JSON_UNESCAPED_UNICODE  ) );
	}
}

