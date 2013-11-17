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
 * @package		core_configuration
 */

class Hook_config_gzip_output
{
	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'GZIP_OUTPUT',
			'type'=>'tick',
			'category'=>'PERFORMANCE',
			'group'=>'FRONT_END_PERFORMANCE',
			'explanation'=>'CONFIG_OPTION_gzip_output',
			'shared_hosting_restricted'=>'1',
			'list_options'=>'',

			'addon'=>'core_configuration',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		if (GOOGLE_APPENGINE) return NULL;
		return '0';
	}
}


