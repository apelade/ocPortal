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
 * @package		ocf_forum
 */

/**
 * Get a map of details relating to the Private Topics of a certain member.
 *
 * @param  integer	The start row for getting details of topics in the Private Topic forum (i.e. 0 is newest, higher is starting further back in time).
 * @param  ?integer	The maximum number of topics to get detail of (NULL: default).
 * @param  ?MEMBER	The member to get Private Topics of (NULL: current member).
 * @return array		The details.
 */
function ocf_get_private_topics($start=0,$max=NULL,$member_id=NULL)
{
	if (is_null($max)) $max=intval(get_option('forum_topics_per_page'));

	if (is_null($member_id))
	{
		$member_id=get_member();
	} else
	{
		if ((!has_specific_permission(get_member(),'view_other_pt')) && ($member_id!=get_member()))
			access_denied('PRIVILEGE','view_other_pt');
	}

	// Find topics
	$where='(t_pt_from='.strval($member_id).' OR t_pt_to='.strval($member_id).') AND t_forum_id IS NULL';
	$filter=get_param('category','');
	$where.=' AND ('.db_string_equal_to('t_pt_from_category',$filter).' AND t_pt_from='.strval($member_id).' OR '.db_string_equal_to('t_pt_to_category',$filter).' AND t_pt_to='.strval($member_id).')';
	$query='FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics top';
	if (!multi_lang_content())
	{
		$query.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p ON p.id=top.t_cache_first_post_id';
	}
	$query.=' WHERE '.$where;
	$max_rows=0;
	$union='';
	if ($filter==do_lang('INVITED_TO_PTS'))
	{
		global $SITE_INFO;
		if (!(((isset($SITE_INFO['mysql_old'])) && ($SITE_INFO['mysql_old']=='1')) || ((!isset($SITE_INFO['mysql_old'])) && (is_file(get_file_base().'/mysql_old')))))
		{
			$or_list='';
			$s_rows=$GLOBALS['FORUM_DB']->query_select('f_special_pt_access',array('s_topic_id'),array('s_member_id'=>get_member()));
			foreach ($s_rows as $s_row)
			{
				if ($or_list!='') $or_list.=' OR ';
				$or_list.='id='.strval($s_row['s_topic_id']);
			}
			if ($or_list!='')
			{
				$query2='FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE '.$or_list;
				$union=' UNION SELECT * '.$query2;
				$max_rows+=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query2);
			}
		}
	}
	$order=get_param('order','last_time');
	$order2='t_cache_last_time DESC';
	if ($order=='first_post') $order2='t_cache_first_time DESC';
	elseif ($order=='title') $order2='t_cache_first_title ASC';
	if (get_value('disable_sunk')!=='1')
		$order2='t_sunk ASC,'.$order2;
	$query_full='SELECT *';
	if (multi_lang_content())
	{
		$query_full.=',t_cache_first_post AS p_post';
	} else
	{
		$query_full.=',p_post,p_post__text_parsed,p_post__source_user';
	}
	$query_full.=' '.$query.$union.' ORDER BY t_pinned DESC,'.$order2;
	$topic_rows=$GLOBALS['FORUM_DB']->query($query_full,$max,$start,false,true);
	$max_rows+=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query);
	$topics=array();
	$hot_topic_definition=intval(get_option('hot_topic_definition'));
	foreach ($topic_rows as $topic_row)
	{
		$topic=array();
		$topic['id']=$topic_row['id'];
		$topic['num_views']=$topic_row['t_num_views'];
		$topic['num_posts']=$topic_row['t_cache_num_posts'];
		$topic['first_time']=$topic_row['t_cache_first_time'];
		$topic['first_title']=$topic_row['t_cache_first_title'];
		if (is_null($topic_row['p_post']))
		{
			$topic['first_post']=new ocp_tempcode();
		} else
		{
			$post_row=db_map_restrict($topic_row,array('p_post'))+array('id'=>$topic_row['p_cache_first_post_id']);
			$topic['first_post']=get_translated_tempcode('f_posts',$post_row,'p_post',$GLOBALS['FORUM_DB']);
		}
		$topic['first_post']->singular_bind('ATTACHMENT_DOWNLOADS',make_string_tempcode('?'));
		$topic['first_username']=$topic_row['t_cache_first_username'];
		$topic['first_member_id']=$topic_row['t_cache_first_member_id'];
		$topic['last_post_id']=$topic_row['t_cache_last_post_id'];
		$topic['last_time']=$topic_row['t_cache_last_time'];
		$topic['last_time_string']=is_null($topic_row['t_cache_last_time'])?'':get_timezoned_date($topic_row['t_cache_last_time']);
		$topic['last_title']=$topic_row['t_cache_last_title'];
		$topic['last_username']=$topic_row['t_cache_last_username'];
		$topic['last_member_id']=$topic_row['t_cache_last_member_id'];
		$topic['emoticon']=$topic_row['t_emoticon'];
		$topic['description']=$topic_row['t_description'];
		$topic['pt_from']=$topic_row['t_pt_from'];
		$topic['pt_to']=$topic_row['t_pt_to'];

		// Modifiers
		$topic['modifiers']=array();
		$has_read=ocf_has_read_topic($topic['id'],$topic_row['t_cache_last_time'],$member_id);
		if (!$has_read) $topic['modifiers'][]='unread';
		if ($topic_row['t_pinned']==1) $topic['modifiers'][]='pinned';
		if ($topic_row['t_sunk']==1) $topic['modifiers'][]='sunk';
		if ($topic_row['t_is_open']==0) $topic['modifiers'][]='closed';
		if (!is_null($topic_row['t_poll_id'])) $topic['modifiers'][]='poll';
		$num_posts=$topic_row['t_cache_num_posts'];
		$start_time=$topic_row['t_cache_first_time'];
		$end_time=$topic_row['t_cache_last_time'];
		$days=floatval($end_time-$start_time)/60.0/60.0/24.0;
		if ($days==0.0) $days=1.0;
		if (intval(round($num_posts/$days))>=$hot_topic_definition) $topic['modifiers'][]='hot';

		$topics[]=$topic;
	}

	$out=array('topics'=>$topics,'max_rows'=>$max_rows);

	if ((has_specific_permission($member_id,'moderate_personal_topic')) && (($member_id==get_member()) || (has_specific_permission($member_id,'multi_delete_topics'))))
	{
		$out['may_move_topics']=1;
		$out['may_delete_topics']=1;
		$out['may_change_max']=1;
	}
	if (ocf_may_make_private_topic()) $out['may_post_topic']=1;

	return $out;
}
