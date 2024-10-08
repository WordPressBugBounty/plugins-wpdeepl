<?php

class DeepLApiTranslate extends DeepLApi {
	protected $endPoint = 'translate';

	protected $langs = array( 'EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL', 'RU' );
	protected $syntaxes = array( 'xml', 'html' );

	public $allow_cache = true;
	protected $http_mode = 'POST';

	protected $request = array(
		'source_lang' 			=> null,
		'target_lang' 			=> false,
		'tag_handling' 			=> 'html',
		'ignore_tags'			=> 'x,code,pre',
		'split_sentences' 		=> 1,
		'preserve_formatting' 	=> 1,
		'formality'				=> 'default',
		//'glossary_id'			=> false,
		'text' 					=> array(),

	);

	public function getRequest() {
		return $this->request;
	}
	
	protected function prepareString( $original_string, $index ) {
		$string = $original_string;

		$string = preg_replace_callback( '/\\\\u( [0-9a-fA-F]{4} )/', function ( $match ) {
 		
 		$string = mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
		}, $string );

		$string = str_replace('&nbsp;', ' ', $string );
		//$string = str_replace('&amp;', '&', $string );
		$string = str_replace('&', '%26', $string );
		//$string = str_replace( "\n", '<br class="98z4er98z4e968r4" />', $string );

		if( $index == 'slug' ) {
			$string = str_replace( '-' , ' ', $string );
		}

		//$string = addslashes( $string );

		return apply_filters( __METHOD__, $string, $original_string );


		$string = str_replace( '&nbsp;', ' ', $string );
		// mandatory for POST requests
		$string = urlencode($string);
		//$string = htmlspecialchars($string);
		$string = trim( $string );




		//echo "\n ORIG $original_string \n = $string";

	}

	protected function splitString( $string, $length = 95000 ) {
		$splitted_strings = array();

		if( strpos( $string, '<!-- wp:') !== false ) {
			$mode = 'blocks';
		}
		else {
			$mode = 'lines';
		}

		//echo "\n mode $mode / $length / string = " . strlen( $string );

		if( $mode == 'blocks' ) {

			$bits = array();
			while( strpos( $string, '<!-- /wp' ) !== false ) {
				preg_match('#<!-- \/wp:([^>]+?)-->#ism', $string, $match );

				$position = strpos( $string, $match[0] );
				$bit = substr( $string, 0, $position + strlen( $match[0] ) );
				$bits[] = $bit;
				$string = substr( $string, strlen( $bit ) );
			}
			$bits[] = $string;


			$current_string = '';
			foreach( $bits as $index => $bit ) {
				if( strlen( $current_string ) + strlen( $bit  ) < $length ) {
					$current_string .= $bit;
				}
				else {
					$splitted_strings[] = $current_string;
					$current_string = $bit;
				}
			}
			$splitted_strings[] = $current_string;	

		}
		else {
			while( strlen( $string ) > $length ) {
				$splitted_string = mb_substr( $string, 0, $length );
				$position = mb_strrpos( $splitted_string, "\n\n" );
				$real_split = mb_substr( $string, 0, $position );
				$string = mb_substr( $string, $position );
				$splitted_strings[] = $real_split;
			}
			$splitted_strings[] = $string;
		}


		foreach( $splitted_strings as $i => $string ) {
			echo "\n #$i = " . strlen( $string );
		}
		return $splitted_strings;
	}

	public function getTranslations( $strings = array(), $return_originals = false ) {
		if ( !is_array( $strings ) ) {
			return new WP_Error( "wrong type", "Parameter is not an array" );
		}

		$debug = true;
		$debug = false;

		$this->finalPrepareRequest();

		$response = array();
		$translated = array();
		
		//plouf( $strings , " zeirpejzirjzi" );		//die( 'okokok' );

		

		$extra_strings = array();
		$extra_prepared_strings = array();
		$prepared_strings = array();

		if( $debug ) plouf( $strings);
		if( $debug ) die('ok');
		

		foreach( $strings as $string_index => $string ) {
			$prepared_string = $this->prepareString( $string, $string_index );

			if( is_array( $prepared_string ) ) {
				wpdeepl_log( array( __METHOD__, "is array", json_encode( $prepared_string ), json_encode( $_REQUEST) ), 'errors' );
				return false;

			}
			// 2.2 20230502
			elseif( strlen( $prepared_string ) > 105000 ) {
				$splitted_strings = $this->splitString( $string );
				//echo "\n avant = " . strlen( $string ) ."  apres =" . strlen( implode("", $splitted_strings ) );
				
				$real_index = $string_index;
				$string_index = false;

				foreach( $splitted_strings as $i => $splitted_string ) {
					$prepared_string = $this->prepareString( $splitted_string, $string_index );
					if( $i == 0) {
						$extra_strings[$real_index .':0'] = $string;
						$prepared_strings[$real_index .':0'] = $prepared_string;
					}
					else {
						$extra_strings[$real_index . ':' . $i] = $string;
						$extra_prepared_strings[$real_index . ':' . $i] = $prepared_string;

					}
				}


			}
			//echo "\n $string_index = " . strlen( $string ) ." / " . strlen( $prepared_string );
			if( $string_index ) 
				$prepared_strings[$string_index] = $prepared_string;
		}

		$need_multiple_requests = false;
		if( count( $extra_prepared_strings ) ) {
			$need_multiple_requests = true;
		}

		$strings_requests = array();
		$strings_requests[] = $prepared_strings;
		if( count( $extra_prepared_strings ) ) {
			foreach( $extra_prepared_strings as $index => $string ) {
				$strings_requests[] = array( $index => $string );
			}
		}
		$translated = array();

		//plouf( $strings_requests );		plouf( $this );		 die('oazeazepùiprjk');



		foreach( $strings_requests as $strings_request ) {
			$translations = $this->requestTranslation( $strings_request, $return_originals );
			if ( is_wp_error( $translations ) ) {
				return $translations;
			}
			foreach( $translations as $key => $string ) {
				$string = str_replace('<br class="98z4er98z4e968r4" />', "\n", $string );
				$string = str_replace('%26', '&', $string );
				$translations[$key] = $string;

			}
			//plouf( $translations ); die('zerepzijrpjr');
			foreach( $translations as $index => $translation ) {

				$real_index = $index;
				if (count( $strings_requests ) > 1 ) {
					$explode = explode(':', $index );
					if( count( $explode ) > 1 ) {
						$real_index = $explode[0];
					}
				}
				if( isset( $translated[$real_index] ) ) {
					$translated[$real_index] .= $translation;
				}
				else {
					$translated[$real_index] = $translation;
				}
			}
		}
		if( isset( $translated['slug'] ) ) {
			$translated['slug'] = str_replace( ' ', '-', $translated['slug'] );
		}

		//plouf( $this);
		//plouf( $strings_requests , "strings reuqests"); 		plouf( $translated, " TRANSLATED" ); 		die('az54eaze684eaok');

		return $translated;
	}

	protected function requestTranslation( $strings, $return_originals ) {
		$this->resetTexts();
		$translated = array();

		//$this->allow_cache = false;

		$string_indexes = array();
		$i = 0;
		$cache_id_strings = '';
		//plouf( $strings) ;
		foreach ( $strings as $string_index => $string ) {
			if( empty( trim( $string ) ) ) {
				unset( $strings[$string_index] );
			}
		}
		foreach ( $strings as $string_index => $string ) {

			$string_indexes[$i] = $string_index;
			$i++;
			$cache_id_strings .= $string;
			$this->addText( $string );
			if ( $return_originals ) {
				$translated['_original_' . $string_index] = urldecode($string);
			}
		}

		$cache_id = ( $this->request['source_lang'] ) ? $this->request['source_lang'] : 'AUTO';
		$cache_id .= ':' . $this->request['target_lang'] . ':' . md5( $cache_id_strings );
		$this->setCacheID( $cache_id );
//		plouf( $strings, "strings donc cache id $cache_id");

		//plouf($this);		die('okaziejaiej');
		if ( !$this->isValidRequest() ) {
			$return = new WP_Error( "bad request", "Bof" );
			return $return;
		}
		

		$response = $this->request();
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( !array_key_exists( 'translations', $response ) ) {
			$return = new WP_Error( "bad response", $response );
		}

		foreach ( $response['translations'] as $index => $translation ) {
			$string_index = $string_indexes[$index];
			$translated_text = $translation->text;

			// 2021 04 14
			$translated_text = str_replace( '&lt;!--', '<!--', $translated_text );
			$translated_text = str_replace( '--&gt;', '-->', $translated_text );
			$translated_text = str_replace( '--&gt', '-->', $translated_text );

			$translated_text = str_replace( '-->', "-->\n", $translated_text );
			$translated[$string_index] = $translated_text;
		}

		//plouf( $response, "response" ); plouf( $string_indexes, "indexes"); plouf( $translated, "transalted");	
		//	die('okokeroz');
		//plouf( $translated, "translated");die('oizeeizjrirj');
		return $translated;
	}

	public function buildBody( $mode = 'POST', $endPoint = '' ) {
		$endPoint = $this->endPoint;
		$body = array();

		//if ( count( $this->headers ) )			$args['headers'] = $this->headers;

		//$request = array( 'auth_key' => $this->authKey );

//		plouf( $this->request); die(('ozeozrkzr'));


		foreach( array( 'target_lang', 'source_lang', 'tag_handling', 'ignore_tags','split_sentences', 'preserve_formatting', 'formality' ) as $tag ) {
			if( !empty( $this->request[$tag] ) ) {
				$body[] = "$tag=" . $this->request[$tag];
			}
		}
		$glossary_id = DeepLConfiguration::getActiveGlossaryFor( $this->request['source_lang'], $this->request['target_lang'] );
		if( $glossary_id ) 
			$body[] = "glossary_id=$glossary_id";

		if ( $mode == 'POST' ) {
			// fixed in 0.3 by schalipp https://wordpress.org/support/topic/translate-button-not-doing-anything/#post-10825822
			// modified in 1.0 to send all strings in one request

			foreach( $this->request['text'] as $string ) {
				if( !empty( $string ) )
					$body[] = "text="  . ( $string );
			}

		}
		else {
		}

		$body = implode( '&', $body );
		$this->final_request['body'] = $body;
		//plouf($this);die('ezorozrk');
		$this->saveRequest();
		return $body;
	}

/*
	public function resetQuery() {
		$this->request = array();
		$this->headers = array();
	}*/

	private function finalPrepareRequest() {

		/*
		Sets whether the translated text should lean towards formal or informal language. This feature currently only works for target languages "DE" (German), "FR" (French), "IT" (Italian), "ES" (Spanish), "NL" (Dutch), "PL" (Polish), "PT-PT", "PT-BR" (Portuguese) and "RU" (Russian).
		*/
		
		/*
		no need to unset, just keep it useless
		$target_lang = $this->request['target_lang'];
		if ( !in_array( $target_lang, array('DE', 'FR', 'IT', 'ES', 'NL', 'PL', 'PT-PT', 'PT-BR', 'RU' ) ) ) {
			unset( $this->request['formality'] );
		}
		*/

		return;
	}

	public function addText( $string ) {
		$this->request['text'][] = $string;
		return strlen( implode( '', $this->request['text'] ) );
	}

	protected function resetTexts() {
		$this->request['text'] = array();
	}

	public function setLangFrom( $source_lang = false ) {
		if ( !$source_lang ) {
			return true;
		}

		$source = DeeplConfiguration::validateLang( $source_lang, 'assource' );
		if ( $source ) {
			$this->request['source_lang'] = $source;
			return true;
		}
		else {
			return true;
		}
	}

	public function setLangTo( $target_lang ) {
		if ( !$target_lang ) {
			return false;
		}

		$target = DeeplConfiguration::validateLang( $target_lang, 'astarget' );
		if ( !$target ) {
			return false;
		}
		else {
			$this->request['target_lang'] = $target;
			return true;
		}
	}

	public function setTagHandling( $tag_handling = 'html' ) {
		if ( $tag_handling && in_array( $tag_handling, $this->syntaxes ) ) {
			$this->request['tag_handling'] = $tag_handling;
			return true;
		}
	}

	public function setSplitSentences( $split_sentences = true ) {
		if ( false === filter_var( $split_sentences, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->request['split_sentences'] = 0;
		}
		else {
			$this->request['split_sentences'] = 1;
		}
	}

	public function setFormality( $formality = 'default' ) {
		if ( in_array( $formality, array('more', 'less', 'prefer_more', 'prefer_less', 'default' ) ) ) {
			$this->request['formality'] = $formality;
		}
		else {
		}
	}

	public function setGlossary( $glossary_id ) {
		
		if( $glossary_id )
			$this->request['glossary_id'] = $glossary_id;
	}

	public function setPreserveFormatting( $preserve_formatting = false ) {
		if ( true === filter_var( $split_sentences, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->request['preserve_formatting'] = 1;
		}
		else {
			$this->request['preserve_formatting'] = 0;
		}
	}

	public function getRequestUniqueID() {
		$this->uniqid = md5( implode( '',$this->request['text'] ) );
		return $this->uniqid;
	}

	// RESPONSE
	public function getDetectedLanguage() {
		if ( !isset( $this->result ) || !array_key_exists( 'detected_source_language', $this->result ) ) {
			return false;
		}
		return $this->result['detected_source_language'];
	}

	public function getMessage() {
		if ( !isset( $this->result ) || !array_key_exists( 'message', $this->result ) ) {
			return false;
		}
		return $this->result['message'];
	}
	public function getTranslatedText() {
		if ( !isset( $this->result ) || !array_key_exists( 'text', $this->result ) ) {
			return false;
		}
		return $this->result['text'];
	}
}

