<?php

// $strings_to_translate = apply_filters( 'deepl_translate_post_link_strings', $strings_to_translate, $WP_Post );
add_filter( 'deepl_translate_post_link_strings', 'idg_add_meta_data_to_translate', 10, 4 );
function idg_add_meta_data_to_translate( $strings_to_translate, $WP_Post, $target_lang, $source_lang ) {
	$debug = false;
	if ( $multi_titles = get_post_meta( $WP_Post->ID, 'multi_title', true ) ) {
		$multi_titles = json_decode( $multi_titles, true );
		if ( $debug ) plouf($multi_titles);
		foreach ( $multi_titles['titles'] as $multi_title => $multi_title_data ) {
			$key = 'mtitle#' . $multi_title;
			$strings_to_translate[$key] = $multi_title_data['value'];
			if ( isset( $multi_title_data['additional'] ) ) foreach ( $multi_title_data['additional'] as $sub_multi_title => $sub_multi_title_value )  {
				$sub_key = 'mtitle#' . $multi_title . '#' . $sub_multi_title;
				$strings_to_translate[$sub_key] = $sub_multi_title_value;


			}
		}

	}
	if ( $debug ) plouf($strings_to_translate);

	return $strings_to_translate;
}

//do_action('deepl_translate_post_link_translation_success', $response, $WP_Post );
add_action('deepl_translate_post_link_translation_success', 'idg_update_meta_data_translated', 10, 2  );
function idg_update_meta_data_translated( $response, $WP_Post ) {

	$debug = false;
	if ( $multi_titles = get_post_meta( $WP_Post->ID, 'multi_title', true ) ) {
		$multi_titles = json_decode( $multi_titles, true );
		if ( $debug ) plouf($multi_titles, " original");
		foreach ( $response['translations'] as $key => $translation ) {

			$translation = deepl_unicode_decode( $translation );
			if ( substr( $key, 0, 7 ) == 'mtitle#' ) {
				$explode_key = explode( '#', $key );
				if ( count( $explode_key ) == 2 ) {
					$main_key = $explode_key[1];
					$multi_titles['titles'][$main_key]['value'] = $translation;
					//echo "\n on update $main_key value = '$translation'";

				}
				elseif ( count( $explode_key ) == 3 ) {
					$main_key = $explode_key[1];
					$additionnal_key = $explode_key[2];
					$multi_titles['titles'][$main_key]['additional'][$additionnal_key] = $translation;
					//echo "\n on update $main_key / add $additionnal_key value = '$translation'";
				}
				else {
					//plouf($explode_key, " no 2 3");
				}
			}
			else {
				//echo " no match on $key";
			}
		}
	if ( $debug ) plouf($multi_titles);
	$json_data = json_encode( $multi_titles, JSON_UNESCAPED_UNICODE);
	if ( $debug ) var_dump( $json_data );
	update_post_meta( $WP_Post->ID, 'multi_title', $json_data );

	if ( $debug ) die('zeroijrij');
	return true;
	}

	return false;
}