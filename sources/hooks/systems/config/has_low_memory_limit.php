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

class Hook_config_has_low_memory_limit
{
	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'HAS_LOW_MEMORY_LIMIT',
			'type'=>'tick',
			'category'=>'SERVER',
			'group'=>'ADVANCED',
			'explanation'=>'CONFIG_OPTION_has_low_memory_limit',
			'shared_hosting_restricted'=>'0',
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
		return ((ini_get('memory_limit')=='-1' || ini_get('memory_limit')=='0' || ini_get('memory_limit')=='')?'0':NULL);
	}
}


