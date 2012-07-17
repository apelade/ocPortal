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

class Hook_Preview_ocf_post
{

	/**
	 * Find whether this preview hook applies.
	 *
	 * @return array			Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
	 */
	function applies()
	{
		$applies=((get_param('page','')=='topics') && (in_array(get_param('type'),array('birthday','edit_post','new_post','edit_topic','new_pt','new_topic','multimod')))) || (get_param('page','')=='topicview');
		return array($applies,'ocf_post',true);
	}

	/**
	 * Standard modular run function for preview hooks.
	 *
	 * @return array			A pair: The preview, the updated post Comcode
	 */
	function run()
	{
		require_lang('ocf');
		require_css('ocf');

		$original_comcode=post_param('post');
		require_code('ocf_posts_action');
		require_code('ocf_posts_action2');
		ocf_check_post($original_comcode,post_param_integer('topic_id',NULL),get_member());
		$posting_ref_id=post_param_integer('posting_ref_id',mt_rand(0,100000));
		if ($posting_ref_id<0) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
		$post_bits=do_comcode_attachments($original_comcode,'ocf_post',strval(-$posting_ref_id),true,$GLOBALS['FORUM_DB']);
		$post_comcode=$post_bits['comcode'];
		$post_html=$post_bits['tempcode'];

		// Put quote in
		$parent_id=post_param_integer('parent_id',NULL);
		if ((!is_null($parent_id)) && (strpos($post_comcode,'[quote')===false))
		{
			$_p=$GLOBALS['FORUM_DB']->query_select('f_posts',array('*'),array('id'=>$parent_id),'',1);
			if (array_key_exists(0,$_p))
			{
				$p=$_p[0];
				$p['message']=get_translated_tempcode($p['p_post'],$GLOBALS['FORUM_DB']);

				$temp=$post_html;
				$post_html=new ocp_tempcode();
				$post_html=do_template('COMCODE_QUOTE_BY',array('SAIDLESS'=>false,'BY'=>$p['p_poster_name_if_guest'],'CONTENT'=>$p['message']));
				$post_html->attach($temp);
			}
		}

		$post_owner=get_member();
		$_post_date=time();
		$post_id=post_param_integer('post_id',NULL);
		if (!is_null($post_id))
		{
			$post_owner=$GLOBALS['FORUM_DB']->query_value_null_ok('f_posts','p_poster',array('id'=>$post_id));
			if (is_null($post_owner)) $post_owner=get_member();

			$_post_date=$GLOBALS['FORUM_DB']->query_value_null_ok('f_posts','p_time',array('id'=>$post_id));
			if (is_null($_post_date)) $_post_date=time();
		}
		$post_date=get_timezoned_date($_post_date);

		$post_title=post_param('title','');
		if (strlen($post_title)>120)
		{
			warn_exit(do_lang_tempcode('TITLE_TOO_LONG'));
		}
		$unvalidated=((post_param_integer('validated',0)==0) && (get_param('page','')=='topics'))?do_lang_tempcode('UNVALIDATED'):new ocp_tempcode();
		$emphasis=new ocp_tempcode();
		$intended_solely_for=post_param('intended_solely_for',NULL);
		if ($intended_solely_for=='') $intended_solely_for=NULL;
		$is_emphasised=post_param_integer('is_emphasised',0)==1;
		if ($is_emphasised)
		{
			$emphasis=do_lang_tempcode('IMPORTANT');
		}
		elseif (!is_null($intended_solely_for))
		{
			if (is_numeric($intended_solely_for))
			{
				$_intended_solely_for=$GLOBALS['FORUM_DRIVER']->get_username(intval($intended_solely_for));
				if (!is_null($_intended_solely_for)) $intended_solely_for=$_intended_solely_for;
			}
			$emphasis=do_lang_tempcode('PP_TO',escape_html($intended_solely_for));

		}
		$class=$is_emphasised?'ocf_post_emphasis':(!is_null($intended_solely_for)?'ocf_post_personal':'');

		// Member details
		$signature=get_translated_tempcode($GLOBALS['FORUM_DRIVER']->get_member_row_field($post_owner,'m_signature'),$GLOBALS['FORUM_DB']);
		$_postdetails_avatar=$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($post_owner);
		if ($_postdetails_avatar!='')
		{
			$post_avatar=do_template('OCF_TOPIC_POST_AVATAR',array('_GUID'=>'2683c09eabd7a9f1fdc57a20117483ef','AVATAR'=>$_postdetails_avatar));
		} else
		{
			$post_avatar=new ocp_tempcode();
		}
		require_code('ocf_groups');
		require_code('ocf_members');
		$poster_title=addon_installed('ocf_member_titles')?$GLOBALS['FORUM_DRIVER']->get_member_row_field($post_owner,'m_title'):'';
		$primary_group=$GLOBALS['FORUM_DRIVER']->get_member_row_field($post_owner,'m_primary_group');
		if ($poster_title=='') $poster_title=get_translated_text(ocf_get_group_property($primary_group,'title'),$GLOBALS['FORUM_DB']);

		// Poster box
		if (!is_guest($post_owner))
		{
			require_code('ocf_members2');
			$poster_details=ocf_show_member_box($post_owner,false,NULL,NULL,false);
			$poster_username=$GLOBALS['FORUM_DRIVER']->get_username($post_owner);
			if (is_null($poster_username)) $poster_username=do_lang('UNKNOWN');
			$poster=do_template('OCF_POSTER_MEMBER',array('_GUID'=>'976a6ceb631bbdcdd950b723cb5d2487','ONLINE'=>true,'ID'=>strval($post_owner),'POSTER_DETAILS'=>$poster_details,'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($post_owner,false,true),'POSTER_USERNAME'=>$poster_username));
		} else
		{
			$poster_details=new ocp_tempcode();
			$custom_fields=do_template('OCF_TOPIC_POST_CUSTOM_FIELD',array('NAME'=>do_lang_tempcode('IP_ADDRESS'),'VALUE'=>(get_ip_address())));
			$poster_details=do_template('OCF_GUEST_DETAILS',array('_GUID'=>'2db48e17db9f060c04386843f2d0f105','CUSTOM_FIELDS'=>$custom_fields));
			$poster_username=post_param('poster_name_if_guest',do_lang('GUEST'));
			$ip_link=has_actual_page_access(get_member(),'admin_lookup')?build_url(array('page'=>'admin_lookup','param'=>get_ip_address()),get_module_zone('admin_lookup')):new ocp_tempcode();
			$poster=do_template('OCF_POSTER_GUEST',array('_GUID'=>'9c0ba6198663de96facc7399a08e8281','IP_LINK'=>$ip_link,'POSTER_DETAILS'=>$poster_details,'POSTER_USERNAME'=>$poster_username));
		}

		// Rank images
		$rank_images=new ocp_tempcode();
		$posters_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($post_owner,true);
		foreach ($posters_groups as $group)
		{
			$rank_image=ocf_get_group_property($group,'rank_image');
			$group_leader=ocf_get_group_property($group,'group_leader');
			$group_name=ocf_get_group_name($group);
			if ($rank_image!='') $rank_images->attach(do_template('OCF_RANK_IMAGE',array('_GUID'=>'a6a413fc07e05b28ab995b072718b755','GROUP_NAME'=>$group_name,'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username(get_member()),'IMG'=>$rank_image,'IS_LEADER'=>$group_leader==get_member())));
		}

		if (get_param('type')=='edit_post')
		{
			$last_edited=do_template('OCF_TOPIC_POST_LAST_EDITED',array('LAST_EDIT_DATE_RAW'=>strval(time()),'LAST_EDIT_DATE'=>get_timezoned_date(time(),true),'LAST_EDIT_PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url(get_member(),false,true),'LAST_EDIT_USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username(get_member())));
		} else $last_edited=new ocp_tempcode();

		$post=do_template('OCF_TOPIC_POST',array('_GUID'=>'354473f96b4f7324d2a9c476ff78f0d7','POST_ID'=>'','TOPIC_FIRST_POST_ID'=>'','TOPIC_FIRST_POSTER'=>strval(get_member()),'POST_TITLE'=>$post_title,'CLASS'=>$class,'EMPHASIS'=>$emphasis,'FIRST_UNREAD'=>'','TOPIC_ID'=>'','ID'=>'','POST_DATE_RAW'=>strval($_post_date),'POST_DATE'=>$post_date,'UNVALIDATED'=>$unvalidated,'URL'=>'','POSTER'=>$poster,'POST_AVATAR'=>$post_avatar,'POSTER_TITLE'=>$poster_title,'RANK_IMAGES'=>$rank_images,'POST'=>$post_html,'LAST_EDITED'=>$last_edited,'SIGNATURE'=>$signature,'BUTTONS'=>'','POSTER_ID'=>strval($post_owner)));
		$out=do_template('OCF_TOPIC_POST_CLEAN_WRAP',array('_GUID'=>'62bbfabfa5c16c2aa6724a0b79839626','POST'=>$post));

		return array($out,$post_comcode);
	}

}


