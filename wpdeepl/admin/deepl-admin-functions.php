<?php

function wpdeepl_test_admin() {


	return;
	/*
	$glossary_id = 'da005df2-fb26-4165-8310-b07c9cc1b492';
	$list = deepl_listGlossaryEntries( $glossary_id, 'tsv' );
	plouf( $list );
	die('okazeaze6a8z4e6e4az4');
*/
	//deepl_deleteGlosssary(  );


}





function deepl_language_selector(
	$type = 'target',
	$css_id = 'deepl_language_selector',
	$selected = false, 
	$not_selected = false,
	$forbid_auto = false
	) {
	$languages = DeepLConfiguration::DefaultsAllLanguages();

	$wp_locale = get_locale();

	$default_target_language = DeepLConfiguration::getDefaultTargetLanguage();

	if ( $type == 'target' && $selected == false ) {
		$selected = $default_target_language;
		//plouf( $languages );
	}

	$html = "";

	$html .= "\n" . '<select id="' . $css_id . '" name="' . $css_id . '" class="deepl_translate_form">';

	if ( $type == 'source' ) {
		if( !$forbid_auto ) {
			if( !defined('WPDEEPLPRO_NAME') || !DeepLConfiguration::usingGlossaries() ) {
			$html .= '
				<option value="auto">' . __( 'Automatic', 'wpdeepl' ) . '</option>';
			}
				
			else {
				$html .= '
				<option value="auto">' . __( 'Automatic (no glossary)', 'wpdeepl' ) . '</option>';

			}
		}
	}


	$languages_to_display = DeepLConfiguration::getDisplayedLanguages();

	foreach ( $languages as $ln_id => $language ) {

		if ( $languages_to_display && !in_array( $ln_id, $languages_to_display ) ) {
			continue;
		}
		if (
			$default_target_language
			&& $ln_id == $default_target_language
			&& $type == 'source'
		) {
			//continue;
		}

		$html .= '
		<option value="' . $ln_id .'"';

		if ( $ln_id == $selected && $ln_id != $not_selected ) {
			$html .= ' selected="selected"';
		}
		$label = ( $wp_locale && isset( $language['labels'][$wp_locale] )) ? $language['labels'][$wp_locale] : $language['labels']['fr_FR'];
		$html .= '>' . $label. '</option>';
	}
	if ( $type == 'target' ) $html .= '
	<option value="notranslation">' . __( 'Dont\'t translate', 'wpdeepl' ) . '</option>';

	$html .="\n</select>";

	return $html;
}

function wpdeepl_show_clear_logs_button() {
	echo '
	<p class="submit">
		<button name="clear_logs" class="button-primary" type="submit" value="clear_logs">' . __('Clear logs', 'wpdeepl') .'</button>
	</p>';
}



function wpdeepl_clear_logs() {
	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
	if ($log_files) foreach ( $log_files as $log_file) {
		unlink($log_file);
	}
	echo '<div class="notice notice-success"><p>' . __('Log files deleted', 'wpdeepl') . '</p></div>';
}
function wpdeepl_log( $bits, $type ) {
	$log_lines = array_merge(array('date'	=> date('d/m/Y H:i:s')), $bits);
	$log_line = serialize($log_lines) . "\n";
	$type = html_entity_decode( $type );
	$log_file = trailingslashit( WPDEEPL_FILES ) . date( 'Y-m' ) . '-' . $type . '.log';
	file_put_contents( $log_file, $log_line, FILE_APPEND );
}


function wpdeepl_prune_logs() {
	if ( !current_user_can( 'manage_options' ) ) {
		return false;
	}

	if( !wp_verify_nonce( $_GET[ 'nonce' ], 'prune_logs' ) ) {
		return false;
	}
	
	$logs = glob( trailingslashit( WPDEEPL_FILES ) . '*.log');
	if ($logs) foreach ($logs as $log_file) {
		$file_name = basename( $log_file );
		if (preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
			//$date = $match[2] . '/' . $match[1];

			$log_time = mktime(0, 0, 0, $match[2], 1, $match[1] );

			$first_day_of_the_month = new DateTime('first day of this month');
			$first_day_of_the_month->modify('- 1 day');
	 		$first_day_time = $first_day_of_the_month->getTimestamp();

 		if ( $log_time < $first_day_time ) {
 			//echo " <br />SUPPRESSION $log_file : " . date('Y-m-d H:i:s', $log_time) . " < " . date('Y-m-d H:i:s', $first_day_time );
 			unlink( $log_file );
 		}
		}
	}
}


function wpdeepl_display_logs() {


	echo '<h3 class="wc-settings-sub-title" id="logs">' . __('Logs','wpdeepl') . '</h3>';

	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
	if ($log_files) {
		foreach ($log_files as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if (preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				echo '<h3>';
				printf(
					__("File '%s' for %s", 'wpdeepl' ),
					$match[3],
					$date
				);
				echo '</h3>';

				$lines = explode("\n", $contents);
				foreach ($lines as $line) {
					plouf(unserialize($line));
				}

			}

		}
	}
	else {
		_e( 'No log files', 'wpdeepl' );
	}
}

