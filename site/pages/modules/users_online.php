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
 * @package		core_ocf
 */

/**
 * Module page class.
 */
class Module_users_online
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-pagelink rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		if (get_forum_type()!='ocf') return NULL;

		if (get_option('session_prudence')==='1')
			return array();
		return array(
			'!'=>array('USERS_ONLINE','menu/social/users_online'),
		);
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		$this->title=get_screen_title('USERS_ONLINE');

		attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();

		$count=0;
		require_code('users2');
		$members=get_users_online(has_privilege(get_member(),'show_user_browsing'),NULL,$count);
		if ((is_null($members)) && (has_privilege(get_member(),'show_user_browsing')))
		{
			$members=get_users_online(false,NULL,$count);
		}
		if (is_null($members)) warn_exit(do_lang_tempcode('TOO_MANY_USERS_ONLINE'));

		$rows=new ocp_tempcode();
		$members=array_reverse($members);
		sort_maps_by($members,'last_activity');
		$members=array_reverse($members);
		foreach ($members as $row)
		{
			$last_activity=$row['last_activity'];
			$member=$row['member_id'];
			//$username=$row['cache_username'];
			$location=$row['the_title'];
			if (($location=='') && ($row['the_type']=='rss'))
			{
				$location='RSS';
				$at_url=make_string_tempcode(find_script('backend'));
			}
			elseif (($location=='') && ($row['the_page']==''))
			{
				$at_url=new ocp_tempcode();
			} else
			{
				$map=array('page'=>$row['the_page']);
				if ($row['the_type']!='') $map['type']=$row['the_type'];
				if ($row['the_id']!='') $map['id']=$row['the_id'];
				$at_url=build_url($map,$row['the_zone']);
			}
			$ip=$row['ip'];
			if (substr($ip,-1)=='*') // sessions IPs are not full
			{
				if (is_guest($member))
				{
					if (addon_installed('stats'))
					{
						$test=$GLOBALS['SITE_DB']->query_select_value_if_there('stats','ip',array('member_id'=>-$row['the_session']));
						if ((!is_null($test)) && ($test!=''))
						{
							$ip=$test;
						} else
						{
							$test=$GLOBALS['SITE_DB']->query_value_if_there('SELECT ip FROM '.get_table_prefix().'stats WHERE ip LIKE \''.db_encode_like(str_replace('*','%',$ip)).'\' ORDER BY date_and_time DESC');
							if ((!is_null($test)) && ($test!='')) $ip=$test;
						}
					}
				} else
				{
					$test=$GLOBALS['FORUM_DRIVER']->get_member_ip($member);
					if ((!is_null($test)) && ($test!='')) $ip=$test;
				}
			}

			$link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($member);

			if ($ip!='') // CRON?
				$rows->attach(do_template('OCF_MEMBER_ONLINE_ROW',array('_GUID'=>'2573786f3bccf9e613b125befb3730e8','IP'=>$ip,'AT_URL'=>$at_url,'LOCATION'=>$location,'MEMBER'=>$link,'TIME'=>integer_format(intval((time()-$last_activity)/60)))));
		}

		if ($rows->is_empty()) warn_exit(do_lang_tempcode('NO_ENTRIES'));

		return do_template('OCF_MEMBERS_ONLINE_SCREEN',array('_GUID'=>'2f63e2926c5a4690d905f97661afe6cc','TITLE'=>$this->title,'ROWS'=>$rows));
	}

}


