<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/*EXTRA FUNCTIONS: imap\_.+*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		tickets
 */

/**
 * Send out an e-mail message for a ticket / ticket reply.
 *
 * @param  ID_TEXT	Ticket ID
 * @param  mixed		URL to the ticket (URLPATH or Tempcode)
 * @param  string		The ticket type's label
 * @param  string		Ticket subject
 * @param  string		Ticket message
 * @param  string		Display name of ticket owner
 * @param  EMAIL		E-mail address of ticket owner
 * @param  string		Display name of staff poster
 * @param  boolean	Whether this is a new ticket, just created by the ticket owner
 */
function ticket_outgoing_message($ticket_id,$ticket_url,$ticket_type_text,$subject,$message,$to_name,$to_email,$from_displayname,$new=false)
{
	if (is_object($ticket_url)) $ticket_url=$ticket_url->evaluate();

	if ($to_email=='') return;

	$headers='';
	$from_email=get_option('ticket_email_from');
	if ($from_email=='') $from_email=get_option('staff_address');
	$website_email=get_option('website_email');
	if ($website_email=='') $website_email=$from_email;
	$headers.='From: '.do_lang('TICKET_SIMPLE_FROM',get_site_name(),$from_displayname).' <'.$website_email.'>'."\r\n";
	$headers.='Reply-To: '.do_lang('TICKET_SIMPLE_FROM',get_site_name(),$from_displayname).' <'.$from_email.'>';

	$tightened_subject=str_replace(array(chr(10),chr(13)),array('',''),$subject);
	$extended_subject=do_lang('TICKET_SIMPLE_SUBJECT_'.($new?'new':'reply'),$subject,$ticket_id,array($ticket_type_text,$from_displayname,get_site_name()));

	require_code('mail');
	$extended_message='';
	$extended_message.=do_lang('TICKET_SIMPLE_MAIL_'.($new?'new':'reply'),get_site_name(),$ticket_type_text,array($ticket_url,$from_displayname));
	$extended_message.=comcode_to_clean_text($message);

	mail($to_name.' <'.$to_email.'>',$extended_subject,$extended_message,$headers);
}

/**
 * Send out an e-mail about us not recognising an e-mail address for a ticket.
 *
 * @param  string		Subject line of original message
 * @param  string		Body of original message
 * @param  string		E-mail address we tried to bind to
 * @param  string		E-mail address of sender (usually the same as $email, but not if it was a forwarded e-mail)
 */
function ticket_email_cannot_bind($subject,$body,$email,$email_bounce_to)
{
	$headers='';
	$from_email=get_option('ticket_email_from');
	if ($from_email=='') $from_email=get_option('staff_address');
	$website_email=get_option('website_email');
	if ($website_email=='') $website_email=$from_email;
	$headers.='From: '.get_site_name().' <'.$website_email.'>'."\r\n";
	$headers.='Reply-To: '.get_site_name().' <'.$from_email.'>';

	require_code('mail');
	$extended_subject=do_lang('TICKET_CANNOT_BIND_SUBJECT',$subject,$email,get_site_name());
	$extended_message=do_lang('TICKET_CANNOT_BIND_MAIL',comcode_to_clean_text($body),$email,array($subject,get_site_name()));

	mail($email_bounce_to,$extended_subject,$extended_message,$headers);
}

/**
 * Scan for new e-mails in the support inbox.
 */
function ticket_incoming_scan()
{
	if (get_option('ticket_mail_on')=='0') return;

	if (!function_exists('imap_open')) warn_exit(do_lang_tempcode('IMAP_NEEDED'));

	require_lang('tickets');
	require_code('tickets2');

	$server=get_option('ticket_mail_server');
	$port=get_option('ticket_mail_server_port');
	$type=get_option('ticket_mail_server_type');

	$username=get_option('ticket_mail_username');
	$password=get_option('ticket_mail_password');

	$ssl=(substr($type,-1)=='s');
	if ($ssl) $type=substr($type,0,strlen($type)-1);
	$ref='{'.$server.':'.$port.'/'.$type.($ssl?'/ssl/novalidate-cert':'').'}';

	$resource=@imap_open($ref.'INBOX',$username,$password,CL_EXPUNGE);
	if ($resource!==false)
	{
		$list=imap_search($resource,(get_param_integer('test',0)==1 && $GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))?'':'UNSEEN');
		if ($list===false) $list=array();
		foreach ($list as $l)
		{
			$header=imap_headerinfo($resource,$l);

			$subject=$header->subject;

			$attachments=array();
			$attachment_size_total=0;
			$body=_imap_get_part($resource,$l,'TEXT/HTML',$attachments,$attachment_size_total);
			if ($body===NULL) // Convert from plain text
			{
				$body=_imap_get_part($resource,$l,'TEXT/PLAIN',$attachments,$attachment_size_total);
				$body=email_comcode_from_text($body);
			} else // Convert from HTML
			{
				$body=email_comcode_from_html($body);
			}
			_imap_get_part($resource,$l,'APPLICATION/OCTET-STREAM',$attachments,$attachment_size_total);

			if (!is_non_human_email($subject,$body))
			{
				imap_clearflag_full($resource,$l,'\\Seen'); // Clear this, as otherwise it is a real pain to debug (have to keep manually marking unread)

				ticket_incoming_message(
					(strlen($header->reply_toaddress)>0)?$header->reply_toaddress:$header->fromaddress,
					$subject,
					$body,
					$attachments
				);
			}

			imap_setflag_full($resource,$l,'\\Seen');
		}
		imap_close($resource);
	} else
	{
		warn_exit(do_lang_tempcode('IMAP_ERROR',imap_last_error()));
	}
}

/**
 * Convert e-mail HTML to Comcode.
 *
 * @param  string		HTML body
 * @return string		Comcode version
 */
function email_comcode_from_html($body)
{
	$body=unixify_line_format($body);

	$body=preg_replace('#.*<body[^<>]*>#is','',$body);
	$body=preg_replace('#</body>.*#is','',$body);

	$body=str_replace(array('<<','>>'),array('&lt;<','>&gt;'),$body);
	$body=str_replace(array(' style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;"',' apple-width="yes" apple-height="yes"','<br clear="all">',' class="gmail_extra"',' class="gmail_quote"',' style="word-wrap:break-word"',' style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; "'),array('','','<br />','','','',''),$body);
	$body=preg_replace('# style="text-indent:0px.*"#U','',$body); // Apple Mail long list of styles

	$body=preg_replace('#<div[^<>]*>On (.*) wrote:</div><br[^<>]*><blockquote[^<>]*>#i','[quote="${1}"]',$body); // Apple Mail
	$body=preg_replace('#(<div[^<>]*>)On (.*) wrote:<br[^<>]*><blockquote[^<>]*>#i','${1}[quote="${2}"]',$body); // gmail
	$body=preg_replace('#(\[quote="[^"]*) &lt;<.*>&gt;#U','${1}',$body); // Remove e-mail address (Apple Mail)
	$body=preg_replace('#(\[quote="[^"]*) <span[^<>]*>&lt;<.*>&gt;</span>#U','${1}',$body); // Remove e-mail address (gmail)
	$body=preg_replace('#<blockquote[^<>]*>#i','[quote]',$body);
	$body=preg_replace('#</blockquote>#i','[/quote]',$body);
	$body=preg_replace('<img [^<>]*src="cid:[^"]*"[^<>]*>','',$body); // We will get this as an attachment instead

	do
	{
		$pos=strpos($body,'<div apple-content-edited="true">');
		if ($pos!==false)
		{
			$stack=1;
			$len=strlen($body);
			for ($pos_b=$pos+1;$pos_b<$len;$pos_b++)
			{
				if ($body[$pos_b]=='<')
				{
					if (substr($body,$pos_b,4)=='<div')
					{
						$stack++;
					} else
					{
						if (substr($body,$pos_b,5)=='</div')
						{
							$stack--;
							if ($stack==0)
							{
								$body=substr($body,0,$pos).substr($body,$pos_b);
								break;
							}
						}
					}
				}
			}
		}
	}
	while ($pos!==false);

	$body=ocp_trim($body,true);

	require_code('comcode_from_html');
	$body=semihtml_to_comcode($body,true);

	$body=preg_replace('#\[quote\](\s|<br />)+#s','[quote]',$body);
	$body=preg_replace('#(\s|<br />)+\[/quote\]#s','[/quote]',$body);

	$body=str_replace("\n\n\n","\n\n",$body);

	// Tidy up the body
	foreach (array('TICKET_SIMPLE_MAIL_new_regexp','TICKET_SIMPLE_MAIL_reply_regexp') as $s)
	{
		$body=preg_replace('#'.str_replace("\n","(\n|<br[^<>]*>)",do_lang($s)).'#','',$body);
	}
	$body=trim($body,"- \n\r");

	return $body;
}

/**
 * Convert e-mail text to Comcode.
 *
 * @param  string		Text body
 * @return string		Comcode version
 */
function email_comcode_from_text($body)
{
	$body=unixify_line_format($body);

	$body=preg_replace_callback('#(\n> .*)+#','_convert_text_quote_to_comcode',$body);

	// Tidy up the body
	foreach (array('TICKET_SIMPLE_MAIL_new_regexp','TICKET_SIMPLE_MAIL_reply_regexp') as $s)
	{
		$body=preg_replace('#'.do_lang($s).'#','',$body);
	}
	$body=trim($body,"- \n\r");

	return $body;
}

/**
 * See if we need to skip over an e-mail message, due to it not being from a human.
 *
 * @param  string		Subject line
 * @param  string		Message body
 * @return boolean	Whether it should not be processed
 */
function is_non_human_email($subject,$body)
{
	$junk=false;
	$junk_strings=array(
		'Delivery Status Notification',
		'Delivery Notification',
		'Returned mail',
		'Undeliverable message',
		'Mail delivery failed',
		'Failure Notice',
		'Delivery Failure',
		'Nondeliverable',
		'Undeliverable',
	);
	foreach ($junk_strings as $j)
	{
		if ((strpos(strtolower($subject),strtolower($j))!==false) || (strpos(strtolower($body),strtolower($j))!==false))
			$junk=true;
	}
	return $junk;
}

/**
 * Process a quote block in plain-text e-mail, into a Comcode quote tag. preg callback.
 *
 * @param  array		preg Matches
 * @return string		The result
 */
function _convert_text_quote_to_comcode($matches)
{
	return '[quote]'.trim(preg_replace('#\n> (.*)#',"\n".'${1}',$matches[0])).'[/quote]';
}

/**
 * Get the mime type for a part of the IMAP structure.
 *
 * @param  object		Structure
 * @return string		Mime type
 */
function _imap_get_mime_type($structure)
{
	$primary_mime_type=array('TEXT','MULTIPART','MESSAGE','APPLICATION','AUDIO','IMAGE','VIDEO','OTHER');
	if ($structure->subtype)
	{
		return $primary_mime_type[intval($structure->type)].'/'.strtoupper($structure->subtype);
	}
	return 'TEXT/PLAIN';
}

/**
 * Find a message part of an e-mail that matches a mime-type.
 * Taken from http://www.php.net/manual/en/function.imap-fetchbody.php
 *
 * @param  resource	IMAP connection object
 * @param  integer	Message number
 * @param  string		Mime type (in upper case)
 * @param  array		Map of attachments (name to file data); only populated if $mime_type is APPLICATION/OCTET-STREAM
 * @param  integer	Total size of attachments in bytes
 * @param  ?object	IMAP message structure (NULL: look up)
 * @param  string		Message part number (blank: root)
 * @return ?string	The message part (NULL: could not find one)
 */
function _imap_get_part($stream,$msg_number,$mime_type,&$attachments,&$attachment_size_total,$structure=NULL,$part_number='')
{
	if ($structure===NULL)
	{
		$structure=imap_fetchstructure($stream,$msg_number);
	}

	$part_mime_type=_imap_get_mime_type($structure);

	if ($mime_type=='APPLICATION/OCTET-STREAM')
	{
		$disposition=$structure->ifdisposition?strtoupper($structure->disposition):'';
		if (($disposition=='ATTACHMENT') || (($structure->type!=1) && ($structure->type!=2) && (isset($structure->bytes)) && ($part_mime_type!='TEXT/PLAIN') && ($part_mime_type!='TEXT/HTML')))
		{
			$filename=$structure->parameters[0]->value;

			if ($attachment_size_total+$structure->bytes<1024*1024*20/*20MB is quite enough, thankyou*/)
			{
				$filedata=imap_fetchbody($stream,$msg_number,$part_number);
				if ($structure->encoding==0)
				{
					$filedata=imap_utf7_decode($filedata);
				}
				elseif ($structure->encoding==1)
				{
					$filedata=imap_utf8($filedata);
				}
				elseif ($structure->encoding==3)
				{
					$filedata=imap_base64($filedata);
				}
				elseif ($structure->encoding==4)
				{
					$filedata=imap_qprint($filedata);
				}

				$attachments[$filename]=$filedata;

				$attachment_size_total+=$structure->bytes;
			} else
			{
				$new_filename='errors-'.$filename.'.txt';
				$attachments[]=array($new_filename=>'20MB filesize limit exceeded');
			}
		}
	} else
	{
		if ($part_mime_type==$mime_type)
		{
			require_code('character_sets');

			if ($part_number=='')
			{
				$part_number='1';
			}
			$filedata=imap_fetchbody($stream,$msg_number,$part_number);
			if ($structure->encoding==0)
			{
				$filedata=imap_utf7_decode($filedata);
				$filedata=convert_to_internal_encoding($filedata,'iso-8859-1');
			}
			elseif ($structure->encoding==1)
			{
				$filedata=imap_utf8($filedata);
				$filedata=convert_to_internal_encoding($filedata,'utf-8');
			}
			elseif ($structure->encoding==3)
			{
				$filedata=imap_base64($filedata);
				$filedata=convert_to_internal_encoding($filedata,'iso-8859-1');
			}
			elseif ($structure->encoding==4)
			{
				$filedata=imap_qprint($filedata);
				$filedata=convert_to_internal_encoding($filedata,'iso-8859-1');
			}
			return fix_bad_unicode($filedata);
		}
	}

	if ($structure->type==1) // Multi-part
	{
		foreach ($structure->parts as $index=>$sub_structure)
		{
			if ($part_number!='')
			{
				$prefix=$part_number.'.';
			} else
			{
				$prefix='';
			}
			$data=_imap_get_part($stream,$msg_number,$mime_type,$attachments,$attachment_size_total,$sub_structure,$prefix.strval($index+1));
			if ($data!==NULL)
			{
				return $data;
			}
		}
	}

	return NULL;
}

/**
 * Process an e-mail found, sent to the support ticket system.
 *
 * @param  EMAIL		From e-mail
 * @param  string		E-mail subject
 * @param  string		E-mail body
 * @param  array		Map of attachments (name to file data); only populated if $mime_type is APPLICATION/OCTET-STREAM
 */
function ticket_incoming_message($from_email,$subject,$body,$attachments)
{
	require_lang('tickets');
	require_code('tickets');
	require_code('tickets2');

	$from_email_orig=$from_email;

	// Try to bind to an existing ticket
	$existing_ticket=mixed();
	$matches=array();
	if (preg_match('#'.do_lang('TICKET_SIMPLE_SUBJECT_regexp').'#',$subject,$matches)!=0)
	{
		if (strpos($matches[1],'_')!==false)
		{
			$existing_ticket=$matches[1];

			// Validate
			$topic_id=$GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier(get_option('ticket_forum_name'),$existing_ticket);
			if (is_null($topic_id)) $existing_ticket=NULL; // Invalid
		}
	}

	// Remove any tags from the subject line
	$num_matches=preg_match_all('# \[([^\[\]]+)\]#',$subject,$matches);
	$tags=array();
	for ($i=0;$i<$num_matches;$i++)
	{
		$tags[]=$matches[1][$i];
		$subject=str_replace($matches[0][$i],'',$subject);
	}

	// De-forward
	$forwarded=false;
	foreach (array('fwd: ','fw: ') as $prefix)
	{
		if (substr(strtolower($subject),0,strlen($prefix))==$prefix)
		{
			$subject=substr($subject,strlen($prefix));
			$forwarded=true;
			$body=preg_replace('#^(\[semihtml\])?(<br />\n)*Begin forwarded message:(\n|<br />)*#','${1}',$body);
		}
	}
	if ($forwarded)
	{
		if (preg_match('#From:(.*)#',$body,$matches)!=0)
		{
			$from_email=$matches[1];
		}
	}

	// Clean up e-mail address
	if (preg_match('#([\w\.\-\+]+@[\w\.\-]+)#',$from_email,$matches)!=0)
	{
		$from_email=$matches[1];
	}

	// Try to bind to a from member
	$member_id=mixed();
	foreach ($tags as $tag)
	{
		$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($tag);
		if (!is_null($member_id))
		{
			break;
		}
	}
	if (is_null($member_id))
	{
		$member_id=$GLOBALS['SITE_DB']->query_select_value_if_there('ticket_known_emailers','member_id',array(
			'email_address'=>$from_email,
		));
		if (is_null($member_id))
		{
			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_email_address($from_email);
			if (is_null($member_id))
			{
				if (is_null($existing_ticket))
				{
					// E-mail back, saying user not found
					ticket_email_cannot_bind($subject,$body,$from_email,$from_email_orig);
					return;
				} else
				{
					$_temp=explode('_',$existing_ticket);
					$member_id=intval($_temp[0]);
				}
			}
		}
	}

	// Remember the e-mail address to member ID mapping
	$GLOBALS['SITE_DB']->query_delete('ticket_known_emailers',array(
		'email_address'=>$from_email,
	));
	$GLOBALS['SITE_DB']->query_insert('ticket_known_emailers',array(
		'email_address'=>$from_email,
		'member_id'=>$member_id,
	));

	// Add in attachments
	foreach ($attachments as $filename=>$filedata)
	{
		$new_filename=preg_replace('#\..*#','',$filename).'.dat';
		do
		{
			$new_path=get_custom_file_base().'/uploads/attachments/'.$new_filename;
			if (file_exists($new_path)) $new_filename=uniqid('',true).'_'.preg_replace('#\..*#','',$filename).'.dat';
		}
		while (file_exists($new_path));
		file_put_contents($new_path,$filedata);
		sync_file($new_path);
		fix_permissions($new_path);

		$attachment_id=$GLOBALS['SITE_DB']->query_insert('attachments',array(
			'a_member_id'=>$member_id,
			'a_file_size'=>strlen($filedata),
			'a_url'=>'uploads/attachments/'.rawurlencode($new_filename),
			'a_thumb_url'=>'',
			'a_original_filename'=>$filename,
			'a_num_downloads'=>0,
			'a_last_downloaded_time'=>time(),
			'a_description'=>'',
			'a_add_time'=>time()
		),true);

		$body.="\n\n".'[attachment framed="1" thumb="1"]'.strval($attachment_id).'[/attachment]';
	}

	// Post
	if (is_null($existing_ticket))
	{
		$new_ticket_id=strval($member_id).'_'.uniqid('');

		$_home_url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$new_ticket_id,'redirect'=>NULL),'_SELF',NULL,false,true,true);
		$home_url=$_home_url->evaluate();

		// Pick up ticket type
		$ticket_type=mixed();
		$tags[]=do_lang('OTHER');
		$tags[]=do_lang('GENERAL');
		foreach ($tags as $tag)
		{
			$ticket_type=$GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types t JOIN '.get_table_prefix().'translate tt ON tt.id=t.ticket_type','ticket_type',array('text_original'=>$tag));
			if (!is_null($ticket_type)) break;
		}
		if (is_null($ticket_type))
			$ticket_type=$GLOBALS['SITE_DB']->query_select_value('ticket_types','ticket_type');

		// Create the ticket...

		ticket_add_post($member_id,$new_ticket_id,$ticket_type,$subject,$body,$home_url);

		// Send email (to staff)
		send_ticket_email($new_ticket_id,$subject,$body,$home_url,$from_email,$ticket_type,$member_id,true);
	} else
	{
		$_home_url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$existing_ticket,'redirect'=>NULL),'_SELF',NULL,false,true,true);
		$home_url=$_home_url->evaluate();

		// Reply to the ticket...

		$ticket_type=$GLOBALS['SITE_DB']->query_select_value_if_there('tickets','ticket_type',array(
			'ticket_id'=>$existing_ticket,
		));

		ticket_add_post($member_id,$existing_ticket,$ticket_type,$subject,$body,$home_url);

		// Find true ticket title
		$_forum=1; $_topic_id=1; $_ticket_type=1; // These will be returned by reference
		$posts=get_ticket_posts($existing_ticket,$_forum,$_topic_id,$_ticket_type);
		if (!is_array($posts)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$__title=do_lang('UNKNOWN');
		foreach ($posts as $ticket_post)
		{
			$__title=$ticket_post['title'];
			if ($__title!='') break;
		}

		// Send email (to staff & to confirm receipt to $member_id)
		send_ticket_email($existing_ticket,$__title,$body,$home_url,$from_email,-1,$member_id,true);
	}
}
