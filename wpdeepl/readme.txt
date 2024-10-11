=== DeepL API translation plugin ===
Contributors: Malaiac
Tags: translation, post
Requires at least: 5.1
Tested up to: 6.6.1
Stable tag: 2.4.3.14
Requires PHP: 7.2
License: GNU AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.txt

Get DeepL translation magic right into your WordPress Admin

== Description ==

This plugin allows you to use DeepL translation API to translate posts ( and pages ) right from your WordPress Admin.
Adds a translate button to the edit post page.
Shows current and remaining API usage.

This plugin requires an active DeepL API subscription with access to the API.
Please note that DeepL User or Teams plans do NOT include API access.
You have to get a ["DeepL Developer" plan](https://www.deepl.com/fr/pro/change-plan#developer) (both 'DeepL API Free' and 'DeepL API Pro' work with this plugin)

Want to translate Products, meta fields, or translate in batches ? 
Get the [DeepL Pro API plugin](https://solutions.fluenx.com/en/produit/batch-deepl-translation-for-wordpress-posts/)

Please note : the free and the premium version works the same relative to content editors ( Elementor, Blocks, etc.). You're free to test this plugin on your website to see if your specific configuration works well with the plugin. We cannot guarantee that all content editors work perfectly with the DeepL API so please test this plugin before buying the premium (you'll need the premium only if you need extended features like meta translations and batch translations)

== Installation ==

1. Upload plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your API key in the Settings/DeepL settings page


== Changelog ==
= 2.4.3.14 - 11/10/2024 =
* late load of settings, better for ACF *
= 2.4.3.10 - 04/10/2024 =
* fixed bulk translation issue *
= 2.4.3.9 - 29/05/2024 08:56:31 =
* added Arabic language *
= 2.4.3.7 - 27/04/2024 21:57:23 =
* improved translation log *
= 2.4.3.6 - 24/04/2024 12:44:12 =
* added translations *
= 2.4.3.2 - 12/02/2024 16:42:12 =
*small fixes for Pro meta functions*
= 2.4.3.1 - 09/02/2024 10:19:41 =
*revert metabox behaviour*
= 2.4.3 - 08/02/2024 11:42:05 =
*fix glossary*
= 2.4.2 - 07/01/2024 16:02:30 =
*added support for Glossaries (premium version)*
= 2.4.0 - 27/11/2023 =
*fixing languages*
= 2.3.8 - 08/11/2023 =
*check for existing translations in poylang*
= 2.3.7.3 - 06/11/2023 =
*allow for optional front end usage*
= 2.3.7.1 - 31/10/2023 =
*fixed missing period at the end of paragraphs*
= 2.3.6.6 - 17/10/2023 =
*vbump for translations*
= 2.3.6.4 - 07/09/2023 =
*bugfix thanks to @julienrabatel*
= 2.3.6.3 - 04/09/2023 =
*added wp_slash to wp_update_post for unicode*
= 2.3.6.2 - 06/08/2023 =
*small php8 fix*
= 2.3.6.1 - 24/07/2023 17:43:01 =
*bugfix when quota exceeded or translation error*
= 2.3.5 - 20/07/2023 13:37:02 =
*bugfix substr/str_replace*
= 2.3.4 - 15/07/2023 03:53:12 =
*fix splitted requests (long post_content) *
= 2.3.3 - 13/07/2023 11:48:54 =
*strange behaviour with _formal locales from Polylang*
= 2.3.2 - 06/07/2023 18:57:41 =
*fix da_DA to da_DK*
= 2.3.0 =
*change of DeeplAPI. Change of requests*
= 2.2.0 =
*long articles split requests ! improved metabox ln selection. notice fixes*
= 2.1.10 - 25/04/2023 17:44:14 =
*late loading of settings for CPT *
= 2.1.9 - 24/03/2023 08:53:23 =
*fix KO instead of KR !*
= 2.1.8 - 13/03/2023 11:08:40 =
*oops on post_excerpt*
= 2.1.7 - 09/03/2023 09:12:39 =
*added Korean and Norwegian*
= 2.1.6 - 03/03/2023 15:54:40 =
*deprecated filter_sanitize_string*
= 2.1.5 - 02/03/2023 15:05:36 =
*added nonce to admin*
= 2.1.4 - 16/02/2023 17:34:12 =
*fix bug*
= 2.1.3 - 10/02/2023 10:51:30 =
*added translations*
= 2.1.2 =
*fixed formality for good. update readme for updated extension*
= 2.1 - 28/01/2023 11:17:57 =
*protection shortcodes, code, pre*
= 2.0.7 - 21/01/2023 17:35:02 =
*correction formalité*
= 2.0 =
*new version. php8 compat. security fixes. compatibilite Pro. bugfix niveau formalité. testé Chrome & Edge. niveau de formalité par langue*
= 1.8 - 22/04/2022 10:01:17 =
*improved admin*
= 1.7.4 - 06/05/2021 09:53:39 =
*temp fix for botched DeepL API returns ( unEscapeHTMLTags )*
= 1.7.2 - 24/04/2021 10:27:55 =
*PHP8 error *: *
= 1.7 - 22/04/2021 11:09:34 =
*pick server (Pro API or Free API)*
= 1.6 - 14/04/2021 11:06:56 =
*update languages ; all 27 of them*
temp fix for bracket bug in DeeplAPI
= 1.5.7 - 07/04/2021 10:41:41 =
*adding formality level settings*
= 1.5.5.1 - 03/02/2021 12:49:46 =
*minor correction for classic editor / text view*
= 1.5.5 - 28/01/2021 15:51:52 =
*wow. way better content replacement in Gutenberg*
= 1.5.3.2 =
*improve tinyMCE editor management (still a problem with missing linebreaks)*
= 1.5.3.1 =
*fix for classic editor linebreaks*
= 1.5.3 =
*First release for Pro Version*
= 1.5.2.9 =
*fixed ugly paragraphs/linebreaks from DeepL ( seriously </br> ??? )*
= 1.5.2.8 =
*fix "Disable Gutenberg"*
= 1.5.2.7 =
*fix pour language japonais (JA not JP)*
= 1.5.2.6 =
*fix pour plugin "Classic Editor"*
= 1.5.2.5 =
*slight change of block pattern to improve HTML syntax after translation*
= 1.5.2.4 =
*fixing language selection. not happy with DeepL syntax of "EN-US" instead of "en_US" or "EN_US". typography for dummies*
= 1.5.2 =
*actually working with Gutenberg*
= 1.5.1 =
*languages passées en locales*
= 1.5.0 - 12/08/2020 15:16:15 =
*compatibilité Gutenberg !*
= 1.3.2 - 08/12/2019 14:24:35 =
*amélioration classe admin.*
correction APIUsage
= 1.3.1 - 06/12/2019 08:36:53 =
*ajout du RU*
= 1.3 - 01/12/2019 10:31:08 =
*MAJ WP5.3*
MAJ URL Deepl
= 1.2 - 02/10/2019 09:25:15 =
*updated settings API*
= 1.1.7 - 02/04/2019 10:28:25 =
*default values for metabox context/priority*
= 1.1.5 - 13/02/2019 21:57:56 =
*ajout locale en_US*
= 1.1.4 - 26/01/2019 13:01:47 =
*correction bug metabox (missing /div)*
= 1.1.3 - 25/01/2019 09:45:45 =
*ajout des options metabox*
= 1.1.2 - 24/01/2019 08:03:54 =
*version bump*
= 1.1.1 - 23/01/2019 18:26:34 =
*urlencode du text pour éviter les sauts d'arugments*
= 1.1 - 23/01/2019 14:30:28 =
*ajout de logs*
= 1.0 - 17/01/2019 12:16:22 =
*refonte complete*
reporté : compatibilité Gutenberg
= 0.3.0 - 07/11/2018 07:55:39 =
*nouvelle URL d'API + fix POST request schalipp*
= 0.2.1 =
*translate seulement URL ou RSS / categories par flux / debug : extrait remplacé à la traduction manuelle*
= 0.1.4 =
*ajout du lien source originale au contenu  / ajout d'un selecteur admin nombre de posts à traduire*
20180418 : 0.1.041813
bug fixes / updated the free version with better functions from paid plugin
20180315 : 0.1.20180315
first version


== Screenshots ==

1. To be used with an active DeepL plan WITH API access