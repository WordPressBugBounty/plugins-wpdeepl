<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * convert  Unicode entities
 * */
function deepl_unicode_decode( $string ) {
	$string = preg_replace_callback( '#\\\\u([0-9a-fA-F]{4})#', function ( $match ) {
			return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
		}, $string );
	$string = str_replace( '\u0002', ' ', $string );
	$string = preg_replace_callback('/&#x([0-9a-fA-F]{3,4});/', function ($match) {
			return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
		}, $string);
	//$string = preg_replace_callback('/&#([0-9a-fA-F]{3,4});/', function ($match) {		    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');		}, $string);
	return $string;
}

