<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_thanks
 */

class Hook_addon_registry_oc_thanks
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
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'Graphical';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array();
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'Licensed on the same terms as ocPortal';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Show the top performing members in a community. The addon adds a [tt]main_stars[/tt] block that ranks members on how many points they have been given in a certain category (also it changes the points module to allow selection of such categories when giving points). It also adds a block to show recent points transfers. Finally, it adds a line to member\'s profile screens that says how many topics they have created, and how many they have replied to, to give a reflection of whether they help more than they ask or vice-versa.
		
Usage:
[code=\"Comcode\"][block max=\"10\"]side_recent_points[/block][/code]
and
[code=\"Comcode\"][block=\"Helpful soul\"]main_stars[/block][/code]The [tt]POINTS_GIVE[/tt] ([tt]themes/default/templates_custom[/tt]) template contains hard-coded HTML that defines each kind of points category that can be used. It is likely you will want to put out one an instance of the [tt]main_stars[/tt] block for each category (using the syntax demonstrated above).';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
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
			'requires'=>array(
				'Javascript enabled',
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
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
			'sources_custom/hooks/systems/addon_registry/oc_thanks.php',
			'sources_custom/hooks/modules/members/octhanks.php',
			'sources_custom/miniblocks/main_stars.php',
			'sources_custom/miniblocks/side_recent_points.php',
			'themes/default/templates_custom/POINTS_GIVE.tpl',
		);
	}
}