<?php

abstract class DeepLApi extends DeepLData {
	protected $endPoint		= '';
	protected $http_mode = '';
	private $endPointURL	= false;
	protected $authKey 	= '';
	protected $log 	= false;
	protected $headers 	= array();

	public $languages;
	public $doing_cron;
	public $instance;
	public $result;
	public $why_no_cache;
	public $final_request;
	public $format;
	
	protected $cache_dir 	= false;

	protected $request 		= array();
	protected $uniqid 		= false;
	protected $cache_file 	= false;
	protected $response 	= false;
	public $cache_prefix 	= false;
	protected $cache_id 	= false;

	public $allow_cache 	= true;
	public $response_type 	= 'fresh';
	public $request_cache_file	= '';
	public $response_cache_file	= '';

	public $start_microtime = 0;
	public $end_microtime 	= 0;
	public $request_microtime = 0;

	const TIMEOUT = 15;

	public function __construct( $languages = array(), $log = false ) {
		$this->authKey = DeepLConfiguration::getAPIKey();
		//$this->request['auth_key'] = $auth_key;
		$this->log = $log;

		$this->languages = DeepLConfiguration::DefaultsAllLanguages();

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->doing_cron = true;
		}
		else {
			$this->doing_cron = false;
		}

		$this->instance = $this;

		$this->cache_dir = $this->getCacheDirectory();
	}

	public function wasItCached() {
		return $this->response_type === 'cache';
	}

	public function getTimeElapsed() {
		return $this->request_microtime;
	}

	abstract public function buildBody( $mode, $endPoint );

	public function getRequestUniqueID() {
		if ( $this->cache_id ) {
			$this->uniqid = $this->cache_id;
		}
		elseif ( !$this->uniqid ) {
			$this->uniqid = microtime();
		}
		return $this->uniqid;
	}

	public function isValidRequest() {
		return true;
	}

/*
	protected function maybeLimitRequestSize() {
	}
*/
	protected function saveRequest() {
		if ( isset( $this->request_cache_file ) ) {
			file_put_contents( $this->request_cache_file, json_encode( $this->final_request ) );
		}
	}

	protected function addHeaders( $headers ) {
		return $headers;
	}

	protected function doRequest( $mode = false ) {
		if( !$mode ) 
			$mode = $this->http_mode;


		if ( $mode == 'GET' ) {
			$response = $this->doGETRequest();
		}
		elseif( $mode == 'DELETE' ) {
			$response = $this->doGETRequest( 'DELETE' );
		}
		elseif( $mode == 'PUT' ) {
			$response = $this->doGETRequest( 'PUT' );
		}
		else {
			$response = $this->doPOSTRequest();
		}

		return $response;
	}

	static function getInstance() {
		return self::instance;
	}

	protected function setCacheID( $idstring ) {
		$this->cache_id = $idstring;
	}

	public function setCachePrefix( $prefix = '' ) {
		if ( !empty( $prefix ) ) {
			$this->cache_prefix = $prefix;
		}
		else {
			$this->cache_prefix = '';
		}
	}

	public function getCacheFile( $type ) {
		if ( $type == 'response' ) {
			return $this->response_cache_file;
		}
		elseif ( $type == 'request' ) {
			return $this->request_cache_file;
		}
	}

	public function setCacheNames() {
		$cache_name = '';
		if ( !empty( $this->cache_prefix ) ) {
			$cache_name .= $this->cache_prefix .'-';
		}
		$cache_name .= $this->cache_id;
		$this->request_cache_file	= trailingslashit( $this->cache_dir ) . $cache_name . '-request';
		$this->response_cache_file	= trailingslashit( $this->cache_dir ) . $cache_name . '-response';
	}

	public function request() {

		$log_bits = array('class' => get_class($this));

		if ( $this->allow_cache )
			$this->uniqid = $this->getRequestUniqueID();

		$log_bits['uniqid'] = $this->uniqid;

		$this->setCacheNames();
		$this->result = false;

		if ( $this->isCacheValid( $this->response_cache_file ) ) {
			$this->response_type = 'cache';
			$this->why_no_cache = false;
			$this->result = file_get_contents( $this->response_cache_file );
			$log_bits['type'] = 'cached';

		}
		else {
			$log_bits['type'] = 'fresh';
			$log_bits['mode'] = $this->http_mode;
			$this->start_microtime = microtime( true );
			//echo "\nIS FRESH";
			$this->response_type = 'fresh';


			if ( $this->http_mode == 'GET' ) {
				$response = $this->doRequest( 'GET' );
			}
			else {
				$response = $this->doRequest( 'POST' );
			}


			$this->end_microtime = microtime( true );
			$this->request_microtime = $this->end_microtime - $this->start_microtime;

			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			}

			if ( $response['response']['code'] > 299 ) {
				$log_bits['response_error'] = $response['response']['code'];
				$log_bits['response'] = $response['response'];
				if ( $response['response']['code'] == 429 ) {
					$log_bits['error_cause'] = 'Excessive request size';
				}
				if ( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");

				$body = json_decode( $response['body'], true );
				$message = isset( $body['message'] ) ? $body['message'] : false;
				return new WP_Error( $message );
			}

			if ( array_key_exists( 'body', $response ) && strlen( $response['body'] ) )
				$this->result = $response['body'];
			else
				$this->result = false;

			if ( $this->result ) {
				//echo " putting " . strlen( serialize( $this->result ) ) ." in "
				try {
					file_put_contents( $this->response_cache_file, $this->result );
				} catch (Exception $e) {
	
				}
			}

			$log_bits['request_microtime'] = $this->request_microtime;
		}

		if ( DeepLConfiguration::getLogLevel() ) {
			if ( DeepLConfiguration::getLogLevel() > 1 ) {
				foreach( $this->request as $key => $value ) {
					if( is_string( $value ) && strlen( $value ) > 500 )
						$log_bits['args'][$key] = "LEN=" . strlen( $value) .", string = " . substr( $value, 0, 100 );
					else
						$log_bits['args'][$key] = $value;

				}
			}
			else {
				foreach ( array ('source_lang', 'target_lang') as $key ) {
					if ( isset( $this->request[$key] ) ) {
						$log_bits[$key] = $this->request[$key];
					}
				}
			}
			if ( isset( $this->request['text'] ) ) {
				$text_size = 0;
				foreach ( $this->request['text'] as $i => $string ) {
					$text_size += strlen($string);
				}
			$log_bits['text_size'] = $text_size;
			}
		}

		if ($this->response_type == 'fresh') {
			// test if JSON
			json_decode($this->result);
			if (json_last_error() == JSON_ERROR_NONE) {
				$log_bits['response'] = json_decode($this->result, 1 );
				if ( DeepLConfiguration::getLogLevel() ) {
					if (isset( $log_bits['response']['translations'] ) ) {
						$translated_text_size = 0;
						foreach ( $log_bits['response']['translations'] as $i => $translation ) {
							$translated_text_size += strlen( $translation['text'] );
						}
						$log_bits['translated_text_size'] = $translated_text_size;
					}
				}
			}
			else {
				$log_bits['response'] = $this->result;
			}

		}
		else {
			$log_bits['response'] = '--hidden response because cached request--';

		}
		$log_bits['response_length'] = strlen($this->result);

		if ( $this->result ) {
			$this->result = json_decode( $this->result );

			$this->result = ( array ) $this->result;
			$this->result['response_type'] = $this->response_type;
			if ( $this->why_no_cache ) {
				$log_bits['why_no_cache'] = $this->why_no_cache;
				$this->result['why_no_cache'] = $this->why_no_cache;
			}
			if ( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");
			return $this->result;
		}
		else {
			if ( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");
			return $this->response['response']['message'];
		}
	}

	public function getRequestTime( $result_in_milliseconds = false ) {
		if ( !$this->request_microtime ) {
			return false;
		}

		if ( $result_in_milliseconds ) {
			return floatval( $this->request_microtime );
		}
		else {
			return floatval( $this->request_microtime/1000 );
		}
	}

	public function getEndPointURL( $add_auth_key = false ) {


		$APIServer = DeeplConfiguration::getAPIServer();
		if ( !$this->endPointURL ) {
			if ( $this->endPoint === '' ) {
				$this->endPointURL = $APIServer;
			}
			else {
				$this->endPointURL = trailingslashit( $APIServer ) . $this->endPoint;
			}
		}

		if ( $add_auth_key ) {
			$this->endPointURL .= '?auth_key=' . $this->authKey;
		}

		return $this->endPointURL;
	}
	/*
	* send a POST request
	*
	* @since 0.1
	*/
	public function doPOSTRequest( $args	= array() ) {


		$args = array(
			'method'	=> 'POST',
			'timeout'	=> self::TIMEOUT,
		);

		$args['headers'] = array(
			'Authorization'	=> 'DeepL-Auth-Key ' . $this->authKey 
		);

		$args['headers'] = $this->addHeaders( $args['headers'] );
		$args['body'] = $this->buildBody( 'POST', $this->endPoint );

		//plouf( $args ,__METHOD__ . "args");

		/*
		$args['auth_key'] = $this->authKey;

		$request = array();
		if ( $this->request ) {
			foreach ( $this->request as $key => $value ) {
				$request[$key] = $value;
			}
		}
		$args['query'] = http_build_query( $request );
		$args['timeout'] = 
*/
		//$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
//			$this->headers['Content-Length'] = strlen( $this->request['text'] );



		$add_auth_key = false;
		$remoteURL = $this->getEndPointURL( $add_auth_key );

		$bits = array_merge( array( $remoteURL, 'POST' ), $args );
		// unsafe
		//wpdeepl_log( $bits, 'apiRequests');
//		plouf( $args, "args" ); 
/*
		$exec = "curl -X POST '$remoteURL' \\\n";
		foreach( $args['headers'] as $key => $value ) {
			$exec .= "-H '$key: $value' \\\n";
		}
		foreach( $args['body'] as $key => $value ) {
			$exec .= "-d '$value' \\\n";
		}

		plouf( $exec );
		$response = exec ( $exec );
		plouf( $response );
		die('okaz4a6e4a68e4a86e');
		*/

		//plouf( $args, $remoteURL );		die('68z4e6z48a68ee8aok');

		//$args['body'] = str_replace('target_lang=NO', 'target_lang=NB', $args['body'] );
		$response = wp_remote_post( $remoteURL, $args );
		//plouf( $args, $remoteURL, " post request");				plouf( $response );		die('okesoezporjkezor');

//		plouf( $args, " args pour $remoteURL" ); 		plouf( $response, "zeirjzpeijrpizerjpirj" );		die('okaze6aze44e');
		//plouf( $args, "args");

//		plouf( $args, "POST request to $remoteURL ");die('okaz5ea5zea4e');

		if ( is_wp_error( $response ) ) {
			$this->response = $response->get_error_message();
				plouf( $args, $this->getEndPointURL() );
				die( __METHOD__ );
		}
		else {
			$this->response = $response;
		}
		return $response;
	}

	/*
	* send a GET request
	*
	* @since 0.1
	*/
	public function doGETRequest( $alt_mode = false ) {

		$method = 'GET';
		if( in_array( $alt_mode, array('PUT', 'DELETE' ) ) ) 
			$method = $alt_mode;
		$args = array(
			'method'	=> $method,
			'timeout'	=> self::TIMEOUT,
		);

		$args['headers'] = array(
			'Authorization'	=> 'DeepL-Auth-Key ' . $this->authKey 
		);
		$args['headers'] = $this->addHeaders( $args['headers'] );

		$remoteURL = $this->getEndPointURL();
		//if( $args['query'] ) 			$remoteURL .= '?' . $args['query'];
		//plouf( $args, "REQUESTING URL = '$url'" );		 die( '6ze846az6e646eok' );

		$curl_string = 'curl -X ' . $args['method'] . " '$remoteURL'\n";
		foreach( $args['headers'] as $key => $value ) {
			$curl_string .= "$key: $value\n";
		}
		//echo "\n CURL = " . str_replace("\n", '<br />', $curl_string );
		$bits = array_merge( array( $remoteURL, 'GET' ), $args );
		// unsafe
		//wpdeepl_log( $bits, 'apiRequests');

//		plouf( $args, $remoteURL );		die('oazea4ze9684e84azek');
		
		$response = wp_remote_get( $remoteURL, $args );
		if ( is_wp_error( $response ) ) {
			$this->response = $response->get_error_message();
		} else {
			$this->response = $response;
		}
		return $response;
	}
}