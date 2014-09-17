<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		locations
 */

class Hook_addon_registry_locations
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
		return 'Development';
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
		return 'Locations API, allows building out tree catalogues with all the world cities.';
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
			'sources_custom/hooks/systems/addon_registry/locations.php',
			'sources_custom/locations/.htaccess',
			'sources_custom/locations/index.html',
			'data_custom/locations/index.html',
			'data_custom/locations/sources.zip',
			'data_custom/locations/readme.txt',
			'sources_custom/locations.php',
			'sources_custom/locations_install.php',
			'sources_custom/locations_geopositioning.php',
			'sources_custom/locations/uk.php',
			'sources_custom/locations/us.php',
			'data_custom/geoposition.php',
		);
	}

	/**
	 * Uninstall the addon.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('locations');
	}

	/**
	 * Install the addon.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 */
	function install($upgrade_from=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('locations',array(
				'id'=>'*AUTO',
				'l_place'=>'SHORT_TEXT',
				'l_type'=>'ID_TEXT',
				'l_continent'=>'ID_TEXT',
				'l_country'=>'ID_TEXT',
				'l_parent_1'=>'ID_TEXT',
				'l_parent_2'=>'ID_TEXT',
				'l_parent_3'=>'ID_TEXT',
				'l_population'=>'?INTEGER',
				'l_latitude'=>'?REAL',
				'l_longitude'=>'?REAL',
				//'l_postcode'=>'ID_TEXT',	Actually often many postcodes per location and/or poor alignment
			));
			$GLOBALS['SITE_DB']->create_index('locations','l_place',array('l_place'));
			$GLOBALS['SITE_DB']->create_index('locations','l_country',array('l_country'));
			$GLOBALS['SITE_DB']->create_index('locations','l_latitude',array('l_latitude'));
			$GLOBALS['SITE_DB']->create_index('locations','l_longitude',array('l_longitude'));
			//$GLOBALS['SITE_DB']->create_index('locations','l_postcode',array('l_postcode'));
		}
	}
}