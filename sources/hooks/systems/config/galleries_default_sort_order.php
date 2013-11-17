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
 * @package		galleries
 */

class Hook_config_galleries_default_sort_order
{
	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'GALLERIES_DEFAULT_SORT_ORDER',
			'type'=>'list',
			'category'=>'GALLERY',
			'group'=>'BROWSING_GALLERIES',
			'explanation'=>'CONFIG_OPTION_galleries_default_sort_order',
			'shared_hosting_restricted'=>'0',
			'list_options'=>'add_date DESC|add_date ASC|average_rating DESC|compound_rating DESC|url DESC|url ASC|fixed_random ASC',

			'addon'=>'galleries',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return 'add_date DESC';
	}
}


