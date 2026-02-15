<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class DeeplConfiguration {
	static function getAPIKey() {
		return apply_filters( 'wpdeepl_' . __METHOD__, trim( get_option( 'wpdeepl_api_key' ) ) );
	}
	static function getAPIServer() {
		$choice = get_option( 'wpdeepl_api_server');
		if ( !$choice ) {
			$choice = 'paid_api';
		}
		$possibilities = self::getDeepLAPIServers();

		if ( isset( $possibilities[$choice] ) ) {
			return apply_filters( 'wpdeepl_' . __METHOD__,  $possibilities[$choice]['server'], $choice, $possibilities );
		}
		else {
			return apply_filters( 'wpdeepl_' . __METHOD__, false, $choice, $possibilities );
		}
	}

	static function getDeeplAPIServers() {
		$array = array(
			'paid_api'	=> array(
				'server'	=> 'https://api.deepl.com/v2/',
				'description'	=> __('Regular paid API Plan (https://api.deepl.com/v2/)', 'wpdeepl')
			),
			'free_plan'	=> array(
				'server'	=> 'https://api-free.deepl.com/v2/',
				'description'	=> __('Free API plan (https://api-free.deepl.com)', 'wpdeepl' )
			),
		);
		return $array;
	}

	static function getLocaleNameForLn( $ln, $type = 'source ' ) {
		$wp_locale = get_locale();
		$languages_data = self::DefaultsAllLanguages();
		foreach( $languages_data as $locale => $locale_data )  {
			$compare = $type == 'target' ? 'astarget' : 'assource';
			if( strtolower( $locale_data[$compare] ) == $ln ) {
				return $locale_data['labels'][$wp_locale];
			}
		}
		return false;
	}

	static function usingGlossaries() {
		return get_option('wpdeepl_glossaries') && is_array( get_option('wpdeepl_glossaries') );
	}

	static function getLocaleNameForIsoCode2( $isocode2, $type = 'source ' ) {
		$wp_locale = get_locale();
		$languages_data = self::DefaultsAllLanguages();
		foreach( $languages_data as $locale => $locale_data )  {
			if( strtolower( $locale_data['isocode'] ) == $isocode2 ) {
				$label = $locale_data['labels'][$wp_locale];
				$label = preg_replace('#\(.*?\)#ism', '', $label );
				return trim( $label );
			}
		}
		return false;
	}

	static function getLogLevel() {
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option( 'wpdeepl_log_level') );
	}

	static function getActivePostTypes() {
		$return = get_option( 'wpdeepl_metabox_post_types' ) ? get_option( 'wpdeepl_metabox_post_types' ) : array();
		$return = get_option( 'wpdeepl_pro_post_types' ) ? array_merge( $return, get_option( 'wpdeepl_pro_post_types' ) ) : $return;
		
		$return = array_unique( $return );
		return apply_filters( 'wpdeepl_' . __METHOD__, $return );

	}
	static function getMetaBoxPostTypes() {
		$post_types = get_option( 'wpdeepl_metabox_post_types' );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}
		return apply_filters( 'wpdeepl_' . __METHOD__, $post_types );
	}
	static function getMetaBoxDefaultBehaviour() {
		return 'replace';
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option( 'wpdeepl_metabox_behaviour' ) );
	}
	static function getDefaultTargetLanguage() {
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option( 'wpdeepl_default_language' ) );
	}

	static function getDisplayedLanguages() {
		$value = get_option( 'wpdeepl_displayed_languages') ;
		return apply_filters( 'wpdeepl_' . __METHOD__, $value );
	}
	static function getMetaBoxContext() {
		$context = get_option( 'wpdeepl_metabox_context' );
		if ( empty( $context ) ) {
			$context = 'side';
		}
		return apply_filters( 'wpdeepl_' . __METHOD__, $context );
	}
	static function getMetaBoxPriority() {
		$priority = get_option( 'wpdeepl_metabox_priority' );
		if ( empty( $priority ) ) {
			$priority = 'high';
		}
		return apply_filters( 'wpdeepl_' . __METHOD__, $priority );
	}

	static function usingMultilingualPlugins() {
		return false;
	}

	static function getNonceAction() {
		return apply_filters( 'wpdeepl_' . __METHOD__, 'deepl_translate_post' );
	}

	static function getActiveGlossaryFor( $source_language, $target_language) {
		$source = !empty( $source_language ) ? strtolower( substr( $source_language, 0, 2 ) ) : false;
		$target = !empty( $target_language ) ? strtolower( substr( $target_language, 0, 2 ) ) : false;
		$active_glossary_id = false;
		if( $source && $target) $active_glossary_id = get_option( 'wpdeepl_glossary_' . $source. '_' . $target );
		return apply_filters( 'wpdeepl_' . __METHOD__, $active_glossary_id );
	}




/**
 * 0 = activated
 * 1 = activated and setup
 */
	static function isPluginInstalled() {
		return get_option( 'wpdeepl_plugin_installed' );
	}


	static function validateLang( $language_string, $output = 'assource' ) {
		// output source will return 2 characters
		// output source will return AA(-AA)

		$language_string = htmlspecialchars( $language_string );
		$language_string = str_replace( '-', '_', $language_string );

		if( $language_string == 'NB' ) {
			$language_string = 'NO';
		}

		$all_languages = DeeplConfiguration::DefaultsAllLanguages();
		$locale = get_locale();
		//wpdeepl_debug_display( $locale, "\n locale" );


		//if( $language_string == 'nn_NO' ) 			$language_string = 'no_NO';

		if( strpos( $locale, '_formal' ) != false ) {
			$locale = str_replace( '_formal', '', $locale );
		}
		if( strpos( $locale, '_informal' ) != false ) {
			$locale = str_replace( '_informal', '', $locale );
		}

		$language = false;
		if( $output == 'astarget' ) {
			//wpdeepl_debug_display( $all_languages[$language_string]," lang  $language_string  locale $locale"); 			wpdeepl_debug_display( $all_languages);
		}
		if ( isset( $all_languages[$language_string] ) ) {
			$language = $all_languages[$language_string];
		}
		else {
			foreach ( $all_languages as $try_locale => $try_language ) {
				if ( $try_language['allcaps'] == strtoupper( $language_string ) ) {
					$language = $try_language;
					break;
				}
				if ( $try_language['isocode'] == strtoupper( $language_string ) ) {
					$language = $try_language;
					break;
				}
				if( $output == 'astarget' && $try_language['astarget'] == strtoupper( $language_string ) ) {
					$language = $try_language;
					break;
				}
			}
		}

		if ( !$language ) {
			return false;
		}

		if( $output == 'astarget' ) {
			//wpdeepl_debug_display( $language, "language, locale $locale");
		}
		$language['label'] = isset( $language['labels'][$locale] ) ? $language['labels'][$locale] : false;

		if ( $output == 'assource' ) {
			return $language['assource'];
		}
		elseif ( $output == 'astarget' ) {
			return $language['astarget'];
		}
		elseif ( $output == 'isocode' ) {
			return $language['isocode'];
		}
		elseif ( $output == 'full' ) {
			return $language;
		}
		elseif ( $output == 'label' ) {

			return $language['label'];
		}
		else {
			return $language['isocode'];
		}
	}
	static function DefaultsMetaboxBehaviours() {
		$array = array(
			'replace'		=> __( 'Replace content', 'wpdeepl' ),
			'append'		=> __( 'Append to content', 'wpdeepl' )
		);
		return apply_filters( 'wpdeepl_' . __METHOD__ , $array );
	}

	static function getFormalityLevel( $target_lang = false ) {
		if ( $target_lang ) {
			$formality_level = get_option('wpdeepl_formality_' . $target_lang );
		}
		if( !$formality_level || $formality_level == 'wpdeepl' ) {
			$formality_level = get_option( 'wpdeepl_default_formality' );
		}

		/**
		 * . This feature currently only works for target languages DE (German), FR (French), IT (Italian), ES (Spanish), NL (Dutch), PL (Polish), PT-PT, PT-BR (Portuguese) and RU (Russian). Setting this parameter with a target language that does not support formality will fail, unless one of the prefer_... options are used. Possible options are:
		 * default (default)
more - for a more formal language
less - for a more informal language
prefer_more - for a more formal language if available, otherwise fallback to default formality
prefer_less - for a more informal language if available, otherwise fallback to default formality
		 * */

		if( $formality_level != 'wpdeepl' ) {
			$formality_level = 'prefer_' . $formality_level;
		}

		return apply_filters( 'wpdeepl_' . __METHOD__, $formality_level, $target_lang );
	}

	static function getLanguagesAllowingFormality() {
			// This feature currently works for target languages DE, FR, IT, ES, NL, PL, PT-PT, PT-BR, RU, JA.
			return array('de_DE', 'fr_FR', 'it_IT', 'es_ES', 'es_419', 'nl_NL', 'ja_JP', 'pl_PL', 'pt_PT', 'pt_BR', 'ru_RU' );
		}	

	static function DefaultsISOCodes() {
		$locale = get_locale();
		$locale = str_replace( '_formal', '', $locale );
		$locale = str_replace( '_informal', '', $locale );
		$all_languages = DeeplConfiguration::DefaultsAllLanguages();
		$languages = array();
		foreach ( $all_languages as $isocode => $labels ) {
			$languages[$isocode] = isset( $labels['labels']) && isset( $labels['labels'][$locale] ) ? $labels['labels'][$locale] : false;
		}
		return apply_filters( 'wpdeepl_' . __METHOD__ , $languages );
	}

	static function getLanguageFromIsoCode2( $isocode2, $context = 'target' ) {
		$isocode2 = strtolower( $isocode2 );
		$all_languages = self::DefaultsAllLanguages();
		$key = ( $context == 'source' ) ? 'assource' : 'astarget';
		foreach( $all_languages as $lang => $language ) {
			$short = substr( strtolower( $language[$key] ), 0, 2 );
			if( $short == $isocode2 ) {
				return $lang;
			}
		}
		return false;
	}

	static function DefaultsAllLanguages() {
		$languages = array();

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
		    require_once( ABSPATH . 'wp-admin/includes/file.php' );
		    if ( ! WP_Filesystem() ) {
		        return $csv_data; // Retourne un tableau vide en cas d'échec
		    }
		}
		$file_path = wp_normalize_path( trailingslashit( WPDEEPL_PATH ) . 'languages.csv' );
		$csv_contents = $wp_filesystem->get_contents( $file_path ); 
		$csv_data = array();

		if ( $csv_contents ) {
		    $lines = explode( "\n", $csv_contents ); 
		    foreach ( $lines as $line ) {
		        // Ignorer les lignes vides
		        $line = trim( $line );
		        if ( empty( $line ) ) {
		            continue;
		        }
		        $data = str_getcsv( $line, ',', '"', "\\" ); 

		        if ( is_array( $data ) && ! empty( $data ) ) {
		            $csv_data[] = $data;
		        }
		    }
		}
		
		//wpdeepl_debug_display( $csv_data, " alors data");
		$headers = array_shift( $csv_data );
		if( $csv_data ) foreach ( $csv_data as $labels ) {
			$labels = array_combine( $headers, $labels );

			$locale = $labels['locale'];
			unset( $labels['locale'] );

			$extra_country = false;
			if ( strlen( $labels['astarget'] ) > 2  ) {
				$extra_country = substr( $labels['astarget'], 3 );
			}

			$languages[$locale] = array();
			foreach ( array('allcaps', 'assource', 'astarget', 'isocode' ) as $key ) {
				$languages[$locale][$key] = $labels[$key];
				unset( $labels[$key] );
			}


			if ( $extra_country ) {
				foreach ( $labels as $code => $label ) {
					$labels[$code] = $label . ' (' . $extra_country . ')';
				}
			}
			$languages[$locale]['labels'] = $labels;
		}

		return apply_filters( 'wpdeepl_' . __METHOD__, $languages );

		// used for.. something else
		/* translators: strings used in extended functions. Target lang */
		$test = __('Translate to %s', 'wpdeepl' );
		/* translators: strings used in extended functions. Number of posts */
		$test = __('Translated %d posts.', 'wpdeepl');
		$test = __('Bulk translate', 'wpdeepl');
		$test = __('Content types', 'wpdeepl');
		$test = __( 'Translate contents', 'wpdeepl' );
		$test = __( 'Select which kind of content you want to be able to translate', 'wpdeepl' );
		$test = __( 'Target languages for bulk actions', 'wpdeepl' );
		$test = __( 'Show these target languages in bulk menu', 'wpdeepl' );
	}

 // might serve somewhere
 	static function getContentTypes() {
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option('wpdeepl_contents_to_translate') );
	}
	static function getTargetLocales() {
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option( 'wpdeepl_target_locales') );
	}

	static function usingPolylang() {
		return function_exists( 'pll_the_languages' );
	}

	static function getBulkTargetLanguages() {
		return apply_filters( 'wpdeepl_' . __METHOD__, get_option( 'wpdeepl_bulk_target_locales' ) );
	}
}

