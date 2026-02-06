<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
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

		//if ( WPDEEPL_DEBUG ) wpdeepl_debug_display($post_types, " context = " . DeepLConfiguration::getMetaBoxContext() . " prio ="  . DeepLConfiguration::getMetaBoxPriority() );

		add_meta_box(
			'deepl_metabox',
			__( 'Translation with DeepL', 'wpdeepl' ),
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
			esc_html_e('Please save or publish the post first', 'wpdeepl' );
			return;

		}
		
		add_action('admin_action_' . $action, 'deepl_translate_post_link' );

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
			<input type="hidden" id="wpdeepl_action"  name="wpdeepl_action" value="' . esc_attr( $action ) .'" />
			<input type="hidden" id="wpdeepl_force_polylang" name="wpdeepl_force_polylang" value="1" />
			' . wp_nonce_field( $nonce_action, '_wpdeeplnonce', false, false ) .'
			' . wpdeepl_language_selector( 'source', 'wpdeepl_source_lang', false ) . '
			<br />' . esc_html__( 'Translating to', 'wpdeepl' ) . '<br />
			' . wpdeepl_language_selector( 'target', 'wpdeepl_target_lang', $target_lang ) . '
			<span id="wpdeepl_error" class="error" style="display: none;"></span>
			<input id="deepl_translate_do" type="submit" class="button button-primary button-large" value="' . esc_attr__( 'Translate' , 'wpdeepl' ) . '">
			<hr />';


		foreach ( $default_metabox_behaviours as $value => $label ) {
			$html.= '
			<span style="display: block;">
				<input type="radio"  name="wpdeepl_replace" value="'. $value .'"';

			if ( $value == $default_behaviour ) {
				$html .= ' checked="checked"';
			}
			if ( $value == 'append' && !DeeplConfiguration::usingMultilingualPlugins() ) {
				$html .= ' disabled="disabled"';
			}
			$html .= '>
				<label for="wpdeepl_replace">' . $label . '</label>
			</span>';
		}
	

		//</form>
		$html .= '
		';

		$html = apply_filters( 'deepl_metabox_html', $html);
		// Allow form elements for metabox functionality
		$allowed_tags = array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'input' => array(
					'type' => true,
					'id' => true,
					'name' => true,
					'value' => true,
					'checked' => true,
					'disabled' => true,
					'class' => true,
				),
				'select' => array(
					'id' => true,
					'name' => true,
					'class' => true,
				),
				'option' => array(
					'value' => true,
					'selected' => true,
				),
			)
		);
		echo wp_kses( $html, $allowed_tags );
	}
}