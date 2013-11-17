<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		gallery_syndication
 */

class Hook_config_vimeo_client_secret
{
	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'VIMEO_CLIENT_SECRET',
			'type'=>'line',
			'category'=>'GALLERY',
			'group'=>'GALLERY_SYNDICATION',
			'explanation'=>'CONFIG_OPTION_vimeo_client_secret',
			'shared_hosting_restricted'=>'0',
			'list_options'=>'',

			'addon'=>'gallery_syndication',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return '';
	}
}


