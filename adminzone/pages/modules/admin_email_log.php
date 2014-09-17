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
 * @package		core
 */

/**
 * Module page class.
 */
class Module_admin_email_log
{
	/**
	 * Find details of the module.
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
	 * Find entry-points available within this module.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		return array(
			'misc'=>array('EMAIL_LOG','menu/adminzone/audit/email_log'),
		);
	}

	var $title;

	/**
	 * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('email_log');

		if ($type=='misc')
		{
			$this->title=get_screen_title('EMAIL_LOG');
		}

		if ($type=='edit' || $type=='_edit')
		{
			$this->title=get_screen_title('HANDLE_QUEUED_MESSAGE');
		}

		if ($type=='mass_send')
		{
			$this->title=get_screen_title('SEND_ALL');
		}

		if ($type=='mass_delete')
		{
			$this->title=get_screen_title('DELETE_ALL');
		}

		return NULL;
	}

	/**
	 * Execute the module.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$type=get_param('type','misc');

		if ($type=='misc') return $this->show();
		if ($type=='edit') return $this->edit();
		if ($type=='_edit') return $this->_edit();
		if ($type=='mass_send') return $this->mass_send();
		if ($type=='mass_delete') return $this->mass_delete();

		return new ocp_tempcode();
	}

	/**
	 * Get a list of all the e-mails sent/queued.
	 *
	 * @return tempcode	The result of execution.
	 */
	function show()
	{
		require_code('mail');

		if (function_exists('set_time_limit')) @set_time_limit(60);

		// Put errors into table
		$start=get_param_integer('start',0);
		$max=get_param_integer('max',20);
		$sortables=array('m_date_and_time'=>do_lang_tempcode('DATE_TIME'),'m_to_name'=>do_lang_tempcode('FROM'),'m_from_name'=>do_lang_tempcode('TO'),'m_subject'=>do_lang_tempcode('SUBJECT'));
		$test=explode(' ',get_param('sort','m_date_and_time DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('DATE_TIME'),do_lang_tempcode('FROM'),do_lang_tempcode('TO'),do_lang_tempcode('SUBJECT')),$sortables,'sort',$sortable.' '.$sort_order);
		$fields=new ocp_tempcode();
		$rows=$GLOBALS['SITE_DB']->query_select('logged_mail_messages',array('*'),NULL,'ORDER BY  '.$sortable.' '.$sort_order,$max,$start);
		foreach ($rows as $row)
		{
			$queued=$row['m_queued']==1;

			if ($queued)
			{
				$edit_url=build_url(array('page'=>'_SELF','type'=>'edit','id'=>$row['id']),'_SELF');
				$date_time=hyperlink($edit_url,get_timezoned_date($row['m_date_and_time']),false,true);
				$date_time=do_lang_tempcode('MAIL_WAS_QUEUED',$date_time);
			} else
			{
				$date_time=make_string_tempcode(escape_html(get_timezoned_date($row['m_date_and_time'])));
				$date_time=do_lang_tempcode('MAIL_WAS_LOGGED',$date_time);
			}

			$from_email=$row['m_from_email'];
			if ($from_email=='') $from_email=get_option('staff_address');
			$from_name=$row['m_from_name'];
			if ($from_name=='') $from_name=get_site_name();

			$to_email=unserialize($row['m_to_email']);
			$extra_cc_addresses=($row['m_extra_cc_addresses']=='')?array():@unserialize($row['m_extra_cc_addresses']);
			$extra_bcc_addresses=($row['m_extra_bcc_addresses']=='')?array():@unserialize($row['m_extra_bcc_addresses']);
			if (is_string($to_email)) $to_email=array($to_email);
			$to_email=array_merge($to_email,$extra_cc_addresses);
			$to_email=array_merge($to_email,$extra_bcc_addresses);
			if ((is_null($to_email)) || (!array_key_exists(0,$to_email))) $to_email[0]=get_option('staff_address');
			$to_name=unserialize($row['m_to_name']);
			if (is_string($to_name)) $to_name=array($to_name);
			if ((is_null($to_name)) || ($to_name==array(NULL)) || ($to_name==array(''))) $to_name=array(get_site_name());
			if (!array_key_exists(0,$to_name)) $to_name[0]=get_site_name();

			$to_link='mailto:'.$to_email[0];
			$to_link.='?subject='.rawurlencode($row['m_subject']);
			$to_link.='&body='.rawurlencode(comcode_to_clean_text($row['m_message']));

			$fields->attach(results_entry(array(
				$date_time,
				hyperlink('mailto:'.$from_email,$from_name,false,true),
				hyperlink($to_link,$to_name[0],false,true),
				do_template('CROP_TEXT_MOUSE_OVER',array('_GUID'=>'c2fd45ce32e1c03a536674108b937098','TEXT_LARGE'=>escape_html(substr($row['m_message'],0,3000)),'TEXT_SMALL'=>escape_html($row['m_subject']))),
			)));
		}
		$max_rows=$GLOBALS['SITE_DB']->query_select_value('logged_mail_messages','COUNT(*)');
		$results_table=results_table(do_lang_tempcode('EMAIL_LOG'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',new ocp_tempcode());

		$mass_delete_url=build_url(array('page'=>'_SELF','type'=>'mass_delete'),'_SELF');
		$mass_send_url=build_url(array('page'=>'_SELF','type'=>'mass_send'),'_SELF');

		$tpl=do_template('EMAIL_LOG_SCREEN',array('_GUID'=>'8c249a372933e1215d8b9ff6d4bb0de3','TITLE'=>$this->title,'RESULTS_TABLE'=>$results_table,'MASS_DELETE_URL'=>$mass_delete_url,'MASS_SEND_URL'=>$mass_send_url));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

	/**
	 * Get a form to edit/send/delete an email.
	 *
	 * @return tempcode	The result of execution.
	 */
	function edit()
	{
		$id=get_param_integer('id');

		$fields=new ocp_tempcode();
		require_code('form_templates');

		$rows=$GLOBALS['SITE_DB']->query_select('logged_mail_messages',array('*'),array('id'=>$id));
		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$rows[0];

		$from_email=$row['m_from_email'];
		if ($from_email=='') $from_email=get_option('staff_address');
		$from_name=$row['m_from_name'];
		if ($from_name=='') $from_name=get_site_name();

		$to_email=unserialize($row['m_to_email']);
		if (is_string($to_email)) $to_email=array($to_email);
		$extra_cc_addresses=($row['m_extra_cc_addresses']=='')?array():@unserialize($row['m_extra_cc_addresses']);
		$extra_bcc_addresses=($row['m_extra_bcc_addresses']=='')?array():@unserialize($row['m_extra_bcc_addresses']);
		if (!array_key_exists(0,$to_email)) $to_email[0]=get_option('staff_address');
		$to_name=unserialize($row['m_to_name']);
		if ((is_null($to_name)) || ($to_name==array(NULL)) || ($to_name==array(''))) $to_name=array(get_site_name());
		if (is_string($to_name)) $to_name=array($to_name);
		if (!array_key_exists(0,$to_name)) $to_name[0]=get_site_name();

		$fields->attach(form_input_line_comcode(do_lang_tempcode('SUBJECT'),'','subject',$row['m_subject'],true));
		$fields->attach(form_input_email(do_lang_tempcode('FROM_EMAIL'),'','from_email',$from_email,false));
		$fields->attach(form_input_line(do_lang_tempcode('FROM_NAME'),'','from_name',$from_name,false));
		$fields->attach(form_input_line_multi(do_lang_tempcode('TO_EMAIL'),'','to_email_',$to_email,1));
		$fields->attach(form_input_line_multi(do_lang_tempcode('TO_NAME'),'','to_name',$to_name,1));
		$fields->attach(form_input_line_multi(do_lang_tempcode('EXTRA_CC_ADDRESSES'),'','extra_cc_addresses_',$extra_cc_addresses,0));
		$fields->attach(form_input_line_multi(do_lang_tempcode('EXTRA_BCC_ADDRESSES'),'','extra_bcc_addresses_',$extra_bcc_addresses,0));
		$fields->attach(form_input_text_comcode(do_lang_tempcode('MESSAGE'),'','message',$row['m_message'],true));

		$radios=new ocp_tempcode();
		$radios->attach(form_input_radio_entry('action','edit',true,do_lang_tempcode('EDIT')));
		$radios->attach(form_input_radio_entry('action','send',false,do_lang_tempcode('EDIT_AND_SEND')));
		$radios->attach(form_input_radio_entry('action','delete',false,do_lang_tempcode('DELETE')));
		$fields->attach(form_input_radio(do_lang_tempcode('ACTION'),'','action',$radios,true));

		$submit_name=do_lang_tempcode('PROCEED');

		$post_url=build_url(array('page'=>'_SELF','type'=>'_edit','id'=>$id),'_SELF');

		return do_template('FORM_SCREEN',array('_GUID'=>'84c9b97944b6cf799ac1abb5044d426a','SKIP_VALIDATION'=>true,'HIDDEN'=>'','TITLE'=>$this->title,'TEXT'=>'','URL'=>$post_url,'FIELDS'=>$fields,'SUBMIT_ICON'=>'buttons__save','SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * Actualiser to edit/send/delete an email.
	 *
	 * @return tempcode	The result of execution.
	 */
	function _edit()
	{
		$id=get_param_integer('id');

		$action=post_param('action');

		switch ($action)
		{
			case 'delete':
				$GLOBALS['SITE_DB']->query_delete('logged_mail_messages',array('id'=>$id),'',1);
				break;

			case 'send':
			case 'edit':
			default:
				$to_name=array();
				$to_email=array();
				$extra_cc_addresses=array();
				$extra_bcc_addresses=array();
				foreach ($_POST as $key=>$input_value)
				{
					//stripslashes if necessary
					if (get_magic_quotes_gpc()) $input_value=stripslashes($input_value);

					if (substr($key,0,8)=='to_name_')
					{
						$to_name[]=$input_value;
					}
					if (substr($key,0,9)=='to_email_')
					{
						$to_email[]=$input_value;
					}
					if (substr($key,0,19)=='extra_cc_addresses_')
					{
						$extra_cc_addresses[]=$input_value;
					}
					if (substr($key,0,20)=='extra_bcc_addresses_')
					{
						$extra_bcc_addresses[]=$input_value;
					}
				}

				$subject=post_param('subject');
				$from_name=post_param('from_name');
				$from_email=post_param('from_email');
				$message=post_param('message');

				$remap=array(
					'm_subject'=>$subject,
					'm_from_email'=>$from_email,
					'm_to_email'=>serialize($to_email),
					'm_extra_cc_addresses'=>serialize($extra_cc_addresses),
					'm_extra_bcc_addresses'=>serialize($extra_bcc_addresses),
					'm_from_name'=>$from_name,
					'm_to_name'=>serialize($to_name),
					'm_message'=>$message,
				);

				if ($action=='send')
				{
					$rows=$GLOBALS['SITE_DB']->query_select('logged_mail_messages',array('*'),array('id'=>$id));
					if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
					$row=$rows[0];

					$join_time=$row['m_join_time'];

					require_code('mail');
					mail_wrap($subject,$message,$to_email,$to_name,$from_email,$from_name,$row['m_priority'],unserialize($row['m_attachments']),$row['m_no_cc']==1,$row['m_as'],$row['m_as_admin']==1,$row['m_in_html']==1,true,'MAIL',false,$extra_cc_addresses,$extra_bcc_addresses,$join_time);

					$remap['m_queued']=0;
				}

				$GLOBALS['SITE_DB']->query_update('logged_mail_messages',$remap,array('id'=>$id),'',1);
				break;
		}

		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualiser to do a mass send.
	 *
	 * @return tempcode	The result of execution.
	 */
	function mass_send()
	{
		require_code('mail');
		$rows=$GLOBALS['SITE_DB']->query_select('logged_mail_messages',array('*'),array('m_queued'=>1));
		foreach ($rows as $row)
		{
			$subject=$row['m_subject'];
			$message=$row['m_message'];
			$to_email=unserialize($row['m_to_email']);
			$extra_cc_addresses=unserialize($row['m_extra_cc_addresses']);
			$extra_bcc_addresses=unserialize($row['m_extra_bcc_addresses']);
			$to_name=unserialize($row['m_to_name']);
			$from_email=$row['m_from_email'];
			$from_name=$row['m_from_name'];
			$join_time=$row['m_join_time'];

			mail_wrap($subject,$message,$to_email,$to_name,$from_email,$from_name,$row['m_priority'],unserialize($row['m_attachments']),$row['m_no_cc']==1,$row['m_as'],$row['m_as_admin']==1,$row['m_in_html']==1,true,'MAIL',false,$extra_cc_addresses,$extra_bcc_addresses,$join_time);
		}

		$GLOBALS['SITE_DB']->query_update('logged_mail_messages',array('m_queued'=>0),array('m_queued'=>1));

		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SENT_NUM',escape_html(integer_format(count($rows)))));
	}

	/**
	 * Actualiser to do a mass send.
	 *
	 * @return tempcode	The result of execution.
	 */
	function mass_delete()
	{
		$count=$GLOBALS['SITE_DB']->query_select_value('logged_mail_messages','COUNT(*)',array('m_queued'=>1));

		$GLOBALS['SITE_DB']->query_delete('logged_mail_messages',array('m_queued'=>1));

		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('DELETE_NUM',escape_html(integer_format($count))));
	}


}

