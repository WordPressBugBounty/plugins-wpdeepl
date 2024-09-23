jQuery(document).ready(function() {

	jQuery(document).ready(function() {
		if( jQuery('#pll_post_lang_choice').length ) {
			var post_language = jQuery('#pll_post_lang_choice').val();
			console.log(" on devrait désactiver " + post_language  + " dans la metabox ");
		}

	});
	jQuery('#deepl_translate_do').on('click', function() {

		var url = jQuery('#deepl_action').val() + '&deepl_action=deepl_translate_post_do';
		url += '&deepl_force_polylang=' + jQuery('#deepl_force_polylang').val();
		url += '&deepl_source_lang=' + jQuery('#deepl_source_lang').val();
		url += '&deepl_target_lang=' + jQuery('#deepl_target_lang').val();
		url += '&behaviour=' + jQuery('input[name="deepl_replace"]:checked').val().trim();
		url += '&_deeplnonce=' + jQuery('#_deeplnonce').val();

		console.log(url);
		window.location.replace(url);
		return false;

	});
});