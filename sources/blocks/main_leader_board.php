<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		points
 */

class Block_main_leader_board
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
		$info['version']=3;
		$info['locked']=false;
		$info['parameters']=array('param','zone','staff');
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(intval(array_key_exists(\'staff\',$map)?$map[\'staff\']:\'0\'),array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'leader_board\'),array_key_exists(\'param\',$map)?intval($map[\'param\']):5)';
		$info['ttl']=(get_value('no_block_timeout')==='1')?60*60*24*365*5/*5 year timeout*/:60*24*7;
		return $info;
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('leader_board',array(
				'lb_member'=>'*MEMBER',
				'lb_points'=>'INTEGER',
				'date_and_time'=>'*TIME'
			));

			add_config_option('LEADERBOARD_START_DATE','leaderboard_start_date','date','return strval(filemtime(get_file_base().\'/index.php\'));','POINTS','POINT_LEADERBOARD');
		}
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		delete_config_option('leaderboard_start_date');
		$GLOBALS['SITE_DB']->drop_table_if_exists('leader_board');
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		$limit=array_key_exists('param',$map)?intval($map['param']):5;
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('leader_board');
		$staff=intval(array_key_exists('staff',$map)?$map['staff']:'0');

		require_lang('leader_board');
		require_code('points');
		require_css('points');

		$cutoff=time()-60*60*24*7;
		$rows=$GLOBALS['SITE_DB']->query('SELECT lb_member,lb_points FROM '.get_table_prefix().'leader_board WHERE date_and_time>'.strval($cutoff));
		$rows=collapse_2d_complexity('lb_member','lb_points',$rows);
		if (count($rows)==0)
		{
			$rows=$this->calculate_leaderboard($limit,$staff);
		} else
		{
			arsort($rows);
		}
		$out=new ocp_tempcode();
		$i=0;

		// Are there any rank images going to display?
		$or_list='1=1';
		$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
		$moderator_groups=$GLOBALS['FORUM_DRIVER']->get_moderator_groups();
		foreach (array_merge($admin_groups,$moderator_groups) as $group_id)
			$or_list.=' AND id<>'.strval($group_id);
		$has_rank_images=(get_forum_type()=='ocf') && ($GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_groups WHERE '.$or_list.' AND '.db_string_not_equal_to('g_rank_image',''))!=0);

		foreach ($rows as $member=>$points)
		{
			if ($i==$limit) break;

			if (is_guest($member)) continue; // Should not happen, but some forum drivers might suck ;)
			if (count($rows)>=$limit) // We don't allow staff, if there are enough to show without
			{
				if (($staff==0) && ($GLOBALS['FORUM_DRIVER']->is_staff($member))) continue;
			}

			$points_url=build_url(array('page'=>'points','type'=>'member','id'=>$member),get_module_zone('points'));
			$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member,true,true);
			$username=$GLOBALS['FORUM_DRIVER']->get_username($member);
			if (is_null($username)) continue;

			if ($i==0) set_value('site_bestmember',$username);

			$out->attach(do_template('POINTS_LEADERBOARD_ROW',array(
				'_GUID'=>'68caa55091aade84bc7ca760e6655a45',
				'ID'=>strval($member),
				'POINTS_URL'=>$points_url,
				'PROFILE_URL'=>$profile_url,
				'POINTS'=>integer_format($points),
				'USERNAME'=>$username,
				'HAS_RANK_IMAGES'=>$has_rank_images,
			)));

			$i++;
		}

		$url=build_url(array('page'=>'leader_board'),$zone);

		return do_template('POINTS_LEADERBOARD',array('_GUID'=>'c875cce925e73f46408acc0a153a2902','URL'=>$url,'LIMIT'=>integer_format($limit),'ROWS'=>$out));
	}

	/**
	 * Calculate the leaderboard.
	 *
	 * @param  integer		The number to show on the leaderboard
	 * @param  BINARY			Whether to include staff
	 * @return array			A map of member-ids to points, sorted by leaderboard status, of the top posters (doing for points would be too inefficient)
	 */
	function calculate_leaderboard($limit,$staff)
	{
		$all_members=$GLOBALS['FORUM_DRIVER']->get_top_posters(max(100,$limit));
		$points=array();
		foreach ($all_members as $member)
		{
			$id=$GLOBALS['FORUM_DRIVER']->mrow_id($member);
			if (count($all_members)>=$limit) // We don't allow staff, if there are enough to show without
			{
				if (($staff==0) && ($GLOBALS['FORUM_DRIVER']->is_staff($id))) continue;
			}
			$points[$id]=total_points($id);
		}

		arsort($points);

		$i=0;
		$time=time();
		foreach ($points as $id=>$num_points)
		{
			if ($i==$limit) break;
			$GLOBALS['SITE_DB']->query_insert('leader_board',array('lb_member'=>$id,'lb_points'=>$num_points,'date_and_time'=>$time),false,true); // Allow failure due to race conditions

			$i++;
		}
		return $points;
	}

}


