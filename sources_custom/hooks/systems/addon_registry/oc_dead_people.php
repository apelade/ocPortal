<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_dead_people
 */

class Hook_addon_registry_oc_dead_people
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
		return 'Fun and Games';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Kamen Blaginov';
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
		return 'Encourage your website users to interact more and increase their activity. You can release a number of diseases all at once or one at a time. ocDeadpeople comes configured with a number of pre-created viruses and you can add more. There are also Cures and Immunizations for the diseases which can be bought through the point store. Each disease will cause a member\'s points total to become sick and start going down unless they buy the cure. The cure is usually twice as much as the immunisation. If the user cannot afford the cure they will have to interact more with the site to rebuild up their points total to be able to afford to buy it. All the pre-configured diseases come unreleased and you have the opportunity to choose when they are released and how virulent they are. Users which have been infected will be sent an email with a link to the cure. Once cured, members can still be re-infected if they have not bought an Immunization. The diseases are spread via the friend lists in ocPortal.

To configure the diseases go to Admin Zone > Setup > Manage Diseases.';
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
				'Cron',
				'OCF',
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
		return 'themes/default/images_custom/icons/48x48/menu/ocdeadpeople_log.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/menu/ocdeadpeople_log.png',
			'themes/default/images_custom/icons/48x48/menu/ocdeadpeople_log.png',
			'sources_custom/hooks/systems/addon_registry/oc_dead_people.php',
			'sources_custom/hooks/systems/notifications/got_disease.php',
			'adminzone/pages/modules_custom/admin_ocdeadpeople.php',
			'lang_custom/EN/ocdeadpeople.ini',
			'sources_custom/hooks/modules/pointstore/ocdeadpeople.php',
			'sources_custom/hooks/systems/cron/ocdeadpeople.php',
			'sources_custom/hooks/systems/do_next_menus/ocdeadpeople.php',
			'themes/default/templates_custom/POINTSTORE_OCDEADPEOPLE.tpl',
			'themes/default/templates_custom/POINTSTORE_OCDEADPEOPLE_DISEASES.tpl',
			'uploads/diseases_addon/index.html',
		);
	}
}