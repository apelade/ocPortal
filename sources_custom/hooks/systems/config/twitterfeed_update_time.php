<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		twitter_feed_integration_block
 */

class Hook_config_twitterfeed_update_time
{
	/**
	 * Gets the details relating to the config option.
	 *
	 * @return ?array		The details (NULL: disabled)
	 */
	function get_details()
	{
		return array(
			'human_name'=>'UPDATE_TIME',
			'type'=>'integer',
			'category'=>'BLOCKS',
			'group'=>'TWITTER_FEED_INTEGRATION',
			'explanation'=>'CONFIG_OPTION_twitterfeed_update_time',
			'shared_hosting_restricted'=>'0',
			'list_options'=>'',
			'required'=>true,

			'addon'=>'twitter_feed_integration_block',
		);
	}

	/**
	 * Gets the default value for the config option.
	 *
	 * @return ?string		The default value (NULL: option is disabled)
	 */
	function get_default()
	{
		return '30';
	}
}


