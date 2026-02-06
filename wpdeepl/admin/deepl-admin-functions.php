<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function wpdeepl_test_admin() {
	return;
}





function wpdeepl_language_selector(
	$type = 'target',
	$css_id = 'deepl_language_selector',
	$selected = false, 
	$not_selected = false,
	$forbid_auto = false
) {
	$languages = DeepLConfiguration::DefaultsAllLanguages();

	$wp_locale = get_locale();

	$default_target_language = DeepLConfiguration::getDefaultTargetLanguage();

	if ( 'target' === $type && false === $selected ) {
		$selected = $default_target_language;
	}

	$html = "";

	// 1. Sécurisation des attributs ID et NAME
	$html .= "\n" . '<select id="' . esc_attr( $css_id ) . '" name="' . esc_attr( $css_id ) . '" class="deepl_translate_form">';

	if ( 'source' === $type ) {
		if( ! $forbid_auto ) {
			// 2. Sécurisation des textes traduits
			if( ! defined('WPDEEPLPRO_NAME') || ! DeepLConfiguration::usingGlossaries() ) {
				$html .= '
				<option value="auto">' . esc_html__( 'Automatic', 'wpdeepl' ) . '</option>';
			}
			else {
				$html .= '
				<option value="auto">' . esc_html__( 'Automatic (no glossary)', 'wpdeepl' ) . '</option>';
			}
		}
	}

	$languages_to_display = DeepLConfiguration::getDisplayedLanguages();

	foreach ( $languages as $ln_id => $language ) {

		if ( $languages_to_display && ! in_array( $ln_id, $languages_to_display, true ) ) {
			continue;
		}
		
		if (
			$default_target_language
			&& $ln_id == $default_target_language
			&& 'source' === $type
		) {
			//continue;
		}

		// 3. Sécurisation de la valeur de l'option
		$html .= '
		<option value="' . esc_attr( $ln_id ) .'"';

		if ( $ln_id == $selected && $ln_id != $not_selected ) {
			$html .= ' selected="selected"';
		}
		
		$label = ( $wp_locale && isset( $language['labels'][$wp_locale] )) ? $language['labels'][$wp_locale] : $language['labels']['fr_FR'];
		
		// 4. Sécurisation impérative du label affiché (FAIL fréquent du PCP ici)
		$html .= '>' . esc_html( $label ) . '</option>';
	}
	
	if ( 'target' === $type ) {
		// Correction typo + escaping
		$html .= '
		<option value="notranslation">' . esc_html__( "Don't translate", 'wpdeepl' ) . '</option>';
	}

	$html .="\n</select>";

	return $html;
}

function wpdeepl_show_clear_logs_button() {
	?>
	<p class="submit">
		<button name="clear_logs" class="button-primary" type="submit" value="clear_logs"><?php esc_html_e('Clear logs', 'wpdeepl'); ?></button>
	</p>
	<?php 
}



function wpdeepl_clear_logs() {
	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
	if ($log_files) foreach ( $log_files as $log_file) {
		wp_delete_file($log_file);
	}
	?>
	<div class="notice notice-success"><p><?php esc_html_e('Log files deleted', 'wpdeepl'); ?></p></div>
	<?php 
}
function wpdeepl_log( $bits, $type ) {
	$log_lines = array_merge(array('date'	=> gmdate('d/m/Y H:i:s')), $bits);
	$log_line = serialize($log_lines) . "\n";
	$type = html_entity_decode( $type );
	$log_file = trailingslashit( WPDEEPL_FILES ) . gmdate( 'Y-m' ) . '-' . $type . '.log';
	file_put_contents( $log_file, $log_line, FILE_APPEND );
}


function wpdeepl_prune_logs() {
	if ( !current_user_can( 'manage_options' ) ) {
		return false;
	}

	$nonce_value = filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	if ( ! $nonce_value || ! wp_verify_nonce( $nonce_value, 'prune_logs' ) ) {
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
 			wp_delete_file( $log_file );
 		}
		}
	}
}


function wpdeepl_display_logs() {


	?>
	<h3 class="wc-settings-sub-title" id="logs"><?php esc_html_e('Logs','wpdeepl'); ?></h3>
	<?php 

	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
	if ($log_files) {
		foreach ($log_files as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if (preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				?>
				<h3><?php
				echo esc_html(
					sprintf(
						/* translators: 1. file name 2. month */
						__("File '%1\$s' for %2\$s", 'wpdeepl' ),
						$match[3],
						$date
					)
				);
				?>
				</h3><?php

				$lines = explode("\n", $contents);
				foreach ($lines as $line) {
					wpdeepl_debug_display(unserialize($line));
				}

			}

		}
	}
	else {
		esc_html_e( 'No log files', 'wpdeepl' );
	}
}

