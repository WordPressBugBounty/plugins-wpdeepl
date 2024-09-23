<?php

class DeepL_Metabox {
	protected $metabox_config = array();

	function __construct() {
		// adding the box
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );

		// adding the javascript footer
		//add_action( 'admin_footer', array( &$this, 'deepl_admin_footer_javascript' ) );

	}

	public function add_meta_box() {
		$post_types = DeepLConfiguration::getMetaBoxPostTypes();
		//if ( WPDEEPL_DEBUG ) plouf($post_types, " context = " . DeepLConfiguration::getMetaBoxContext() . " prio ="  . DeepLConfiguration::getMetaBoxPriority() );

		add_meta_box(
			'deepl_metabox',
			__( 'DeepL translation', 'wpdeepl' ),
			array( &$this, 'output' ),
			$post_types,
			DeepLConfiguration::getMetaBoxContext(),
			DeepLConfiguration::getMetaBoxPriority()
		);
	}

	public function output() {

		global $post;


		$nonce_action = DeepLConfiguration::getNonceAction();
		$post_type_object = get_post_type_object( $post->post_type );
		$action = admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) );

		$default_behaviour = DeepLConfiguration::getMetaBoxDefaultBehaviour();
		$default_metabox_behaviours = DeepLConfiguration::DefaultsMetaboxBehaviours();

		//var_dump(DeeplConfiguration::usingMultilingualPlugins());
		if ( !DeeplConfiguration::usingMultilingualPlugins() ) {
			$default_behaviour = 'replace';
		}
		if ( !$default_behaviour ) {
			$default_behaviour = 'replace';
		}

		global $pagenow;
		
		if( $pagenow == 'post-new.php' ) {
			_e('Please save or publish the post first', 'wpdeepl' );
			return;

		}
		
		add_action('admin_action_' . $action, 'deepl_translate_post_link' );

//		echo '1';		return false;


		//<form id="deepl_admin_translation" name="deepl_admin_translation" method="POST" action="post.php">
		/*$target_lang = get_option( 'deepl_default_locale');
		$current_language = false;
		if( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
			if( $current_language ) {
				$source_lang = DeepLConfiguration::getLanguageFromIsoCode2( $current_language );
			}
			else {
				$terms = wp_get_post_terms( $post->ID,'language' );
				if( $terms ) {
					$language = $terms[0];
					$source_lang = DeepLConfiguration::getLanguageFromIsoCode2( $language->slug  );
				}
			}
		}

		//$target_lang = DeepLConfiguration::getDefaultTargetLanguage();
		$target_lang = false;

		$html = '
			<input type="hidden" id="deepl_action"  name="deepl_action" value="' . $action .'" />
			<input type="hidden" id="deepl_force_polylang" name="deepl_force_polylang" value="1" />
			' . wp_nonce_field( $nonce_action, '_deeplnonce', false, false ) .'
			' . deepl_language_selector( 'source', 'deepl_source_lang', $source_lang ) . '
			<br />' . __( 'Translating to', 'wpdeepl' ) . '<br />
			' . deepl_language_selector( 'target', 'deepl_target_lang', $target_lang, $source_lang ) . '
			<span id="deepl_error" class="error" style="display: none;"></span>
			<input id="deepl_translate_do" type="submit" class="button button-primary button-large" value="' . __( 'Translate' , 'wpdeepl' ) . '">
			<hr />';
		//$html .= "\n current $current_language / source $source_lang / target $target_lang ";
		*/

		$target_lang = get_option( 'deepl_default_locale');
		if( function_exists( 'pll_current_language' ) ) {
			$current_language = pll_current_language();
			if( $current_language ) {
				$target_lang = DeepLConfiguration::getLanguageFromIsoCode2( $current_language );
			}
			else {
				$terms = wp_get_post_terms( $post->ID,'language' );
				if( $terms ) {
					$language = $terms[0];
					$target_lang = DeepLConfiguration::getLanguageFromIsoCode2( $language->slug  );
				}
			}
		}


		$html = '
			<input type="hidden" id="deepl_action"  name="deepl_action" value="' . $action .'" />
			<input type="hidden" id="deepl_force_polylang" name="deepl_force_polylang" value="1" />
			' . wp_nonce_field( $nonce_action, '_deeplnonce', false, false ) .'
			' . deepl_language_selector( 'source', 'deepl_source_lang', false ) . '
			<br />' . __( 'Translating to', 'wpdeepl' ) . '<br />
			' . deepl_language_selector( 'target', 'deepl_target_lang', $target_lang ) . '
			<span id="deepl_error" class="error" style="display: none;"></span>
			<input id="deepl_translate_do" type="submit" class="button button-primary button-large" value="' . __( 'Translate' , 'wpdeepl' ) . '">
			<hr />';


		foreach ( $default_metabox_behaviours as $value => $label ) {
			$html.= '
			<span style="display: block;">
				<input type="radio"  name="deepl_replace" value="'. $value .'"';

			if ( $value == $default_behaviour ) {
				$html .= ' checked="checked"';
			}
			if ( $value == 'append' && !DeeplConfiguration::usingMultilingualPlugins() ) {
				$html .= ' disabled="disabled"';
			}
			$html .= '>
				<label for="deepl_replace">' . $label . '</label>
			</span>';
		}
	

		//</form>
		$html .= '
		';

		$html = apply_filters( 'deepl_metabox_html', $html);
		echo ( $html);
	}
}