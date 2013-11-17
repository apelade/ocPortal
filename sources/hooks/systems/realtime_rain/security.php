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
 * @package		securitylogging
 */

class Hook_realtime_rain_security
{
	/**
	 * Standard modular run function for realtime-rain hooks.
	 *
	 * @param  TIME			Start of time range.
	 * @param  TIME			End of time range.
	 * @return array			A list of template parameter sets for rendering a 'drop'.
	 */
	function run($from,$to)
	{
		$drops=array();

		if (has_actual_page_access(get_member(),'admin_security'))
		{
			$rows=$GLOBALS['SITE_DB']->query('SELECT id,reason,ip,date_and_time AS timestamp,member_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'hackattack WHERE date_and_time BETWEEN '.strval($from).' AND '.strval($to));

			foreach ($rows as $row)
			{
				require_lang('security');

				$timestamp=$row['timestamp'];
				$member_id=$row['member_id'];

				$drops[]=rain_get_special_icons($row['ip'],$timestamp)+array(
					'TYPE'=>'security',
					'FROM_MEMBER_ID'=>strval($member_id),
					'TO_MEMBER_ID'=>NULL,
					'TITLE'=>rain_truncate_for_title(do_lang('HACKER_DETECTED',do_lang($row['reason']))),
					'IMAGE'=>is_guest($member_id)?rain_get_country_image($row['ip']):$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
					'TIMESTAMP'=>strval($timestamp),
					'RELATIVE_TIMESTAMP'=>strval($timestamp-$from),
					'TICKER_TEXT'=>NULL,
					'URL'=>build_url(array('page'=>'admin_security','type'=>'view','id'=>$row['id']),'_SEARCH'),
					'IS_POSITIVE'=>false,
					'IS_NEGATIVE'=>true,

					// These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
					'FROM_ID'=>'member_'.strval($member_id),
					'TO_ID'=>NULL,
					'GROUP_ID'=>'hack_type_'.$row['reason'],
				);
			}
		}

		return $drops;
	}
}
