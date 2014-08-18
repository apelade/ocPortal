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
 * @package		tickets
 */

class Hook_change_detection_tickets
{
	/**
	 * Standard modular run function for change_detection hooks. They see if their own something has changed in comparison to sample data.
	 *
	 * @param  string			The sample data, serialised and then MD5'd
	 * @return boolean		Whether the something has changed
	 */
	function run($data)
	{
		if (get_param('type','misc')=='misc')
		{
			require_code('tickets');
			require_code('tickets2');
			$ticket_type_id=get_param_integer('ticket_type_id',NULL);
			$tickets=get_tickets(get_member(),$ticket_type_id);
			return md5(serialize($tickets))!=$data;
		}

		$id=get_param('id',NULL);
		require_code('tickets');
		require_code('tickets2');
		$forum=0;
		$topic_id=0;
		$ticket_type_id=0;
		$_comments=get_ticket_posts($id,$forum,$topic_id,$ticket_type_id);

		return md5(serialize($_comments))!=$data;
	}
}


