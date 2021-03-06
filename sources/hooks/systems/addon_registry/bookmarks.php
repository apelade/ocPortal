<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		bookmarks
 */

class Hook_addon_registry_bookmarks
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
		return 'Allow members to bookmark screens of the website. As the bookmarks are tied to their member profile they can access them from any computer they can log in on.';
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
			'conflicts_with'=>array(),
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(

			'sources/bookmarks.php',
			'data/bookmarks.php',
			'sources/hooks/systems/addon_registry/bookmarks.php',
			'sources/hooks/modules/admin_import_types/bookmarks.php',
			'BOOKMARKS_SCREEN.tpl',
			'themes/default/images/bottom/bookmarksmenu.png',
			'themes/default/images/bottom/bookmarksmenu_off.png',
			'lang/EN/bookmarks.ini',
			'site/pages/modules/bookmarks.php',
			'sources/hooks/systems/snippets/bookmarks_menu.php',
			'bookmarks.css',
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
				'BOOKMARKS_SCREEN.tpl'=>'bookmarks_screen',
				);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__bookmarks_screen()
	{
		require_lang('zones');

		$bookmarks=array(
			array(
				'CAPTION'=>lorem_word(),
				'ID'=>placeholder_id(),
				'FOLDER'=>lorem_word_2(),
				'PAGE_LINK'=>placeholder_url(),
			),
		);

		return array(
			lorem_globalise(
				do_lorem_template('BOOKMARKS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'FORM_URL'=>placeholder_url(),
					'FORM'=>placeholder_form(),
					'BOOKMARKS'=>$bookmarks,
						)
			),NULL,'',true),
		);
	}
}
