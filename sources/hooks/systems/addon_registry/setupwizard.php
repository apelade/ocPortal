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
 * @package		setupwizard
 */

class Hook_addon_registry_setupwizard
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
		return 'Quick-start setup wizard.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_configuration',
			'tut_drinking',
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
			'conflicts_with'=>array(),
			'previously_in_addon'=>array(
				'core_setupwizard'
			)
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/menu/adminzone/setup/setupwizard.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images/icons/24x24/menu/adminzone/setup/setupwizard.png',
			'themes/default/images/icons/48x48/menu/adminzone/setup/setupwizard.png',
			'sources/hooks/modules/admin_setupwizard_installprofiles/.htaccess',
			'sources/hooks/modules/admin_setupwizard_installprofiles/index.html',
			'sources/hooks/modules/admin_setupwizard_installprofiles/community.php',
			'sources/hooks/modules/admin_setupwizard_installprofiles/infosite.php',
			'themes/default/templates/SETUPWIZARD_2_SCREEN.tpl',
			'themes/default/templates/SETUPWIZARD_BLOCK_PREVIEW.tpl',
			'sources/hooks/systems/addon_registry/setupwizard.php',
			'sources/setupwizard.php',
			'sources/hooks/systems/preview/setupwizard.php',
			'sources/hooks/systems/preview/setupwizard_blocks.php',
			'themes/default/templates/SETUPWIZARD_7_SCREEN.tpl',
			'adminzone/pages/modules/admin_setupwizard.php',
			'text/EN/rules_balanced.txt',
			'text/EN/rules_corporate.txt',
			'text/EN/rules_liberal.txt',
			'sources/hooks/modules/admin_setupwizard/.htaccess',
			'sources/hooks/modules/admin_setupwizard/index.html',
			'sources/hooks/systems/do_next_menus/setupwizard.php',
			'sources/hooks/modules/admin_setupwizard/core.php',
			'sources/hooks/modules/admin_setupwizard_installprofiles/minimalistic.php',
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
			'SETUPWIZARD_2_SCREEN.tpl'=>'administrative__setupwizard_2_screen',
			'SETUPWIZARD_7_SCREEN.tpl'=>'administrative__setupwizard_7_screen',
			'SETUPWIZARD_BLOCK_PREVIEW.tpl'=>'administrative__setupwizard_block_preview'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__setupwizard_block_preview()
	{
		return array(
			lorem_globalise(do_lorem_template('SETUPWIZARD_BLOCK_PREVIEW',array(
				'LEFT'=>lorem_paragraph(),
				'RIGHT'=>lorem_paragraph(),
				'START'=>lorem_paragraph()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__setupwizard_2_screen()
	{
		require_lang('config');
		return array(
			lorem_globalise(do_lorem_template('SETUPWIZARD_2_SCREEN',array(
				'SKIP_VALIDATION'=>true,
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url(),
				'SUBMIT_NAME'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__setupwizard_7_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('SETUPWIZARD_7_SCREEN',array(
				'TITLE'=>lorem_title(),
				'FORM'=>placeholder_form(),
				'BALANCED'=>lorem_phrase(),
				'LIBERAL'=>lorem_phrase(),
				'CORPORATE'=>lorem_phrase()
			)), NULL, '', true)
		);
	}
}
