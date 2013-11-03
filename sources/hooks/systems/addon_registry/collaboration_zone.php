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
 * @package		collaboration_zone
 */

class Hook_addon_registry_collaboration_zone
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
		return 'Collaboration Zone.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_collaboration',
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
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images/icons/48x48/menu/collaboration.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images/icons/24x24/menu/collaboration.png',
			'themes/default/images/icons/48x48/menu/collaboration.png',
			'themes/default/images/icons/24x24/menu/collaboration/index.html',
			'themes/default/images/icons/48x48/menu/collaboration/index.html',
			'sources/hooks/systems/addon_registry/collaboration_zone.php',
			'sources/hooks/modules/admin_themewizard/collaboration_zone.php',
			'themes/default/images/EN/logo/collaboration-logo.png',
			'collaboration/index.php',
			'collaboration/pages/comcode/.htaccess',
			'collaboration/pages/comcode/EN/.htaccess',
			'collaboration/pages/comcode/EN/about.txt',
			'collaboration/pages/comcode/EN/index.html',
			'collaboration/pages/comcode/EN/panel_left.txt',
			'collaboration/pages/comcode/EN/start.txt',
			'collaboration/pages/comcode/index.html',
			'collaboration/pages/comcode_custom/.htaccess',
			'collaboration/pages/comcode_custom/EN/.htaccess',
			'collaboration/pages/comcode_custom/EN/index.html',
			'collaboration/pages/comcode_custom/index.html',
			'collaboration/pages/html/.htaccess',
			'collaboration/pages/html/EN/.htaccess',
			'collaboration/pages/html/EN/index.html',
			'collaboration/pages/html/index.html',
			'collaboration/pages/html_custom/EN/.htaccess',
			'collaboration/pages/html_custom/EN/index.html',
			'collaboration/pages/html_custom/index.html',
			'collaboration/pages/index.html',
			'collaboration/pages/minimodules/.htaccess',
			'collaboration/pages/minimodules/index.html',
			'collaboration/pages/minimodules_custom/.htaccess',
			'collaboration/pages/minimodules_custom/index.html',
			'collaboration/pages/modules/.htaccess',
			'collaboration/pages/modules/index.html',
			'collaboration/pages/modules_custom/.htaccess',
			'collaboration/pages/modules_custom/index.html',
			'sources/hooks/systems/do_next_menus/collaboration_zone.php',
		);
	}

}
