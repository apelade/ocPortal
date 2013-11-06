<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		reported_content
 */

class Hook_config_reported_times
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'REPORTED_TIMES',
			'type'=>'integer',
			'category'=>'FEATURE',
			'group'=>'REPORT_CONTENT',
			'explanation'=>'CONFIG_OPTION_reported_times',
			'shared_hosting_restricted'=>'0',
			'list_options'=>'',
			'required'=>true,

			'addon'=>'reported_content',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return '3';
	}

}

