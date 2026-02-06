jQuery(document).ready(function() {

	jQuery(document).ready(function() {
		if( jQuery('#pll_post_lang_choice').length ) {
			var post_language = jQuery('#pll_post_lang_choice').val();
			console.log(" on devrait désactiver " + post_language  + " dans la metabox ");
		}

	});
	jQuery('#deepl_translate_do').on('click', function() {

		var url = jQuery('#wpdeepl_action').val() + '&wpdeepl_action=deepl_translate_post_do';
		url += '&wpdeepl_force_polylang=' + jQuery('#wpdeepl_force_polylang').val();
		url += '&wpdeepl_source_lang=' + jQuery('#wpdeepl_source_lang').val();
		url += '&wpdeepl_target_lang=' + jQuery('#wpdeepl_target_lang').val();
		url += '&behaviour=' + jQuery('input[name="wpdeepl_replace"]:checked').val().trim();
		url += '&_wpdeeplnonce=' + jQuery('#_wpdeeplnonce').val();

		console.log(url);
		window.location.replace(url);
		return false;

	});
});