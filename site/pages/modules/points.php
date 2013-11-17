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
 * @package		points
 */

/**
 * Module page class.
 */
class Module_points
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
		$info['version']=7;
		$info['locked']=true;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('chargelog');
		$GLOBALS['SITE_DB']->drop_table_if_exists('gifts');

		delete_privilege('give_points_self');
		delete_privilege('have_negative_gift_points');
		delete_privilege('give_negative_points');
		delete_privilege('view_charge_log');
		delete_privilege('use_points');
		delete_privilege('trace_anonymous_gifts');

		$GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_used');
		$GLOBALS['FORUM_DRIVER']->install_delete_custom_field('gift_points_used');
		$GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_gained_given');
		$GLOBALS['FORUM_DRIVER']->install_delete_custom_field('points_gained_rating');
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
			add_privilege('POINTS','use_points',true);

			$GLOBALS['SITE_DB']->create_table('chargelog',array(
				'id'=>'*AUTO',
				'member_id'=>'MEMBER',
				'amount'=>'INTEGER',
				'reason'=>'SHORT_TRANS',	// Comcode
				'date_and_time'=>'TIME'
			));

			$GLOBALS['SITE_DB']->create_table('gifts',array(
				'id'=>'*AUTO',
				'date_and_time'=>'TIME',
				'amount'=>'INTEGER',
				'gift_from'=>'MEMBER',
				'gift_to'=>'MEMBER',
				'reason'=>'SHORT_TRANS',	// Comcode
				'anonymous'=>'BINARY'
			));
			$GLOBALS['SITE_DB']->create_index('gifts','giftsgiven',array('gift_from'));
			$GLOBALS['SITE_DB']->create_index('gifts','giftsreceived',array('gift_to'));

			add_privilege('POINTS','trace_anonymous_gifts',false);
			add_privilege('POINTS','give_points_self',false);
			add_privilege('POINTS','have_negative_gift_points',false);
			add_privilege('POINTS','give_negative_points',false);
			add_privilege('POINTS','view_charge_log',false);

			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_used',20,1,0,0,0,'','integer');
			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('gift_points_used',20,1,0,0,0,'','integer');
			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_gained_given',20,1,0,0,0,'','integer');
			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('points_gained_rating',20,1,0,0,0,'','integer');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<8))
		{
			$GLOBALS['SITE_DB']->alter_table_field('chargelog','user_id','MEMBER','member_id');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<8))
		{
			rename_config_option('leaderboard_start_date','leader_board_start_date');
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		if (get_forum_type()=='ocf') return array();
		$ret=array(
			'misc'=>array('MEMBER_POINT_FIND','buttons/search'),
		);
		if (!$check_perms || !is_guest($member_id))
			$ret['member']=array('POINTS','menu/social/points');
		return $ret;
	}

	var $title;
	var $member_id_of;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('points');

		if ($type=='misc' || $type=='_search')
		{
			set_feed_url('?mode=points&filter=');
		}

		if ($type=='misc')
		{
			$this->member_id_of=db_get_first_id()+1;
			set_feed_url('?mode=points&filter='.strval($this->member_id_of));

			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MEMBER_POINT_FIND'))));

			$this->title=get_screen_title('MEMBER_POINT_FIND');
		}

		if ($type=='_search')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MEMBER_POINT_FIND'))));

			$this->title=get_screen_title('MEMBER_POINT_FIND');
		}

		if ($type=='give')
		{
			$member_id_of=get_param_integer('id');

			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MEMBER_POINT_FIND')),array('_SELF:_SELF:member:'.strval($member_id_of),do_lang_tempcode('_POINTS',escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id_of,true))))));

			$this->title=get_screen_title('POINTS');
		}

		if ($type=='member')
		{
			$this->member_id_of=get_param_integer('id',get_member());
			set_feed_url('?mode=points&filter='.strval($this->member_id_of));

			$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id_of,true);
			if ((is_null($username)) || (is_guest($member_id_of))) warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
			$this->title=get_screen_title('_POINTS',true,array(escape_html($username)));
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
		require_code('points');
		require_css('points');

		// Work out what we're doing here
		$type=get_param('type','misc');

		if ($type=='misc') return $this->points_search_form();
		if ($type=='_search') return $this->points_search_results();
		if ($type=='give') return $this->do_give();
		if ($type=='member') return $this->points_profile();

		return new ocp_tempcode();
	}

	/**
	 * The UI to search for a member (with regard to viewing their point profile).
	 *
	 * @return tempcode		The UI
	 */
	function points_search_form()
	{
		$post_url=build_url(array('page'=>'_SELF','type'=>'_search'),'_SELF',NULL,false,true);
		require_code('form_templates');
		if (!is_guest())
		{
			$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		} else
		{
			$username='';
		}
		$fields=form_input_username(do_lang_tempcode('USERNAME'),'','username',$username,true,false);
		$submit_name=do_lang_tempcode('SEARCH');
		$text=new ocp_tempcode();
		$text->attach(paragraph(do_lang_tempcode('POINTS_SEARCH_FORM')));
		$text->attach(paragraph(do_lang_tempcode('WILDCARD')));

		return do_template('FORM_SCREEN',array('_GUID'=>'e5ab8d5d599093d1a550cb3b3e56d2bf','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','TITLE'=>$this->title,'URL'=>$post_url,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name,'TEXT'=>$text));
	}

	/**
	 * The actualiser for a points profile search.
	 *
	 * @return tempcode		The UI
	 */
	function points_search_results()
	{
		$username=str_replace('*','%',get_param('username'));
		if ((substr($username,0,1)=='%') && ($GLOBALS['FORUM_DRIVER']->get_members()>3000))
			warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
		if ((strpos($username,'%')!==false) && (strpos($username,'%')<6) && ($GLOBALS['FORUM_DRIVER']->get_members()>30000))
			warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
		if ((strpos($username,'%')!==false) && (strpos($username,'%')<12) && ($GLOBALS['FORUM_DRIVER']->get_members()>300000))
			warn_exit(do_lang_tempcode('CANNOT_WILDCARD_START'));
		$rows=$GLOBALS['FORUM_DRIVER']->get_matching_members($username,100);
		if (!array_key_exists(0,$rows))
		{
			return warn_screen($this->title,do_lang_tempcode('NO_RESULTS'));
		}

		$results=new ocp_tempcode();
		foreach ($rows as $myrow)
		{
			$id=$GLOBALS['FORUM_DRIVER']->mrow_id($myrow);
			if (!is_guest($id))
			{
				$url=build_url(array('page'=>'_SELF','type'=>'member','id'=>$id),'_SELF');
				$username=$GLOBALS['FORUM_DRIVER']->mrow_username($myrow);

				$results->attach(do_template('POINTS_SEARCH_RESULT',array('_GUID'=>'df240255b2981dcaee38e126622be388','URL'=>$url,'ID'=>strval($id),'USERNAME'=>$username)));
			}
		}

		return do_template('POINTS_SEARCH_SCREEN',array('_GUID'=>'659af8a012d459db09dad0325a75ac70','TITLE'=>$this->title,'RESULTS'=>$results));
	}

	/**
	 * The UI for a points profile.
	 *
	 * @return tempcode		The UI
	 */
	function points_profile()
	{
		$member_id_of=$this->member_id_of;

		if (get_forum_type()=='ocf')
		{
			$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id_of,true,true);
			if (is_object($url)) $url=$url->evaluate();
			return redirect_screen($this->title,$url.'#tab__points','');
		}

		require_code('points3');
		$content=points_profile($member_id_of,get_member());

		return do_template('POINTS_SCREEN',array('_GUID'=>'7fadfc2886ba063008f6333fb3f19e75','TITLE'=>$this->title,'CONTENT'=>$content));
	}

	/**
	 * The actualiser for a gift point transaction.
	 *
	 * @return tempcode		The UI
	 */
	function do_give()
	{
		$member_id_of=get_param_integer('id');

		$trans_type=post_param('trans_type','gift');

		$amount=post_param_integer('amount');
		$reason=post_param('reason');

		$worked=false;

		$member_id_viewing=get_member();
		if (($member_id_of==$member_id_viewing) && (!has_privilege($member_id_viewing,'give_points_self'))) // No cheating
		{
			$message=do_lang_tempcode('PE_SELF');
		}
		elseif (is_guest($member_id_viewing)) // No cheating
		{
			$message=do_lang_tempcode('MUST_LOGIN');
		} else
		{
			if ($trans_type=='gift')
			{
				$anonymous=post_param_integer('anonymous',0);
				$viewer_gift_points_available=get_gift_points_to_give($member_id_viewing);
				//$viewer_gift_points_used=get_gift_points_used($member_id_viewing);

				if (($viewer_gift_points_available<$amount) && (!has_privilege($member_id_viewing,'have_negative_gift_points'))) // Validate we have enough for this, and add to usage
				{
					$message=do_lang_tempcode('PE_LACKING_GIFT_POINTS');
				}
				elseif (($amount<0) && (!has_privilege($member_id_viewing,'give_negative_points'))) // Trying to be negative
				{
					$message=do_lang_tempcode('PE_NEGATIVE_GIFT');
				}
				elseif ($reason=='') // Must give a reason
				{
					$message=do_lang_tempcode('IMPROPERLY_FILLED_IN');
				} else
				{
					// Write transfer
					require_code('points2');
					give_points($amount,$member_id_of,$member_id_viewing,$reason,$anonymous==1);

					// Randomised gifts
					$gift_reward_chance=intval(get_option('gift_reward_chance'));
					if (mt_rand(0,100)<$gift_reward_chance)
					{
						$gift_reward_amount=intval(get_option('gift_reward_amount'));

						$message=do_lang_tempcode('PR_LUCKY');
						$_current_gift=point_info($member_id_viewing);
						$current_gift=array_key_exists('points_gained_given',$_current_gift)?$_current_gift['points_gained_given']:0;
						$GLOBALS['FORUM_DRIVER']->set_custom_field($member_id_viewing,'points_gained_given',$current_gift+$gift_reward_amount);
					} else $message=do_lang_tempcode('PR_NORMAL');

					$worked=true;
				}
			}

			if ($trans_type=='refund')
			{
				$trans_type='charge';
				$amount=-$amount;
			}
			if ($trans_type=='charge')
			{
				if (has_actual_page_access($member_id_viewing,'adminzone'))
				{
					require_code('points2');
					charge_member($member_id_of,$amount,$reason);
					$left=available_points($member_id_of);

					$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id_of);
					if (is_null($username)) $username=do_lang('UNKNOWN');
					$message=do_lang_tempcode('MEMBER_HAS_BEEN_CHARGED',escape_html($username),escape_html(integer_format($amount)),escape_html(integer_format($left)));

					$worked=true;
				} else
				{
					access_denied('I_ERROR');
				}
			}
		}

		if ($worked)
		{
			// Show it worked / Refresh
			$url=build_url(array('page'=>'_SELF','type'=>'member','id'=>$member_id_of),'_SELF');
			return redirect_screen($this->title,$url,$message);
		}
		return warn_screen($this->title,$message);
	}
}


