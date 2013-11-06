<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocportalcom_support_credits
 */

class Hook_config_support_budget_priority
{

	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'SUPPORT_PRIORITY_BUDGET_MINUTES',
			'type'=>'float',
			'category'=>'FEATURE',
			'group'=>'SECTION_CUSTOMERS',
			'explanation'=>'CONFIG_OPTION_support_budget_priority',
			'shared_hosting_restricted'=>'0',
			'list_options'=>'',
			'order_in_category_group'=>5,

			'addon'=>'ocportalcom_support_credits',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return '10';
	}

}

