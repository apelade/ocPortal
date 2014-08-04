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
 * Add a topic.
 *
 * @param  ?AUTO_LINK	The ID of the forum the topic will be in (NULL: Private Topic).
 * @param  SHORT_TEXT	Description of the topic.
 * @param  SHORT_TEXT	The theme image code of the emoticon for the topic.
 * @param  ?BINARY		Whether the topic is validated (NULL: detect whether it should be).
 * @param  BINARY			Whether the topic is open.
 * @param  BINARY			Whether the topic is pinned.
 * @param  BINARY			Whether the topic is sunk.
 * @param  BINARY			Whether the topic is cascading.
 * @param  ?MEMBER		If it is a Private Topic, who is it 'from' (NULL: not a Private Topic).
 * @param  ?MEMBER		If it is a Private Topic, who is it 'to' (NULL: not a Private Topic).
 * @param  boolean		Whether to check the poster has permissions for the given topic settings.
 * @param  integer		The number of times the topic has been viewed.
 * @param  ?AUTO_LINK	Force an ID (NULL: don't force an ID)
 * @param  SHORT_TEXT	Link related to the topic (e.g. link to view a ticket).
 * @return AUTO_LINK		The ID of the newly created topic.
 */
function ocf_make_topic($forum_id,$description='',$emoticon='',$validated=NULL,$open=1,$pinned=0,$sunk=0,$cascading=0,$pt_from=NULL,$pt_to=NULL,$check_perms=true,$num_views=0,$id=NULL,$description_link='')
{
	if (is_null($pinned)) $pinned=0;
	if (is_null($sunk)) $sunk=0;
	if (is_null($description)) $description='';
	if (is_null($num_views)) $num_views=0;

	if ($check_perms)
	{
		require_code('ocf_topics');
		if (!ocf_may_post_topic($forum_id,get_member()))
		{
			access_denied('I_ERROR');
		}

		if (!is_null($pt_to))
		{
			decache(array(
				array('side_ocf_personal_topics',array($pt_to)),
				array('_new_pp',array($pt_to)),
			));
		}

		if (!is_null($forum_id))
		{
			require_code('ocf_posts_action');
			ocf_decache_ocp_blocks($forum_id);
		}

		require_code('ocf_forums');
		if (!ocf_may_moderate_forum($forum_id))
		{
			$pinned=0;
			$sunk=0;
			$open=1;
			$cascading=0;
		}
	}

	if ((is_null($validated)) || (($check_perms) && ($validated==1)))
	{
		if ((!is_null($forum_id)) && (!has_privilege(get_member(),'bypass_validation_midrange_content','topics',array('forums',$forum_id)))) $validated=0; else $validated=1;
	}

	if (!addon_installed('unvalidated')) $validated=1;
	$map=array(
		't_pinned'=>$pinned,
		't_sunk'=>$sunk,
		't_cascading'=>$cascading,
		't_forum_id'=>$forum_id,
		't_pt_from'=>$pt_from,
		't_pt_to'=>$pt_to,
		't_description'=>$description,
		't_description_link'=>substr($description_link,0,255),
		't_emoticon'=>$emoticon,
		't_num_views'=>$num_views,
		't_validated'=>$validated,
		't_is_open'=>$open,
		't_poll_id'=>NULL,
		't_cache_first_post_id'=>NULL,
		't_cache_first_post'=>NULL,
		't_cache_first_time'=>NULL,
		't_cache_first_title'=>'',
		't_cache_first_username'=>'',
		't_cache_first_member_id'=>NULL,
		't_cache_last_post_id'=>NULL,
		't_cache_last_time'=>NULL,
		't_cache_last_title'=>'',
		't_cache_last_username'=>'',
		't_cache_last_member_id'=>NULL,
		't_cache_num_posts'=>0,
		't_pt_from_category'=>'',
		't_pt_to_category'=>''
	);
	if (!is_null($id)) $map['id']=$id;

	$topic_id=$GLOBALS['FORUM_DB']->query_insert('f_topics',$map,true);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('topic',strval($topic_id),NULL,NULL,true);
	}

	require_code('member_mentions');
	dispatch_member_mention_notifications('topic',strval($topic_id));

	return $topic_id;
}

