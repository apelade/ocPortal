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
 * @package		ocf_avatars
 */

class Hook_addon_registry_ocf_avatars
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
		return 'A selection of avatars for OCF';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_members',
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
			'requires'=>array('ocf_member_avatars'),
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
			'sources/hooks/systems/addon_registry/ocf_avatars.php',
			'themes/default/images/ocf_default_avatars/default_set/airplane.png',
			'themes/default/images/ocf_default_avatars/default_set/bird.png',
			'themes/default/images/ocf_default_avatars/default_set/bonfire.png',
			'themes/default/images/ocf_default_avatars/default_set/cool_flare.png',
			'themes/default/images/ocf_default_avatars/default_set/dog.png',
			'themes/default/images/ocf_default_avatars/default_set/eagle.png',
			'themes/default/images/ocf_default_avatars/default_set/forks.png',
			'themes/default/images/ocf_default_avatars/default_set/horse.png',
			'themes/default/images/ocf_default_avatars/default_set/index.html',
			'themes/default/images/ocf_default_avatars/default_set/music.png',
			'themes/default/images/ocf_default_avatars/default_set/ocp_fanatic.png',
			'themes/default/images/ocf_default_avatars/default_set/trees.png',
			'themes/default/images/ocf_default_avatars/default_set/chess.png',
			'themes/default/images/ocf_default_avatars/default_set/fireman.png',
			'themes/default/images/ocf_default_avatars/default_set/berries.png',
		);
	}
}
