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
 * Standard code module initialisation function.
 */
function init__ocf_general()
{
	global $SET_CONTEXT_FORUM;
	$SET_CONTEXT_FORUM=NULL;
}

/**
 * Get some forum stats.
 *
 * @return array	A map of forum stats.
 */
function ocf_get_forums_stats()
{
	$out=array();

	$out['num_topics']=$GLOBALS['OCF_DRIVER']->get_topics();
	$out['num_posts']=$GLOBALS['OCF_DRIVER']->get_num_forum_posts();
	$out['num_members']=$GLOBALS['OCF_DRIVER']->get_members();

	$temp=get_value_newer_than('ocf_newest_member_id',time()-60*60*1);
	$out['newest_member_id']=is_null($temp)?NULL:intval($temp);
	if (!is_null($out['newest_member_id']))
	{
		$out['newest_member_username']=get_value_newer_than('ocf_newest_member_username',time()-60*60*1);
	} else
	{
		$out['newest_member_username']=NULL;
	}
	if (is_null($out['newest_member_username']))
	{
		$newest_member=$GLOBALS['FORUM_DB']->query('SELECT m_username,id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE m_validated=1 AND id<>'.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).' ORDER BY m_join_time DESC',1); // Only ordered by m_join_time and not double ordered with ID to make much faster in MySQL
		$out['newest_member_id']=$newest_member[0]['id'];
		$out['newest_member_username']=$newest_member[0]['m_username'];
		if (get_db_type()!='xml')
		{
			if (!$GLOBALS['SITE_DB']->table_is_locked('values'))
			{
				set_value('ocf_newest_member_id',strval($out['newest_member_id']));
				set_value('ocf_newest_member_username',$out['newest_member_username']);
			}
		}
	}

	return $out;
}

/**
 * Get details on a member profile.
 *
 * @param  MEMBER		The member to get details of.
 * @param  boolean	Whether to get a 'lite' version (contains less detail, therefore less costly).
 * @return array 		A map of details.
 */
function ocf_read_in_member_profile($member_id,$lite=true)
{
	$row=$GLOBALS['OCF_DRIVER']->get_member_row($member_id);
	if (is_null($row)) return array();
	$last_visit_time=(($member_id==get_member()) && (array_key_exists('last_visit',$_COOKIE)))?intval($_COOKIE['last_visit']):$row['m_last_visit_time'];
	$join_time=$row['m_join_time'];

	$out=array(
			'username'=>$row['m_username'],
			'last_visit_time'=>$last_visit_time,
			'last_visit_time_string'=>get_timezoned_date($last_visit_time),
			'signature'=>$row['m_signature'],
			'posts'=>$row['m_cache_num_posts'],
			'join_time'=>$join_time,
			'join_time_string'=>get_timezoned_date($join_time),
	);

	if (addon_installed('points'))
	{
		require_code('points');
		$num_points=total_points($member_id);
		$out['points']=$num_points;
	}

	if (!$lite)
	{
		$out['groups']=ocf_get_members_groups($member_id);

		// Custom fields
		$out['custom_fields']=ocf_get_all_custom_fields_match_member($member_id,((get_member()!=$member_id) && (!has_privilege(get_member(),'view_any_profile_field')))?1:NULL,((get_member()!=$member_id) && (!has_privilege(get_member(),'view_any_profile_field')))?1:NULL);

		// Birthdate
		if ($row['m_reveal_age']==1)
		{
			$out['birthdate']=$row['m_dob_year'].'/'.$row['m_dob_month'].'/'.$row['m_dob_day'];
		}

		// Find title
		if (addon_installed('ocf_member_titles'))
		{
			$title=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_title');
			if ($title=='')
			{
				$primary_group=ocf_get_member_primary_group($member_id);
				$title=ocf_get_group_property($primary_group,$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'title'));
			}
			if ($title!='') $out['title']=$title;
		}

		// Find photo
		$photo=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_photo_thumb_url');
		if (($photo!='') && (addon_installed('ocf_member_photos')))
		{
			if (url_is_local($photo)) $photo=get_complex_base_url($photo).'/'.$photo;
			$out['photo']=$photo;
		}

		// Any warnings?
		if ((has_privilege(get_member(),'see_warnings')) && (addon_installed('ocf_warnings')))
		{
			$out['warnings']=ocf_get_warnings($member_id);
		}
	}

	// Find avatar
	$avatar=$GLOBALS['OCF_DRIVER']->get_member_avatar_url($member_id);
	if ($avatar!='')
	{
		$out['avatar']=$avatar;
	}

	// Primary usergroup
	require_code('ocf_members');
	$primary_group=ocf_get_member_primary_group($member_id);
	$out['primary_group']=$primary_group;
	require_code('ocf_groups');
	$out['primary_group_name']=ocf_get_group_name($primary_group);

	// Find how many points we need to advance
	if (addon_installed('points'))
	{
		$promotion_threshold=ocf_get_group_property($primary_group,'promotion_threshold');
		if (!is_null($promotion_threshold))
		{
			$num_points_advance=$promotion_threshold-$num_points;
			$out['num_points_advance']=$num_points_advance;
		}
	}

	return $out;
}

/**
 * Get a usergroup colour based on it's ID number.
 *
 * @param  GROUP			ID number.
 * @return string			Colour.
 */
function get_group_colour($gid)
{
	$all_colours=array('ocf_gcol_1','ocf_gcol_2','ocf_gcol_3','ocf_gcol_4','ocf_gcol_5','ocf_gcol_6','ocf_gcol_7','ocf_gcol_8','ocf_gcol_9','ocf_gcol_10','ocf_gcol_11','ocf_gcol_12','ocf_gcol_13','ocf_gcol_14','ocf_gcol_15');
	return $all_colours[$gid%count($all_colours)];
}

/**
 * Find all the birthdays in a certain day.
 *
 * @param  ?TIME	A timestamps that exists in the certain day (NULL: now).
 * @return array	List of maps describing the members whose birthday it is on the certain day.
 */
function ocf_find_birthdays($time=NULL)
{
	if (is_null($time)) $time=time();

	list($day,$month,$year)=explode(' ',date('j m Y',utctime_to_usertime($time)));
	$rows=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_username','m_reveal_age','m_dob_year'),array('m_dob_day'=>intval($day),'m_dob_month'=>intval($month)),'ORDER BY m_last_visit_time DESC',20);

	$birthdays=array();
	foreach ($rows as $row)
	{
		$birthday=array('id'=>$row['id'],'username'=>$row['m_username']);
		if ($row['m_reveal_age']==1) $birthday['age']=intval($year)-$row['m_dob_year'];

		$birthdays[]=$birthday;
	}

	return $birthdays;
}

/**
 * Turn a list of maps describing buttons, into a tempcode button panel.
 *
 * @param  array		List of maps (each map contains: url, img, title).
 * @return tempcode  The button panel.
 */
function ocf_button_screen_wrap($buttons)
{
	if (count($buttons)==0) return new ocp_tempcode();

	$b=new ocp_tempcode();
	foreach ($buttons as $button)
	{
		$b->attach(do_template('BUTTON_SCREEN',array('_GUID'=>'bdd441c40c5b03134ce6541335fece2c','REL'=>array_key_exists('rel',$button)?$button['rel']:NULL,'IMMEDIATE'=>$button['immediate'],'URL'=>$button['url'],'IMG'=>$button['img'],'TITLE'=>$button['title'])));
	}
	return $b;
}

/**
 * Set the forum context.
 *
 * @param  AUTO_LINK	Forum ID.
 */
function ocf_set_context_forum($forum_id)
{
	global $SET_CONTEXT_FORUM;
	$SET_CONTEXT_FORUM=$forum_id;
}
