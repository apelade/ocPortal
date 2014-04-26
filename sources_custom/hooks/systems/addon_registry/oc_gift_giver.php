<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_gift_giver
 */

class Hook_addon_registry_oc_gift_giver
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
		return 'Provides the ability for members to purchase a wide variety of configurable gifts and to send them to other members or even to send them themselves.

The gifts are configurable by the admin section:
 - gift title (name)
 - gift image
 - gift price (in points)

When a gift is sent to a member it creates a Private Topic that describes the gift. Also, it places the gift in the list of gifts received by the member in the profile section. Gifts also could be sent anonymously to members.

Creating new Gifts:
When creating new gifts please only use images which are free to use, we suggest http://www.openclipart.org/ which has a good selection of free to use images. Go to the set up section and click \"Manage Gifts\". Click Add Gift. Upload the image and give it a title, choose the cost to send and click save. You can edit the standard gifts or ones you have created in the same section.';
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
				'OCF',
				'pointstore',
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
		return 'themes/default/images_custom/icons/48x48/menu/ocgifts.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/menu/ocgifts.png',
			'themes/default/images_custom/icons/48x48/menu/ocgifts.png',
			'sources_custom/hooks/systems/addon_registry/oc_gift_giver.php',
			'sources_custom/hooks/systems/notifications/gift.php',
			'adminzone/pages/modules_custom/admin_ocgifts.php',
			'lang_custom/EN/ocgifts.ini',
			'sources_custom/hooks/modules/members/gifts.php',
			'sources_custom/hooks/modules/pointstore/ocgifts.php',
			'sources_custom/hooks/systems/page_groupings/ocgifts.php',
			'themes/default/templates_custom/POINTSTORE_OCGIFTS.tpl',
			'themes/default/templates_custom/POINTSTORE_OCGIFTS_GIFTS.tpl',
			'themes/default/templates_custom/OCF_MEMBER_SCREEN_GIFTS_WRAP.tpl',
			'themes/default/css/gifts.css',
			'uploads/ocgifts_addon/index.html',
			'uploads/ocgifts_addon/2a kiss.jpg',
			'uploads/ocgifts_addon/2Football_(soccer).gif',
			'uploads/ocgifts_addon/2Muga_Glass_of_red_wine.png',
			'uploads/ocgifts_addon/3Football_(soccer).gif',
			'uploads/ocgifts_addon/a kiss.jpg',
			'uploads/ocgifts_addon/Birthday_cake.gif',
			'uploads/ocgifts_addon/bouquet_of_flowers.gif',
			'uploads/ocgifts_addon/Champagne.jpg',
			'uploads/ocgifts_addon/Drum_Kit_3.jpg',
			'uploads/ocgifts_addon/electric_guitar.jpg',
			'uploads/ocgifts_addon/Football_(soccer).gif',
			'uploads/ocgifts_addon/ghirlande_festa.gif',
			'uploads/ocgifts_addon/glass_of_beer.gif',
			'uploads/ocgifts_addon/hrum_cocktail.gif',
			'uploads/ocgifts_addon/jean_victor_balin_balloons.gif',
			'uploads/ocgifts_addon/liftarn_Four_leaf_clover.gif',
			'uploads/ocgifts_addon/liftarn_Green_hat.gif',
			'uploads/ocgifts_addon/love note.jpg',
			'uploads/ocgifts_addon/love_heart.gif',
			'uploads/ocgifts_addon/Money_Bag_Icon.gif',
			'uploads/ocgifts_addon/Muga_Glass_of_red_wine.png',
			'uploads/ocgifts_addon/piano.jpg',
			'uploads/ocgifts_addon/red-rose.jpg',
			'uploads/ocgifts_addon/reporter_Happy_Valentine.gif',
			'uploads/ocgifts_addon/Santa_Hat.jpg',
			'themes/default/templates_custom/OCF_BIRTHDAY_LINK.tpl',
			'themes/default/templates_custom/OCF_USER_MEMBER.tpl',
			'lang_custom/EN/ocf.ini ',
		);
	}
}