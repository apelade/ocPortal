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
class Module_join
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
		return array('misc'=>'_JOIN');
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

		$this->title=get_screen_title('__JOIN',true,array(escape_html(get_site_name())));

		if ($type=='misc')
		{
			breadcrumb_set_self(do_lang_tempcode('_JOIN'));
		}

		if ($type=='step2')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('_JOIN'))));
			breadcrumb_set_self(do_lang_tempcode('DETAILS'));
		}

		if ($type=='step3')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('_JOIN'))));
			breadcrumb_set_self(do_lang_tempcode('DONE'));
		}

		if ($type=='step4')
		{
			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('_JOIN'))));
			breadcrumb_set_self(do_lang_tempcode('DONE'));
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
		require_code('ocf_join');

		ocf_require_all_forum_stuff();

		$type=get_param('type','misc');

		if ($type=='misc')
		{
			check_joining_allowed();
			return (get_option('show_first_join_page')!='1')?$this->step2():$this->step1();
		}
		if ($type=='step2') return $this->step2();
		if ($type=='step3') return $this->step3();
		if ($type=='step4') return $this->step4();

		return new ocp_tempcode();
	}

	/**
	 * The UI to accept the rules of joining.
	 *
	 * @return tempcode		The UI
	 */
	function step1()
	{
		if (!is_guest()) warn_exit(do_lang_tempcode('NO_JOIN_LOGGED_IN'));

		// Show rules
		$rules=request_page('_rules',true,get_comcode_zone('rules'),NULL,true);
		$map=array('page'=>'_SELF','type'=>'step2');
		$email_address=trim(get_param('email_address',''));
		if ($email_address!='') $map['email_address']=$email_address;
		$redirect=get_param('redirect','');
		if ($redirect!='') $map['redirect']=$redirect;
		$url=build_url($map,'_SELF');

		$group_select=new ocp_tempcode();
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name','g_is_default'),array('g_is_presented_at_install'=>1),'ORDER BY g_order');
		if (count($rows)>1)
		{
			foreach ($rows as $group)
			{
				if (get_param_integer('usergroup',-1)==-1)
				{
					$selected=$group['g_is_default']==1;
				} else
				{
					$selected=$group['id']==get_param_integer('usergroup');
				}
				$group_select->attach(form_input_list_entry(strval($group['id']),$selected,get_translated_text($group['g_name'])));
			}
		}

		return do_template('OCF_JOIN_STEP1_SCREEN',array('_GUID'=>'3776e89f3b18e4bd9dd532defe6b1e9e','TITLE'=>$this->title,'RULES'=>$rules,'URL'=>$url,'GROUP_SELECT'=>$group_select));
	}

	/**
	 * The UI to enter profile details.
	 *
	 * @return tempcode		The UI
	 */
	function step2()
	{
		if (!is_guest()) warn_exit(do_lang_tempcode('NO_JOIN_LOGGED_IN'));

		if ((get_option('show_first_join_page')=='1') && (post_param_integer('confirm',0)!=1))
			warn_exit(do_lang_tempcode('DESCRIPTION_I_AGREE_RULES'));

		$map=array('page'=>'_SELF','type'=>'step3');
		$redirect=get_param('redirect','');
		if ($redirect!='') $map['redirect']=$redirect;
		$url=build_url($map,'_SELF');

		list($javascript,$form)=ocf_join_form($url);

		return do_template('OCF_JOIN_STEP2_SCREEN',array('_GUID'=>'5879db5cf331526a999371f76868233d','JAVASCRIPT'=>$javascript,'TITLE'=>$this->title,'FORM'=>$form));
	}

	/**
	 * The actualiser for adding a member.
	 *
	 * @return tempcode		The UI
	 */
	function step3()
	{
		if ((get_option('show_first_join_page')=='1') && (post_param_integer('confirm',0)!=1))
			warn_exit(do_lang_tempcode('DESCRIPTION_I_AGREE_RULES'));

		// Check e-mail domain, if applicable
		$email_address=trim(post_param('email_address'));
		if ($email_address!='')
		{
			$valid_email_domains=get_option('valid_email_domains');
			if ($valid_email_domains!='')
			{
				$domains=explode(',',$valid_email_domains);
				$ok=false;
				foreach ($domains as $domain)
				{
					if (substr($email_address,-strlen('@'.$domain))=='@'.$domain)
						$ok=true;
				}
				if (!$ok)
				{
					warn_exit(do_lang_tempcode('_MUST_BE_EMAIL_DOMAIN',escape_html($valid_email_domains)));
				}
			}
		}

		list($message)=ocf_join_actual();

		return inform_screen($this->title,$message);
	}

	/**
	 * The actualiser for setting up account confirmation.
	 *
	 * @return tempcode		The UI
	 */
	function step4()
	{
		// Check confirm code correct
		$_code=get_param('code','-1'); // -1 allowed because people often seem to mess the e-mail link up
		$code=intval($_code);
		if ($code<=0)
		{
			require_code('form_templates');
			$fields=new ocp_tempcode();
			$fields->attach(form_input_email(do_lang_tempcode('EMAIL_ADDRESS'),'','email','',true));
			$fields->attach(form_input_integer(do_lang_tempcode('CODE'),'','code',NULL,true));
			$submit_name=do_lang_tempcode('PROCEED');
			return do_template('FORM_SCREEN',array(
				'_GUID'=>'e2c8c3762a308ac7489ec3fb32cc0cf8',
				'TITLE'=>$this->title,
				'GET'=>true,
				'SKIP_VALIDATION'=>true,
				'HIDDEN'=>'',
				'URL'=>get_self_url(false,false,NULL,false,true),
				'FIELDS'=>$fields,
				'TEXT'=>do_lang_tempcode('MISSING_CONFIRM_CODE'),
				'SUBMIT_NAME'=>$submit_name,
			));
		}
		$rows=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_validated'),array('m_validated_email_confirm_code'=>strval($code),'m_email_address'=>trim(get_param('email'))));
		if (!array_key_exists(0,$rows))
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_validated'),array('m_validated_email_confirm_code'=>'','m_email_address'=>trim(get_param('email'))));
			if (!array_key_exists(0,$rows))
			{
				warn_exit(do_lang_tempcode('INCORRECT_CONFIRM_CODE'));
			} else
			{
				$redirect=get_param('redirect','');
				$map=array('page'=>'login','type'=>'misc');
				if ($redirect!='') $map['redirect']=$redirect;
				$url=build_url($map,get_module_zone('login'));
				return redirect_screen($this->title,$url,do_lang_tempcode('ALREADY_CONFIRMED_THIS'));
			}
		}
		$id=$rows[0]['id'];
		$validated=$rows[0]['m_validated'];

		// Activate user
		$GLOBALS['FORUM_DB']->query_update('f_members',array('m_validated_email_confirm_code'=>''),array('id'=>$id),'',1);

		if ($validated==0)
		{
			return inform_screen($this->title,do_lang_tempcode('AWAITING_MEMBER_VALIDATION'));
		}

		// Alert user to situation
		$redirect=get_param('redirect','');
		$map=array('page'=>'login','type'=>'misc');
		if ($redirect!='') $map['redirect']=$redirect;
		$url=build_url($map,get_module_zone('login'));
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESSFUL_CONFIRM'));
	}

}

