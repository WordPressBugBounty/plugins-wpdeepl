<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function deepl_get_translation_results_in_admin() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Heavily sanitizing to check if one param is set to 1
	if ( isset( $_GET['translated'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Heavily sanitizing to check if one param is set to 1
		if ( filter_var( wp_unslash($_GET['translated']), FILTER_VALIDATE_INT ) == 1 ) {
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
		<div class="notice notice-' . esc_attr($deepl_result) . ' is-dismissible">
		      <p>' . esc_html($message) . '</p>
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
				var result_type = '<?php echo esc_js($deepl_result); ?>';
				if( result_type == 'success' ) {
					wp.data.dispatch( 'core/notices').createSuccessNotice( '<?php echo esc_js( $message ); ?>', 'deepl-result');
				}
				else {
					wp.data.dispatch( 'core/notices').createWarningNotice( '<?php echo esc_js( $message ); ?>', 'deepl-result');	
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
//https://wpdeepl.zebench.net/wp-admin/post.php?post=134&action=deepl_translate_post_do&wpdeepl_source_lang=auto&wpdeepl_target_lang=en_GB&behaviour=replace&_wpdeeplnonce=60a9264039
add_action( 'admin_init', 'deepl_maybe_translate_post' ); 

function deepl_maybe_translate_post() {

	$action = isset( $_GET['wpdeepl_action'] ) ? sanitize_text_field( wp_unslash($_GET['wpdeepl_action']) ) : '';
	
	if ( ! $action || 'deepl_translate_post_do' !== $action ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) { 
		wp_die( esc_html__( 'You are not allowed to translate posts.', 'wpdeepl' ) );
	}
	
	$nonce = filter_input( INPUT_GET, '_wpdeeplnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$nonce_action = DeepLConfiguration::getNonceAction();

	if ( ! $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
		wp_die( esc_html__( 'Security check failed.', 'wpdeepl' ) );
	}
	
	$args = array();
	$args['ID'] = filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT ); 
	if ( ! $args['ID'] ) { return; } // Arrêt si l'ID est invalide
	
	// Langues et comportement : Sanitization en texte simple.
	$args['source_lang'] = sanitize_text_field( filter_input( INPUT_GET, 'wpdeepl_source_lang' ) ); 
	$args['target_lang'] = sanitize_text_field( filter_input( INPUT_GET, 'wpdeepl_target_lang' ) );
	$args['behaviour'] = sanitize_text_field( filter_input( INPUT_GET, 'behaviour' ) );
	
	// Force Polylang : Validation en BOOLEAN, puis cast strict.
	$args['force_polylang'] = (bool) filter_input( INPUT_GET, 'wpdeepl_force_polylang', FILTER_VALIDATE_BOOLEAN );

	//wpdeepl_debug_display( $_GET, " get");	wpdeepl_debug_display( $_POST, "post");	wpdeepl_debug_display( $args); 	die('zerpozerokre68rezr48');

	// Lancement de la traduction (la fonction doit être sécurisée ailleurs)
	$translated = deepl_translate_post_link( $args );

	$redirection = admin_url( 'post.php' );
	
	$query_args = array(
		'post'       => $args['ID'],
		'action'     => 'edit',
		'translated' => $translated ? '1' : '0',
	);

	// wp_safe_redirect est content de recevoir une URL construite via add_query_arg
	$redirection = add_query_arg( $query_args, $redirection );
	wp_safe_redirect( $redirection );
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

	$defaults = array(
		'ID'	=> false,
		'source_lang'	=> false,
		'target_lang' => DeepLConfiguration::getDefaultTargetLanguage(),
		'behaviour'	=> DeepLConfiguration::getMetaBoxDefaultBehaviour(),
		'bulk'	=> false,
		'bulk_action'	=> false,
		'force_polylang' => false,
		'redirect'	=> true,
	);
	$args = wp_parse_args( $args, $defaults );
	//wpdeepl_debug_display( $args );//	die('oiezrjzeoijr');
	extract( $args );

	$WP_Post = get_post( $args['ID'] );

	if( !$force_polylang &&  DeepLConfiguration::usingPolylang() ) {
		$translation_id =  deepl_already_exists_in_polylang( $WP_Post->ID, $target_lang );
//		var_dump( $translation_id ); die('okazmeaz4aze6848846');

		if( $translation_id ) {
			$log = array ($WP_Post->ID, "already exists in $target_lang", $translation_id );
			wpdeepl_log( $log, 'errors');
			if( $redirect ) {
				$redirect = get_permalink( $translation_id );
				//die(" redirect $redirect " . __FUNCTION__ );
				wp_safe_redirect( $redirect );
				exit();

			}
			else {
				if( !$bulk_action )
					return  $translation_id;
			}
		}
	}
	
	$strings_to_translate = array();


	foreach ( array( 'post_title', 'post_content', 'post_excerpt' ) as $key ) {
		$option_key = 'wpdeepl_t' . $key;
		if( WPDEEPL_DEBUG ) echo "\n option key " . esc_html($option_key) . " = " . esc_html(get_option( $option_key ));
		if( get_option( $option_key ) !== '' )
			$strings_to_translate[$key] = $WP_Post->$key;
	}

	// shortcodes
	foreach( $strings_to_translate as $key => $string ) {
		preg_match_all( '#\[([a-z_0-9]+)]#m', $string, $matches );
		if( $matches ) {
			//wpdeepl_debug_display( $matches, $key );
			foreach( $matches[0] as $found ) {
				//echo "\n '$found' to '<x>$found</x>' ";
				$strings_to_translate[$key] = str_replace( $found, '<x>' . $found .'</x>', $strings_to_translate[$key] );

			}

		}

	}
	
	//if( WPDEEPL_DEBUG ) wpdeepl_debug_display( $strings_to_translate,  "avant filtre");

	$strings_to_translate = apply_filters( 
		'deepl_translate_post_link_strings', 
		$strings_to_translate, 
		$WP_Post, 
		$target_lang, 
		$source_lang,
		$bulk, 
		$bulk_action
	);
	//if( WPDEEPL_DEBUG ) wpdeepl_debug_display( $strings_to_translate,  "apres filtre");
	

	$no_translation = array();
	if( isset( $strings_to_translate['_notranslation'] ) ) {
		$no_translation = $strings_to_translate['_notranslation'];
		unset( $strings_to_translate['_notranslation'] );
	}


	//wpdeepl_debug_display( $strings_to_translate);	 die('okzeùlrkzpeorrkpzo');

	$response = deepl_translate( $source_lang, $target_lang, $strings_to_translate );
	if( WPDEEPL_DEBUG ) {
		//wpdeepl_debug_display( $response, " response de traduction");;
	}

	//wpdeepl_debug_display( $strings_to_translate," from $source_lang to $target_lang ");	wpdeepl_debug_display( $response );	die('zemozjpriook');

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


		//wpdeepl_debug_display( $post_array );		die('oaze5az46ea6ze4a48eak');
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
//		wpdeepl_debug_display( $response );		wpdeepl_debug_display( $post_array , " arz)ozoz");		wpdeepl_debug_display( wp_slash( $post_array ) );		 die('azezeeeaok');

		if( WPDEEPL_DEBUG ) {
			//wpdeepl_debug_display( $post_array, " nouvelle post array ");;
		}
		
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