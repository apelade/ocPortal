<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		gallery_syndicate
 */

class Hook_cron_gallery_syndicate
{

	/**
	 * Standard modular run function for CRON hooks. Searches for tasks to perform.
	 */
	function run()
	{
		$value=get_value('last_gallery_syndicate');
		if ((is_null($value)) || (intval($value)<time()-60*60))
		{
			require_code('gallery_syndicate');
			sync_video_syndication();

			set_value('last_gallery_syndicate',strval(time()));
		}
	}

}


