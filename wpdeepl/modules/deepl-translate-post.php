<?php

function deepl_get_translation_results_in_admin() {
	if ( isset( $_GET['translated'] ) ) {
		if ( filter_var( $_GET['translated'], FILTER_VALIDATE_INT ) == 1 ) {
			$deepl_result = 'success';
			$message = __( 'This post has been translated.', 'wpdeepl' );
		}
		else {
			$deepl_result = 'warning';
			$message = __( 'The translation has failed. See logs for details', 'wpdeepl' );
		}
	}
	else {
		$message = '';
		$deepl_result = '';
	}
	return compact('message', 'deepl_result');
}
// notice for classic editor
add_action( 'admin_notices', 'deepl_admin_notice_nogutenberg_deepl_translated' );
function deepl_admin_notice_nogutenberg_deepl_translated() {
	$results = deepl_get_translation_results_in_admin();
	extract( $results );
	if( !empty( $message ) ) {
		echo '
		<div class="notice notice-'. $deepl_result. ' is-dismissible">
		      <p>' . $message . '</p>
		</div>'; 

	}
}
// notice for gutenberg // obsolete since we reload after translation
add_action('admin_footer', 'deepl_admin_notice_gutenberg_deepl_translated');
function deepl_admin_notice_gutenberg_deepl_translated() {
	$results = deepl_get_translation_results_in_admin();
	extract( $results );

	if ( $message && strlen( $message) && !empty( $deepl_result ) ) :
	?>
	<script type="text/javascript">

		jQuery(document).ready(function() {
			if( typeof wp !== 'undefined' && typeof wp.data !== 'undefined' ) {
				var result_type = '<?php echo $deepl_result; ?>';
				if( result_type == 'success' ) {
					wp.data.dispatch( 'core/notices').createSuccessNotice( '<?php echo addslashes( $message ); ?>', 'deepl-result');
				}
				else {
					wp.data.dispatch( 'core/notices').createWarningNotice( '<?php echo addslashes( $message ); ?>', 'deepl-result');	
				}
			}
			else {
				// console.log('no gutenberg');
			}
		});
	</script>

	<?php
	endif;
}
//https://wpdeepl.zebench.net/wp-admin/post.php?post=134&action=deepl_translate_post_do&deepl_source_lang=auto&deepl_target_lang=en_GB&behaviour=replace&_deeplnonce=60a9264039
add_action('admin_init', 'deepl_maybe_translate_post' );
function deepl_maybe_translate_post() {
	$local_debug = true;
	$local_debug = false;
	if ( !isset( $_GET['deepl_action']) ) {
		if( $local_debug ) die('1');
		return;
	}
	if ( !isset($_GET['deepl_action']) || html_entity_decode( $_GET['deepl_action'] ) != 'deepl_translate_post_do' ) {
		if( $local_debug ) die('2');
		return;
	}
	if ( !isset( $_GET['_deeplnonce'] ) ) {
		if( $local_debug ) die('3');
		return;
	}
	if ( !wp_verify_nonce( $_GET['_deeplnonce'], DeepLConfiguration::getNonceAction() ) ) {
		if( $local_debug ) die('4');
		return;
	}



	$args = array();
	$args['ID'] = filter_var( $_GET['post'] , FILTER_VALIDATE_INT );
	$args['source_lang'] = html_entity_decode( $_GET['deepl_source_lang'] );
	$args['target_lang'] = html_entity_decode( $_GET['deepl_target_lang'] );
	$args['behaviour'] = html_entity_decode( $_GET['behaviour'] );
	$args['force_polylang'] = filter_var( $_GET['deepl_force_polylang'], FILTER_VALIDATE_BOOLEAN );

	$translated = deepl_translate_post_link( $args );
	//echo "translaed ";	var_dump( $translated);

	$redirection = admin_url( '/post.php?post=' . $args['ID'] . '&action=edit&translated=' );
	if ( $translated )
		$redirection .= '1';
	else
		$redirection .= '0';
	if( $local_debug ) die('5 = ' . $redirection);
		
		//https://wpdeepl.zebench.net/wp-admin/post.php?post=130&action=edit&translated=1
//	var_dump( $redirection);	die('gloubi boulga');



	wp_redirect( $redirection );
	exit();
}

function deepl_already_exists_in_polylang( $post_id, $target_lang ) {
	// checking if Polylang equivalent already exists;
	$post_translations_terms = wp_get_object_terms( $post_id, 'post_translations' );
	if( is_array( $post_translations_terms ) && count( $post_translations_terms ) ) {
        $post_translations = $post_translations_terms[0];
        $pll_link_array = maybe_unserialize( $post_translations->description );
        if( strlen( $target_lang ) == 2 ) {
        	$pll_language = strtolower( $target_lang  );	
        }
        else {
        	$pll_language = strtolower( substr( $target_lang, 0, 2 ) );
        }
        if( isset( $pll_link_array[$pll_language] ) ) {
        	$post_id = $pll_link_array[$pll_language];
        	return $post_id;
        }
    }
    return false;

}

function deepl_translate_post_link( $args ) {

	$debug = true;
	$debug =  false;

	$defaults = array(
		'ID'	=> false,
		'source_lang'	=> false,
		'target_lang' => DeepLConfiguration::getDefaultTargetLanguage(),
		'behaviour'	=> DeepLConfiguration::getMetaBoxDefaultBehaviour(),
		'bulk'	=> false,
		'bulk_action'	=> false,
		'force_polylang' => false,
	);
	$args = wp_parse_args( $args, $defaults );
	//plouf( $args );//	die('oiezrjzeoijr');
	extract( $args );

	$WP_Post = get_post( $args['ID'] );

	if( !$force_polylang &&  DeepLConfiguration::usingPolylang() ) {
		$translation_id =  deepl_already_exists_in_polylang( $WP_Post->ID, $target_lang );
//		var_dump( $translation_id ); die('okazmeaz4aze6848846');

		if( $translation_id ) {
			$log = array ($WP_Post->ID, "already exists in $target_lang", $translation_id );
			wpdeepl_log( $log, 'errors');
			$redirect = get_permalink( $translation_id );
			wp_redirect( $redirect );
			exit();
		}
	}

	
	$strings_to_translate = array();


	foreach ( array( 'post_title', 'post_content', 'post_excerpt' ) as $key ) {
		if( get_option( 'wpdeepl_t' . $key ) !== '' )
			$strings_to_translate[$key] = $WP_Post->$key;
	}

	//plouf( $strings_to_translate);	echo "\n excertp = "; var_dump( $WP_Post->excerpt );	echo "\n post excertp"; var_dump( $WP_Post->post_excerpt) ;	die('okaze4az6e84e68a4e');


	// shortcodes
	foreach( $strings_to_translate as $key => $string ) {
		preg_match_all( '#\[([a-z_0-9]+)]#m', $string, $matches );
		if( $matches ) {
			//plouf( $matches, $key );
			foreach( $matches[0] as $found ) {
				//echo "\n '$found' to '<x>$found</x>' ";
				$strings_to_translate[$key] = str_replace( $found, '<x>' . $found .'</x>', $strings_to_translate[$key] );

			}

		}

	}
	
	if( $debug ) plouf( $strings_to_translate,  "avant filtre");

	$strings_to_translate = apply_filters( 
		'deepl_translate_post_link_strings', 
		$strings_to_translate, 
		$WP_Post, 
		$target_lang, 
		$source_lang,
		$bulk, 
		$bulk_action
	);
	if( $debug ) plouf( $strings_to_translate,  "apres filtre");
	if( $debug ) die('ok');
	

	$no_translation = array();
	if( isset( $strings_to_translate['_notranslation'] ) ) {
		$no_translation = $strings_to_translate['_notranslation'];
		unset( $strings_to_translate['_notranslation'] );
	}


	//plouf( $strings_to_translate);	 die('okzeùlrkzpeorrkpzo');

	$response = deepl_translate( $source_lang, $target_lang, $strings_to_translate );

	//plouf( $strings_to_translate," from $source_lang to $target_lang ");	plouf( $response );
	//die('zemozjpriook');

	$log = array('ID'	=> $WP_Post->ID );
	$log = array_merge( $log, json_decode( json_encode( $response ), true ) );
	$return = false;

	$post_array = array();
	if ( is_array( $response ) &&  $response['success'] ) {
		$post_array = array(
			'ID'	=> $WP_Post->ID
		);
		foreach ( $response['translations'] as $key => $translation ) {
			// shortcode
			$translation = preg_replace( '#<x>(.+?)<\/x>#', '\1', $translation );
			$post_array[$key] = $translation;
		}


		//plouf( $post_array );		die('oaze5az46ea6ze4a48eak');
		$post_array = apply_filters('deepl_translate_post_link_translated_array', 
			$post_array,
			$strings_to_translate, 
			$response, 
			$WP_Post, 
			$no_translation,
			$bulk,
			$bulk_action
		);

		do_action('deepl_translate_before_post_update', 
			$post_array, 
			$strings_to_translate, 
			$response, 
			$WP_Post, 
			$no_translation, 
			$bulk, 
			$bulk_action 
		);


		if( isset( $post_array['post_content']  ) ) {
			$post_array['post_content'] = html_entity_decode( $post_array['post_content'] );

		}
//		plouf( $response );		plouf( $post_array , " arz)ozoz");		plouf( wp_slash( $post_array ) );		 die('azezeeeaok');
		
		if (count( $post_array ) > 1 ) {
			$return = wp_update_post( wp_slash( $post_array ) );

			//$translated_post = get_post( $post_array['ID'] ); echo "\n translated = \n" . $translated_post->post_content;die('ozerkozerk');
			//var_dump( $updated);
			
			do_action('deepl_translate_post_link_translation_success', $response, $WP_Post );
		} else {
			$log[] = __('Nothing to update', 'wpdeepl' );
			wpdeepl_log( $log, 'errors');
			do_action('deepl_translate_post_link_translation_error', $response, $WP_Post );
		}
	} else {
		$log[] = __('Translation error', 'wpdeepl' );
		$log[] = json_encode( $response );
		do_action('deepl_translate_post_link_translation_error', $response, $WP_Post );
		wpdeepl_log( $log, 'errors');
	}


	do_action('deepl_translate_after_post_update', 
		$post_array, 
		$strings_to_translate, 
		$response, 
		$WP_Post, 
		$no_translation,
		$bulk, 
		$bulk_action
	);
	do_action('deepl_translate_post_link_after', 
		$response, 
		$strings_to_translate, 
		$WP_Post, 
		$no_translation,
		$bulk, 
		$bulk_action 
	);
	return $return;
}