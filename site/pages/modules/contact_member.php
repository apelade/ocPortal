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
 * @package		ocf_contact_member
 */

/**
 * Module page class.
 */
class Module_contact_member
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

	var $title;
	var $member_id;
	var $username;
	var $to_name;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		if ($type=='misc')
		{
			attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

			$member_id=get_param_integer('id');
			$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id,true);
			if (is_null($username)) warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));

			$this->title=get_screen_title('EMAIL_MEMBER',true,array(escape_html($username)));

			$this->member_id=$member_id;
			$this->username=$username;
		}

		if ($type=='actual')
		{
			$member_id=get_param_integer('id');
			$to_name=$GLOBALS['FORUM_DRIVER']->get_username($member_id,true);

			breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('EMAIL_MEMBER',escape_html($to_name)))));
			breadcrumb_set_self(do_lang_tempcode('DONE'));

			$this->title=get_screen_title('EMAIL_MEMBER',true,array(escape_html($GLOBALS['FORUM_DRIVER']->get_username($member_id,true))));

			$this->member_id=$member_id;
			$this->to_name=$to_name;
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
		require_lang('mail');
		require_lang('comcode');

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();

		$type=get_param('type','misc');

		$member_id=get_param_integer('id');
		if (($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_email_address')=='') || ((get_option('allow_email_disable')=='1') && ($GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_allow_emails')==0)) || (is_guest($member_id)))
			warn_exit(do_lang_tempcode('NO_ACCEPT_EMAILS'));

		if ($type=='misc') return $this->gui();
		if ($type=='actual') return $this->actual();

		return new ocp_tempcode();
	}

	/**
	 * The UI to contact a member.
	 *
	 * @return tempcode		The UI
	 */
	function gui()
	{
		$member_id=$this->member_id;
		$username=$this->username;

		$text=do_lang_tempcode('EMAIL_MEMBER_TEXT');

		$fields=new ocp_tempcode();
		require_code('form_templates');
		$default_email=(is_guest())?'':$GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(),'m_email_address');
		$default_name=(is_guest())?'':$GLOBALS['FORUM_DRIVER']->get_member_row_field(get_member(),'m_username');
		$name_field=form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('_DESCRIPTION_NAME'),'name',$default_name,true);
		if ($default_name=='')
			$fields->attach($name_field);
		$email_field=form_input_email(do_lang_tempcode('EMAIL_ADDRESS'),do_lang_tempcode('YOUR_ADDRESS'),'email_address',$default_email,true);
		if ($default_email=='')
			$fields->attach($email_field);
		$fields->attach(form_input_line(do_lang_tempcode('SUBJECT'),'','subject',get_param('subject',''),true));
		$fields->attach(form_input_text(do_lang_tempcode('MESSAGE'),'','message',get_param('message',''),true));
		if (addon_installed('captcha'))
		{
			require_code('captcha');
			if (use_captcha())
			{
				$fields->attach(form_input_captcha());
				$text->attach(' ');
				$text->attach(do_lang_tempcode('FORM_TIME_SECURITY'));
			}
		}
		$size=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_max_email_attach_size_mb');
		$hidden=new ocp_tempcode();
		if ($size!=0)
		{
			handle_max_file_size($hidden);
			$fields->attach(form_input_upload_multi(do_lang_tempcode('_ATTACHMENT'),do_lang_tempcode('EMAIL_ATTACHMENTS',integer_format($size)),'attachment',false));
		}
		if (!is_guest())
		{
			if (ini_get('suhosin.mail.protect')!='2')
			{
				$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'7f7e5aa2fa469ebbca9ca61e9f869882','TITLE'=>do_lang_tempcode('ADVANCED'),'SECTION_HIDDEN'=>true)));
				if ($default_name!='')
					$fields->attach($name_field);
				if ($default_email!='')
					$fields->attach($email_field);
				$fields->attach(form_input_username_multi(do_lang_tempcode('EMAIL_CC_ADDRESS'),do_lang_tempcode('DESCRIPTION_EMAIL_CC_ADDRESS'),'cc_',array(),0,false));
				$fields->attach(form_input_username_multi(do_lang_tempcode('EMAIL_BCC_ADDRESS'),do_lang_tempcode('DESCRIPTION_EMAIL_BCC_ADDRESS'),'bcc_',array(),0,false));
			}
		}
		$submit_name=do_lang_tempcode('SEND');
		$redirect=get_param('redirect','');
		if ($redirect=='')
		{
			$redirect=$GLOBALS['FORUM_DRIVER']->member_profile_url($member_id,false,true);
			if (is_object($redirect)) $redirect=$redirect->evaluate();
		}
		$post_url=build_url(array('page'=>'_SELF','type'=>'actual','id'=>$member_id,'redirect'=>$redirect),'_SELF');

		return do_template('FORM_SCREEN',array(
			'_GUID'=>'e06557e6eceacf1f46ee930c99ac5bb5',
			'TITLE'=>$this->title,
			'HIDDEN'=>$hidden,
			'JAVASCRIPT'=>function_exists('captcha_ajax_check')?captcha_ajax_check():'',
			'FIELDS'=>$fields,
			'TEXT'=>$text,
			'SUBMIT_NAME'=>$submit_name,
			'URL'=>$post_url,
			'SUPPORT_AUTOSAVE'=>true,
		));
	}

	/**
	 * The actualiser to contact a member.
	 *
	 * @return tempcode		The UI
	 */
	function actual()
	{
		if (addon_installed('captcha'))
		{
			require_code('captcha');
			enforce_captcha();
		}

		$member_id=$this->member_id;
		$to_name=$this->to_name;

		$email_address=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_email_address');
		if (is_null($email_address)) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));

		if (is_null($to_name)) warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));

		$from_email=trim(post_param('email_address'));
		require_code('type_validation');
		if (!is_valid_email_address($from_email)) warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
		$from_name=post_param('name');

		$extra_cc_addresses=array();
		$extra_bcc_addresses=array();
		if (!is_guest())
		{
			foreach ($_POST as $key=>$val)
			{
				if (($val!='') && ((substr($key,0,3)=='cc_') || (substr($key,0,4)=='bcc_')))
				{
					$address=post_param($key);
					if (!is_valid_email_address($address))
					{
						$address=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_members','m_email_address',array('m_username'=>$address));
						if (is_null($address))
							warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
						if (!is_valid_email_address($address))
							warn_exit(do_lang_tempcode('INVALID_EMAIL_ADDRESS'));
					}
					if (substr($key,0,3)=='cc_')
					{
						$extra_cc_addresses[]=$address;
					}
					if (substr($key,0,4)=='bcc_')
					{
						$extra_bcc_addresses[]=$address;
					}
				}
			}
		}

		require_code('mail');
		$attachments=array();
		$size_so_far=0;
		require_code('uploads');
		is_swf_upload(true);
		foreach ($_FILES as $file)
		{
			if ((is_swf_upload()) || (is_uploaded_file($file['tmp_name'])))
			{
				$attachments[$file['tmp_name']]=$file['name'];
				$size_so_far+=$file['size'];
			} else
			{
				if ((defined('UPLOAD_ERR_NO_FILE')) && (array_key_exists('error',$file)) && ($file['error']!=UPLOAD_ERR_NO_FILE))
					warn_exit(do_lang_tempcode('ERROR_UPLOADING_ATTACHMENTS'));
			}
		}
		$size=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_max_email_attach_size_mb');
		if ($size_so_far>$size*1024*1024)
		{
			warn_exit(do_lang_tempcode('EXCEEDED_ATTACHMENT_SIZE',integer_format($size)));
		}
		mail_wrap(do_lang('EMAIL_MEMBER_SUBJECT',get_site_name(),post_param('subject'),NULL,get_lang($member_id)),post_param('message'),array($email_address),$to_name,$from_email,$from_name,3,$attachments,false,get_member(),false,false,false,'MAIL',false,$extra_cc_addresses,$extra_bcc_addresses);

		log_it('EMAIL',strval($member_id),$to_name);

		require_code('autosave');
		clear_ocp_autosave();

		$url=get_param('redirect');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}
}


