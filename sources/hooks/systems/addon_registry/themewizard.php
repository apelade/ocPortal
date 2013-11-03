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
 * @package		themewizard
 */

class Hook_addon_registry_themewizard
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
		return 'Automatically generate your own colour schemes using the default theme as a base. Uses the sophisticated chromographic equations built into ocPortal.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_themes',
			'tut_designer_themes',
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
		return 'themes/default/images/icons/48x48/menu/adminzone/style/themes/themewizard.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images/icons/24x24/menu/adminzone/style/themes/logowizard.png',
			'themes/default/images/icons/24x24/menu/adminzone/style/themes/themewizard.png',
			'themes/default/images/icons/48x48/menu/adminzone/style/themes/logowizard.png',
			'themes/default/images/icons/48x48/menu/adminzone/style/themes/themewizard.png',
			'sources/hooks/systems/occle_commands/themewizard_find_color.php',
			'sources/hooks/systems/occle_commands/themewizard_compute_equation.php',
			'sources/hooks/modules/admin_themewizard/.htaccess',
			'sources/hooks/systems/snippets/themewizard_equation.php',
			'sources/hooks/modules/admin_themewizard/index.html',
			'sources/hooks/systems/addon_registry/themewizard.php',
			'sources/themewizard.php',
			'adminzone/pages/modules/admin_themewizard.php',
			'themes/default/templates/THEMEWIZARD_2_SCREEN.tpl',
			'themes/default/templates/THEMEWIZARD_2_PREVIEW.tpl',
			'adminzone/themewizard.php',
			'sources/hooks/systems/do_next_menus/themewizard.php',
			'themes/default/templates/LOGOWIZARD_2.tpl',
			'adminzone/logowizard.php',
			'themes/default/images/logo_template.png',
			'themes/default/images/trimmed_logo_template.png',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array						The mapping
	 */
	function tpl_previews()
	{
		return array(
			'THEMEWIZARD_2_PREVIEW.tpl'=>'administrative__themewizard_2_preview',
			'THEMEWIZARD_2_SCREEN.tpl'=>'administrative__themewizard_2_screen',
			'LOGOWIZARD_2.tpl'=>'administrative__logowizard_2'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__themewizard_2_preview()
	{
		require_lang('themes');

		$content=do_lorem_template('THEMEWIZARD_2_PREVIEW');

		return array(
			lorem_globalise($content, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__themewizard_2_screen()
	{
		require_lang('themes');

		return array(
			lorem_globalise(do_lorem_template('THEMEWIZARD_2_SCREEN',array(
				'SOURCE_THEME'=>'default',
				'ALGORITHM'=>'equations',
				'RED'=>placeholder_id(),
				'GREEN'=>placeholder_id(),
				'BLUE'=>placeholder_id(),
				'SEED'=>lorem_word(),
				'DARK'=>lorem_word_2(),
				'DOMINANT'=>lorem_word(),
				'LD'=>lorem_phrase(),
				'TITLE'=>lorem_title(),
				'CHANGE_URL'=>placeholder_url(),
				'STAGE3_URL'=>placeholder_url()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__logowizard_2()
	{
		require_lang('themes');

		$preview=do_lorem_template('LOGOWIZARD_2',array(
			'NAME'=>lorem_phrase(),
			'TITLE'=>lorem_phrase(),
			'THEME'=>lorem_phrase()
		));

		return array(
			lorem_globalise(do_lorem_template('CONFIRM_SCREEN',array(
				'URL'=>placeholder_url(),
				'BACK_URL'=>placeholder_url(),
				'PREVIEW'=>$preview,
				'FIELDS'=>'',
				'TITLE'=>lorem_title()
			)), NULL, '', true)
		);
	}
}
