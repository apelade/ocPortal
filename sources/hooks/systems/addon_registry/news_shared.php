<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		news_shared
 */

class Hook_addon_registry_news_shared
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return '(Common files needed for RSS and News addons)';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_news',
			'tut_adv_news',
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources/hooks/systems/addon_registry/news_shared.php',
			'themes/default/templates/NEWS_BOX.tpl',
			'themes/default/templates/NEWS_BRIEF.tpl',
			'themes/default/css/news.css',
			'lang/EN/news.ini',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'NEWS_BOX.tpl'=>'news_piece_summary'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__news_piece_summary()
	{
		return array(
			lorem_globalise(do_lorem_template('NEWS_BOX',array(
				'BLOG'=>lorem_phrase(),
				'AUTHOR_URL'=>placeholder_url(),
				'TAGS'=>'',
				'CATEGORY'=>lorem_phrase(),
				'IMG'=>placeholder_image_url(),
				'_IMG'=>placeholder_image_url(),
				'AUTHOR'=>lorem_phrase(),
				'_AUTHOR'=>lorem_phrase(),
				'SUBMITTER'=>placeholder_id(),
				'AVATAR'=>lorem_phrase(),
				'NEWS_TITLE'=>lorem_phrase(),
				'DATE'=>lorem_phrase(),
				'NEWS'=>lorem_phrase(),
				'COMMENTS'=>lorem_phrase(),
				'VIEW'=>lorem_phrase(),
				'ID'=>placeholder_id(),
				'FULL_URL'=>placeholder_url(),
				'COMMENT_COUNT'=>lorem_phrase(),
				'READ_MORE'=>lorem_sentence(),
				'TRUNCATE'=>false,
				'FIRSTTIME'=>lorem_word(),
				'LASTTIME'=>lorem_word_2(),
				'CLOSED'=>lorem_word(),
				'FIRSTUSERNAME'=>lorem_word(),
				'LASTUSERNAME'=>lorem_word(),
				'FIRSTMEMBERID'=>lorem_word(),
				'LASTMEMBERID'=>lorem_word(),
				'DATE_RAW'=>lorem_word(),
				'GIVE_CONTEXT'=>true,
			)), NULL, '', true)
		);
	}
}
