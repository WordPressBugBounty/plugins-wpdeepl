<?php

class DeepLApiGlossary extends DeepLApi {
	//protected $endPoint 	= '';
 	//public $allow_cache 	= false;
 	//protected $http_mode 	= '';

 	public function getSupportedLanguages() {
 		$this->endPoint = 'glossary-language-pairs';
 		$this->http_mode = 'GET';
 		$this->doRequest();


		if ( !isset( $this->response ) || !isset( $this->response['body'] ) ) {
			return false;
		}
 		$json = $this->response['body'];
 		$array = json_decode( $json, true );
 		if( !isset( $array['supported_languages'] ) ) 
 			return false;

 		return $array['supported_languages'];
	}

	
	public function createGlossary( $name, $data, $source_lang, $target_lang, $format = 'tsv' ) {
		$this->endPoint = 'glossaries';
		$this->http_mode = 'POST';

		$body = array(
			'name'	=> $name,
			'source_lang' => $source_lang,
			'target_lang'	=> $target_lang,
			'entries'	=> $data,
			'entries_format' => $format
		);
		$this->final_request['body'] = json_encode( $body );

		plouf( $this->final_request );
//		die('okzemrpzejrpizr');



		$this->doRequest( 'POST' );
		if ( !isset( $this->response ) || !isset( $this->response['body'] ) ) {
			return false;
		}
 		$json = $this->response['body'];
 		$array = json_decode( $json, true );
 		if( !isset( $array['glossary_id'] ) ) 
 			return false;

 		return $array;
	}

	public function listGlossaries() {
		$this->endPoint = 'glossaries';
		$this->http_mode = 'GET';
		$this->doRequest();


		if ( !isset( $this->response ) || !isset( $this->response['body'] ) ) {
			return false;
		}
 		$json = $this->response['body'];
 		$array = json_decode( $json, true );

 		if( !isset( $array['glossaries'] ) ) 
 			return false;

 		return $array['glossaries'];
	}

	public function deleteGlossary( $glossary_id ) {
		$this->endPoint = 'glossaries/' . htmlspecialchars_decode( $glossary_id );
		$this->http_mode = 'DELETE';
		$this->doRequest();
		

		return $this->response;
	}

	public function listGlossaryEntries( $glossary_id, $format = 'tsv' ) {
		$this->endPoint = 'glossaries/' . htmlspecialchars_decode( $glossary_id ) . '/entries';
		$this->http_mode = 'GET';
		$this->format = $format;

		$this->doRequest();

		if( !isset( $this->response['body'] ) ) {
			return false;
		}
		$tsv = $this->response['body'];
		return $tsv;


	}

	function addHeaders( $headers ) {

		echo "end " . $this->endPoint;
		if( $this->endPoint == 'glossaries' && $this->http_mode == 'POST' ) {
			$headers['Content-Type'] = 'application/json';
		}
		elseif( strpos( $this->endPoint, 'entries' ) !== false ) {
			if( $this->format == 'csv' )
				$headers['Accept'] = 'text/csv';
			else 	
				$headers['Accept'] = 'text/tab-separated-values';
		}

		return $headers;
	}

	function buildBody( $mode = 'POST', $endPoint = 'glossaries' ) {
		if( $this->http_mode == 'POST' )
			return $this->final_request['body'];
		else
			return;
		
	}


}