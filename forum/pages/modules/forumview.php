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
 * @package		ocf_forum
 */

/**
 * Module page class.
 */
class Module_forumview
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
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true)
	{
		return array('!'=>'ROOT_FORUM');
	}

	var $title;
	var $id;
	var $forum_info;
	var $breadcrumbs;
	var $of_member_id;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		inform_non_canonical_parameter('#^kfs_.*$#');

		if ($type=='misc')
		{
			$id=get_param_integer('id',db_get_first_id());

			$_forum_info=$GLOBALS['FORUM_DB']->query_select('f_forums f',array('f_redirection','f_intro_question','f_intro_answer','f_order_sub_alpha','f_parent_forum','f_name','f_description','f_order'),array('f.id'=>$id),'',1,NULL,false,array('f_description','f_intro_question'));
			if (!array_key_exists(0,$_forum_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
			$forum_info=$_forum_info[0];

			$description_text=get_translated_text($forum_info['f_description'],$GLOBALS['FORUM_DB']);

			set_extra_request_metadata(array(
				'created'=>'',
				'creator'=>'',
				'publisher'=>'', // blank means same as creator
				'modified'=>'',
				'type'=>'Forum',
				'title'=>$forum_info['f_name'],
				'identifier'=>'_SEARCH:forumview:misc:'.strval($id),
				'description'=>$description_text,
				'image'=>find_theme_image('icons/48x48/menu/social/forum/forums'),
				//'category'=>???,
			));

			if ((get_value('no_awards_in_titles')!=='1') && (addon_installed('awards')))
			{
				require_code('awards');
				$awards=is_null($id)?array():find_awards_for('forum',strval($id));
			} else $awards=array();

			$forum_name=$forum_info['f_name'];
			$ltitle=do_lang_tempcode('NAMED_FORUM',make_fractionable_editable('forum',$id,$forum_name));

			$this->title=get_screen_title($ltitle,false,NULL,NULL,$awards);

			if (($forum_info['f_redirection']!='') && (looks_like_url($forum_info['f_redirection'])))
			{
				require_code('site2');
				smart_redirect($forum_info['f_redirection']);
			}

			set_short_title($forum_name);

			set_feed_url('?mode=ocf_forumview&filter='.strval($id));

			require_code('ocf_forums');
			$breadcrumbs=ocf_forum_breadcrumbs($id,$forum_name,$forum_info['f_parent_forum']);
			breadcrumb_add_segment($breadcrumbs);
			$this->breadcrumbs=$breadcrumbs;

			$this->id=$id;
			$this->forum_info=$forum_info;
		}

		if ($type=='pts')
		{
			$this->title=get_screen_title('PRIVATE_TOPICS');

			$root=get_param_integer('keep_forum_root',db_get_first_id());
			$root_forum_name=$GLOBALS['FORUM_DB']->query_select_value('f_forums','f_name',array('id'=>$root));
			$breadcrumbs=hyperlink(build_url(array('page'=>'_SELF','id'=>($root==db_get_first_id())?NULL:$root),'_SELF'),escape_html($root_forum_name),false,false,do_lang_tempcode('GO_BACKWARDS_TO',$root_forum_name),NULL,NULL,'up');
			$breadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR'));
			$of_member_id=get_param_integer('id',get_member());
			$pt_username=$GLOBALS['FORUM_DRIVER']->get_username($of_member_id);
			$pt_displayname=$GLOBALS['FORUM_DRIVER']->get_username($of_member_id,true);
			if (is_null($pt_username)) $pt_username=do_lang('UNKNOWN');
			$breadcrumbs->attach(do_lang_tempcode('PRIVATE_TOPICS_OF',escape_html($pt_displayname),escape_html($pt_username)));
			$this->breadcrumbs=$breadcrumbs;
			$this->of_member_id=$of_member_id;
		}

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
		require_code('ocf_forumview');

		$type=get_param('type','misc');

		$current_filter_cat=get_param('category','');

		$default_max=intval(get_option('forum_topics_per_page'));
		$max=get_param_integer('forum_max',$default_max);
		if (($max>50) && (!has_privilege(get_member(),'remove_page_split'))) $max=$default_max;

		$root=get_param_integer('keep_forum_root',db_get_first_id());

		if ($type=='pt') // Not used anymore by default, but code still here
		{
			$id=NULL;
			$forum_info=array();
			$start=get_param_integer('forum_start',get_param_integer('kfs',0));
			$of_member_id=$this->of_member_id;
		} else
		{
			$id=$this->id;
			$forum_info=$this->forum_info;
			$start=get_param_integer('forum_start',get_param_integer('kfs'.strval($id),0));
			$of_member_id=NULL;
		}

		// Don't allow guest bots to probe too deep into the forum index, it gets very slow; the XML Sitemap is for guiding to topics like this
		if (($start>$max*5) && (is_guest()) && (!is_null(get_bot_type())))
			access_denied('NOT_AS_GUEST');

		require_code('ocf_general');
		ocf_set_context_forum($id);

		$test=ocf_render_forumview($id,$forum_info,$current_filter_cat,$max,$start,$root,$of_member_id,$this->breadcrumbs);
		if (is_array($test))
		{
			list($content,$forum_name)=$test;
		} else
		{
			return $test;
		}

		// Members viewing this forum
		require_code('users2');
		list($num_guests,$num_members,$members_viewing)=get_members_viewing_wrap('forumview','',strval($id),true);

		$tpl=do_template('OCF_FORUM_SCREEN',array(
			'_GUID'=>'9e9fd9110effd8a92b7a839a4fea60c5',
			'TITLE'=>$this->title,
			'CONTENT'=>$content,
			'ID'=>strval($id),
			'NUM_GUESTS'=>integer_format($num_guests),
			'NUM_MEMBERS'=>integer_format($num_members),
			'MEMBERS_VIEWING'=>$members_viewing,
		));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

}


