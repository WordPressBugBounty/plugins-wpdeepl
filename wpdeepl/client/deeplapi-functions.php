<?php

function deepl_translate( $source_lang = false, $target_lang = false, $strings = array(), $cache_prefix = '', $allow_cache = true  ) {
		$DeepLApiTranslate = new DeepLApiTranslate();
		$DeepLApiTranslate->setCachePrefix( $cache_prefix );
		$DeepLApiTranslate->allowCache( $allow_cache );

		if ( $source_lang ) {
			$DeepLApiTranslate->setLangFrom( $source_lang );
		}

		//var_dump($target_lang);		die('okzerzererrzer');
		if ( !$DeepLApiTranslate->setLangTo( $target_lang ) ) {
			return new WP_Error( sprintf( __( "Target language '%s' not valid", 'wpdeepl' ), $target_lang ) );
		}

		$DeepLApiTranslate->setTagHandling( 'html' );

		$DeepLApiTranslate->setFormality( DeepLConfiguration::getFormalityLevel( $target_lang) );

		$DeepLApiTranslate = apply_filters('deepl_translate_DeepLApiTranslate_before', $DeepLApiTranslate );

		//echo "\n pour lang $target_lang , formality = " . DeepLConfiguration::getFormalityLevel( $target_lang) ;
//		plouf($DeepLApiTranslate);		die('oazeaz+e48az4ea698k');
		
		$translations = $DeepLApiTranslate->getTranslations( $strings );

		if ( is_wp_error( $translations ) ) {
			return array(
				'success'	=> false,
				'errors'	=> $translations
			);
		}
		$request = array(
			'cached'				=> $DeepLApiTranslate->wasItCached(),
			'time'					=> $DeepLApiTranslate->getTimeElapsed(),
			'cache_file_request'	=> $DeepLApiTranslate->getCacheFile( 'request' ),
			'cache_file_response'	=> $DeepLApiTranslate->getCacheFile( 'response' ),
		);
		$success = true;
		$return = compact( 'success', 'request', 'translations' );
		return apply_filters( 'deepl_translate', $return, $source_lang, $target_lang, $strings, $cache_prefix );
}


function deepl_show_usage() {
	?>
		<h3><?php _e( 'Usage', 'wpdeepl' ); ?></h3>
		<?php
		$DeepLApiUsage = new DeepLApiUsage();
		$usage = $DeepLApiUsage->request();

		//plouf($usage);		plouf($DeepLApiUsage);

		if ( $usage && is_array( $usage ) && array_key_exists( 'character_count', $usage ) && array_key_exists( 'character_limit', $usage )) :
			$ratio = round( 100 * ( $usage['character_count'] / $usage['character_limit'] ), 3 );
			$left_chars = $usage['character_limit'] - $usage['character_count'];

		?>
			<div class="progress-bar blue">
				<span style="width: <?php echo round( (100 - $ratio ), 0 ); ?>%"><b><?php printf( __( '%s characters remaining', 'wpdeepl' ), number_format( $left_chars )); ?></b></span>
				<div class="progress-text"><?php
				printf( __( '%s / %s characters translated', 'wpdeepl' ), number_format_i18n( $usage['character_count'] ), number_format_i18n( $usage['character_limit'] ) );
				 echo " - " . ( $ratio ); ?> %</div>
				 <small class="request_time"><?php printf( __( 'Request done in: %f milliseconds', 'wpdeepl' ), $DeepLApiUsage->getRequestTime( true )) ?></small>
			</div>
		<?php
		else :
			_e('No response from the server', 'wpdeepl');
			echo '<br />';
			printf(__('Did you select the right server ? If yes, check your plan on <a href="%s">DeepL Pro website</a>. "DeepL API" should be included in it.', 'wpdeepl' ),
				'https://www.deepl.com/pro-account/plan'
			);

		endif;
}