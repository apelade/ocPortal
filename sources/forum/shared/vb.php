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
 * @package		core_forum_drivers
 */

class forum_driver_vb_shared extends forum_driver_base
{

	/**
	 * Check the connected DB is valid for this forum driver.
	 *
	 * @return boolean		Whether it is valid
	 */
	function check_db()
	{
		$test=$this->connection->query('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'user',NULL,NULL,true);
		return !is_null($test);
	}

	/**
	 * Get the rows for the top given number of posters on the forum.
	 *
	 * @param  integer		The limit to the number of top posters to fetch
	 * @return array			The rows for the given number of top posters in the forum
	 */
	function get_top_posters($limit)
	{
		return $this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'user WHERE userid<>'.strval((integer)$this->get_guest_id()).' ORDER BY posts DESC',$limit);
	}

	/**
	 * Attempt to to find the member's language from their forum profile. It converts between language-identifiers using a map (lang/map.ini).
	 *
	 * @param  MEMBER				The member who's language needs to be fetched
	 * @return ?LANGUAGE_NAME	The member's language (NULL: unknown)
	 */
	function forum_get_lang($member)
	{
		unset($member);
		return NULL;
	}

	/**
	 * Find if the login cookie contains the login name instead of the member id.
	 *
	 * @return boolean		Whether the login cookie contains a login name or a member id
	 */
	function is_cookie_login_name()
	{
		return false;
	}

	/**
	 * Find the member id of the forum guest member.
	 *
	 * @return MEMBER			The member id of the forum guest member
	 */
	function get_guest_id()
	{
		return 0;
	}

	/**
	 * Add the specified custom field to the forum (some forums implemented this using proper custom profile fields, others through adding a new field).
	 *
	 * @param  string			The name of the new custom field
	 * @param  integer		The length of the new custom field
	 * @param  BINARY			Whether the field is locked
	 * @param  BINARY			Whether the field is for viewing
	 * @param  BINARY			Whether the field is for setting
	 * @param  BINARY			Whether the field is required
	 * @return boolean		Whether the custom field was created successfully
	 */
	function install_create_custom_field($name,$length,$locked=1,$viewable=0,$settable=0,$required=0)
	{
		if (!array_key_exists('vb_table_prefix',$_POST)) $_POST['vb_table_prefix']=''; // for now

		$name='ocp_'.$name;
		if ((!isset($GLOBALS['SITE_INFO']['vb_version'])) || ($GLOBALS['SITE_INFO']['vb_version']>=3.6))
		{
			$r=$this->connection->query('SELECT f.profilefieldid FROM '.$this->connection->get_table_prefix().'profilefield f LEFT JOIN '.$this->connection->get_table_prefix().'phrase p ON ('.db_string_equal_to('product','vbulletin').' AND p.varname=CONCAT(\'field\',f.profilefieldid,\'_title\')) WHERE '.db_string_equal_to('p.text',$name));
		} else
		{
			$r=$this->connection->query('SELECT profilefieldid FROM '.$_POST['vb_table_prefix'].'profilefield WHERE '.db_string_equal_to('title',$name).'\'');
		}

		if (!array_key_exists(0,$r))
		{
			if ((!isset($GLOBALS['SITE_INFO']['vb_version'])) || ($GLOBALS['SITE_INFO']['vb_version']>=3.6))
			{
				$key=$this->connection->query_insert('profilefield',array('required'=>$required,'hidden'=>1-$viewable,'maxlength'=>$length,'size'=>$length,'editable'=>$settable),true);
				$this->connection->query_insert('phrase',array('languageid'=>0,'varname'=>'field'.strval($key).'_title','fieldname'=>'cprofilefield','text'=>$name,'product'=>'vbulletin','username'=>'','dateline'=>0,'version'=>''));
			} else
			{
				$this->connection->query('INSERT INTO '.$_POST['vb_table_prefix'].'profilefield (title,description,required,hidden,maxlength,size,editable) VALUES (\''.db_escape_string($name).'\',\'\','.strval(intval($required)).','.strval(intval(1-$viewable)).',\''.strval($length).'\',\''.strval($length).'\','.strval(intval($settable)).')');
				$_key=$this->connection->query('SELECT MAX(profilefieldid) AS v FROM '.$_POST['vb_table_prefix'].'profilefield');
				$key=$_key[0]['v'];
			}
			$this->connection->query('ALTER TABLE '.$_POST['vb_table_prefix'].'userfield ADD field'.strval($key).' TEXT',NULL,NULL,true);
			return true;
		}
		return false;
	}

	/**
	 * Get the forums' table prefix for the database.
	 *
	 * @return string			The forum database table prefix
	 */
	function get_drivered_table_prefix()
	{
		global $SITE_INFO;
		return $SITE_INFO['vb_table_prefix'];
	}

	/**
	 * Get an emoticon chooser template.
	 *
	 * @param  string			The ID of the form field the emoticon chooser adds to
	 * @return tempcode		The emoticon chooser template
	 */
	function get_emoticon_chooser($field_name='post')
	{
		require_code('comcode_text');
		$emoticons=$this->connection->query_select('smilie',array('*'));
		$em=new ocp_tempcode();
		foreach ($emoticons as $emo)
		{
			$code=$emo['smilietext'];
			$em->attach(do_template('EMOTICON_CLICK_CODE',array('_GUID'=>'a9dabba90bc5e781de02ab57ebfc6e8d','FIELD_NAME'=>$field_name,'CODE'=>$code,'IMAGE'=>apply_emoticons($code))));
		}

		return $em;
	}

	/**
	 * Pin a topic.
	 *
	 * @param  AUTO_LINK		The topic ID
	 */
	function pin_topic($id)
	{
		$this->connection->query_update('threads',array('sticky'=>1),array('threadid'=>$id),'',1);
	}

	/**
	 * Get a member profile-row for the member of the given name.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return ?array			The profile-row (NULL: could not find)
	 */
	function pget_row($name)
	{
		$rows=$this->connection->query_select('user',array('*'),array('username'=>$name),'',1);
		if (!array_key_exists(0,$rows)) return NULL;
		return $rows[0];
	}

	/**
	 * From a member profile-row, get the member's primary usergroup.
	 *
	 * @param  array			The profile-row
	 * @return GROUP			The member's primary usergroup
	 */
	function pname_group($r)
	{
		return $r['usergroupid'];
	}

	/**
	 * From a member profile-row, get the member's member id.
	 *
	 * @param  array			The profile-row
	 * @return MEMBER			The member id
	 */
	function pname_id($r)
	{
		return $r['userid'];
	}

	/**
	 * From a member profile-row, get the member's name.
	 *
	 * @param  array			The profile-row
	 * @return string			The member name
	 */
	function pname_name($r)
	{
		return $r['username'];
	}

	/**
	 * From a member profile-row, get the member's e-mail address.
	 *
	 * @param  array			The profile-row
	 * @return SHORT_TEXT	The member e-mail address
	 */
	function pname_email($r)
	{
		return $r['email'];
	}

	/**
	 * Get a URL to the specified member's home (control panel).
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the members home
	 */
	function member_home_url($id)
	{
		unset($id);
		return get_forum_base_url().'/usercp.php';
	}

	/**
	 * Get the photo thumbnail URL for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL (blank: none)
	 */
	function get_member_photo_url($member)
	{
		return get_forum_base_url().'/image.php?u='.strval($member).'&type=profile';
	}

	/**
	 * Get the avatar URL for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL (blank: none)
	 */
	function get_member_avatar_url($member)
	{
		return get_forum_base_url().'/image.php?u='.strval($member);
	}

	/**
	 * Get a URL to the specified member's profile.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the member profile
	 */
	function _member_profile_url($id)
	{
		return get_forum_base_url().'/member.php?action=getinfo&userid='.strval($id);
	}

	/**
	 * Get a URL to the registration page (for people to create member accounts).
	 *
	 * @return URLPATH		The URL to the registration page
	 */
	function _join_url()
	{
		return get_forum_base_url().'/register.php?action=signup';
	}

	/**
	 * Get a URL to the members-online page.
	 *
	 * @return URLPATH		The URL to the members-online page
	 */
	function _online_members_url()
	{
		return get_forum_base_url().'/online.php';
	}

	/**
	 * Get a URL to send a private/personal message to the given member.
	 *
	 * @param  MEMBER			The member id
	 * @return URLPATH		The URL to the private/personal message page
	 */
	function _member_pm_url($id)
	{
		return get_forum_base_url().'/private.php?action=newmessage&userid='.strval($id);
	}

	/**
	 * Get a URL to the specified forum.
	 *
	 * @param  integer		The forum ID
	 * @return URLPATH		The URL to the specified forum
	 */
	function _forum_url($id)
	{
		return get_forum_base_url().'/forumdisplay.php?forumid='.strval($id);
	}

	/**
	 * Get the forum ID from a forum name.
	 *
	 * @param  SHORT_TEXT	The forum name
	 * @return integer		The forum ID
	 */
	function forum_id_from_name($forum_name)
	{
		return is_numeric($forum_name)?intval($forum_name):$this->connection->query_value_null_ok('forum','forumid',array('title'=>$forum_name));
	}

	/**
	 * Get the topic ID from a topic identifier in the specified forum. It is used by comment topics, which means that the unique-topic-name assumption holds valid.
	 *
	 * @param  string			The forum name / ID
	 * @param  SHORT_TEXT	The topic identifier
	 * @return integer		The topic ID
	 */
	function find_topic_id_for_topic_identifier($forum,$topic_identifier)
	{
		if (is_integer($forum)) $forum_id=$forum;
		else $forum_id=$this->forum_id_from_name($forum);
		return $this->connection->query_value_null_ok_full('SELECT threadid FROM '.$this->connection->get_table_prefix().'thread WHERE forumid='.strval((integer)$forum_id).' AND ('.db_string_equal_to('title',$topic_identifier).' OR title LIKE \'%: #'.db_encode_like($topic_identifier).'\')');
	}

	/**
	 * Makes a post in the specified forum, in the specified topic according to the given specifications. If the topic doesn't exist, it is created along with a spacer-post.
	 * Spacer posts exist in order to allow staff to delete the first true post in a topic. Without spacers, this would not be possible with most forum systems. They also serve to provide meta information on the topic that cannot be encoded in the title (such as a link to the content being commented upon).
	 *
	 * @param  SHORT_TEXT	The forum name
	 * @param  SHORT_TEXT	The topic identifier (usually <content-type>_<content-id>)
	 * @param  MEMBER			The member ID
	 * @param  LONG_TEXT		The post title
	 * @param  LONG_TEXT		The post content in Comcode format
	 * @param  string			The topic title; must be same as content title if this is for a comment topic
	 * @param  string			This is put together with the topic identifier to make a more-human-readable topic title or topic description (hopefully the latter and a $content_title title, but only if the forum supports descriptions)
	 * @param  ?URLPATH		URL to the content (NULL: do not make spacer post)
	 * @param  ?TIME			The post time (NULL: use current time)
	 * @param  ?IP				The post IP address (NULL: use current members IP address)
	 * @param  ?BINARY		Whether the post is validated (NULL: unknown, find whether it needs to be marked unvalidated initially). This only works with the OCF driver.
	 * @param  ?BINARY		Whether the topic is validated (NULL: unknown, find whether it needs to be marked unvalidated initially). This only works with the OCF driver.
	 * @param  boolean		Whether to skip post checks
	 * @param  SHORT_TEXT	The name of the poster
	 * @param  ?AUTO_LINK	ID of post being replied to (NULL: N/A)
	 * @param  boolean		Whether the reply is only visible to staff
	 * @return array			Topic ID (may be NULL), and whether a hidden post has been made
	 */
	function make_post_forum_topic($forum_name,$topic_identifier,$member,$post_title,$post,$content_title,$topic_identifier_encapsulation_prefix,$content_url=NULL,$time=NULL,$ip=NULL,$validated=NULL,$topic_validated=1,$skip_post_checks=false,$poster_name_if_guest='',$parent_id=NULL,$staff_only=false)
	{
		if (is_null($time)) $time=time();
		if (is_null($ip)) $ip=get_ip_address();
		$forum_id=$this->forum_id_from_name($forum_name);
		if (is_null($forum_id)) warn_exit(do_lang_tempcode('MISSING_FORUM',escape_html($forum_name)));
		$username=$this->get_username($member);
		$topic_id=$this->find_topic_id_for_topic_identifier($forum_name,$topic_identifier);
		$is_new=is_null($topic_id);
		if ($is_new)
		{
			$topic_id=$this->connection->query_insert('thread',array('title'=>$content_title.', '.$topic_identifier_encapsulation_prefix.': #'.$topic_identifier,'lastpost'=>$time,'forumid'=>$forum_id,'open'=>1,'postusername'=>$username,'postuserid'=>$member,'lastposter'=>$username,'dateline'=>$time,'visible'=>1),true);
			$home_link=hyperlink($content_url,escape_html($content_title));
			$this->connection->query_insert('post',array('threadid'=>$topic_id,'username'=>do_lang('SYSTEM','','','',get_site_default_lang()),'userid'=>0,'title'=>'','dateline'=>$time,'pagetext'=>do_lang('SPACER_POST',$home_link->evaluate(),'','',get_site_default_lang()),'allowsmilie'=>1,'ipaddress'=>'127.0.0.1','visible'=>1));
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'forum SET threadcount=(threadcount+1) WHERE forumid='.strval((integer)$forum_id),1);
		}

		$GLOBALS['LAST_TOPIC_ID']=$topic_id;
		$GLOBALS['LAST_TOPIC_IS_NEW']=$is_new;

		if ($post=='') return array($topic_id,false);

		$last_post_id=$this->connection->query_insert('post',array('threadid'=>$topic_id,'username'=>$username,'userid'=>$member,'title'=>$post_title,'dateline'=>$time,'pagetext'=>$post,'allowsmilie'=>1,'ipaddress'=>$ip,'visible'=>1),true);
		$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'forum SET replycount=(replycount+1), lastpost='.strval($time).', lastposter=\''.db_escape_string($username).'\' WHERE forumid='.strval((integer)$forum_id),1);
		if ((!isset($GLOBALS['SITE_INFO']['vb_version'])) || ($GLOBALS['SITE_INFO']['vb_version']>=3.6))
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'thread SET lastpostid='.strval((integer)$last_post_id).', replycount=(replycount+1), lastpost='.strval($time).', lastposter=\''.db_escape_string($username).'\' WHERE threadid='.strval((integer)$topic_id),1);
		} else
		{
			$this->connection->query('UPDATE '.$this->connection->get_table_prefix().'thread SET replycount=(replycount+1), lastpost='.strval($time).', lastposter=\''.db_escape_string($username).'\' WHERE threadid='.strval((integer)$topic_id),1);
		}

		return array($topic_id,false);
	}

	/**
	 * Get an array of maps for the topic in the given forum.
	 *
	 * @param  integer		The topic ID
	 * @param  integer		The comment count will be returned here by reference
	 * @param  integer		Maximum comments to returned
	 * @param  integer		Comment to start at
	 * @param  boolean		Whether to mark the topic read (ignored for this forum driver)
	 * @param  boolean		Whether to show in reverse
	 * @return mixed			The array of maps (Each map is: title, message, member, date) (-1 for no such forum, -2 for no such topic)
	 */
	function get_forum_topic_posts($topic_id,&$count,$max=100,$start=0,$mark_read=true,$reverse=false)
	{
		if (is_null($topic_id)) return (-2);
		$order=$reverse?'dateline DESC':'dateline';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'post WHERE threadid='.strval((integer)$topic_id).' AND pagetext NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\' ORDER BY '.$order,$max,$start);
		$count=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'post WHERE threadid='.strval((integer)$topic_id).' AND pagetext NOT LIKE \''.db_encode_like(substr(do_lang('SPACER_POST','','','',get_site_default_lang()),0,20).'%').'\'');
		$out=array();
		foreach ($rows as $myrow)
		{
			$temp=array();
			$temp['title']=$myrow['title'];
			global $LAX_COMCODE;
			$temp2=$LAX_COMCODE;
			$LAX_COMCODE=true;
			$temp['message']=comcode_to_tempcode(@html_entity_decode($myrow['pagetext'],ENT_QUOTES,get_charset()),$myrow['userid']);
			$LAX_COMCODE=$temp2;
			$temp['user']=$myrow['userid'];
			$temp['date']=$myrow['dateline'];

			$out[]=$temp;
		}

		return $out;
	}

	/**
	 * Get a URL to the specified topic ID. Most forums don't require the second parameter, but some do, so it is required in the interface.
	 *
	 * @param  integer		The topic ID
	 * @param string			  The forum ID
	 * @return URLPATH		The URL to the topic
	 */
	function topic_url($id,$forum)
	{
		unset($forum);
		return get_forum_base_url().'/showthread.php?threadid='.strval($id);
	}

	/**
	 * Get a URL to the specified post id.
	 *
	 * @param  integer		The post id
	 * @param string			The forum ID
	 * @return URLPATH		The URL to the post
	 */
	function post_url($id,$forum)
	{
		unset($forum);
		return get_forum_base_url().'/showpost.php?p='.strval($id);
	}

	/**
	 * Get an array of topics in the given forum. Each topic is an array with the following attributes:
	 * - id, the topic ID
	 * - title, the topic title
	 * - lastusername, the username of the last poster
	 * - lasttime, the timestamp of the last reply
	 * - closed, a Boolean for whether the topic is currently closed or not
	 * - firsttitle, the title of the first post
	 * - firstpost, the first post (only set if $show_first_posts was true)
	 *
	 * @param  mixed			The forum name or an array of forum IDs
	 * @param  integer		The limit
	 * @param  integer		The start position
	 * @param  integer		The total rows (not a parameter: returns by reference)
	 * @param  SHORT_TEXT	The topic title filter
	 * @param  boolean		Whether to show the first posts
	 * @param  string			The date key to sort by
	 * @set    lasttime firsttime
	 * @param  boolean		Whether to limit to hot topics
	 * @param  SHORT_TEXT	The topic description filter
	 * @return ?array			The array of topics (NULL: error)
	 */
	function show_forum_topics($name,$limit,$start,&$max_rows,$filter_topic_title='',$show_first_posts=false,$date_key='lasttime',$hot=false,$filter_topic_description='')
	{
		if (is_integer($name)) $id_list='forumid='.strval((integer)$name);
		elseif (!is_array($name))
		{
			$id=$this->forum_id_from_name($name);
			if (is_null($id)) return NULL;
			$id_list='forumid='.strval((integer)$id);
		} else
		{
			$id_list='';
			foreach (array_keys($name) as $id)
			{
				if ($id_list!='') $id_list.=' OR ';
				$id_list.='forumid='.strval((integer)$id);
			}
			if ($id_list=='') return NULL;
		}

		$topic_filter=($filter_topic_title!='')?('AND title LIKE \''.db_encode_like($filter_topic_title).'\''):'';
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'thread WHERE ('.$id_list.') '.$topic_filter.' ORDER BY '.(($date_key=='lastpost')?'last_post':'dateline').' DESC',$limit,$start);
		$max_rows=$this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'thread WHERE ('.$id_list.') '.$topic_filter);
		$out=array();
		foreach ($rows as $i=>$r)
		{
			$out[$i]=array();
			$out[$i]['id']=$r['threadid'];
			$out[$i]['num']=$r['replycount']+1;
			$out[$i]['title']=$r['title'];
			$out[$i]['description']=$r['title'];
			$out[$i]['firstusername']=$r['postusername'];
			$out[$i]['lastusername']=$r['lastposter'];
			$out[$i]['firstmemberid']=$r['postuserid'];
			$out[$i]['firsttime']=$r['dateline'];
			$out[$i]['lasttime']=$r['lastpost'];
			$out[$i]['closed']=($r['open']==0);
			$fp_rows=$this->connection->query('SELECT title,pagetext,userid FROM '.$this->connection->get_table_prefix().'post WHERE pagetext NOT LIKE \''.db_encode_like(do_lang('SPACER_POST','','','',get_site_default_lang()).'%').'\' AND threadid='.strval((integer)$out[$i]['id']).' ORDER BY dateline',1);
			if (!array_key_exists(0,$fp_rows))
			{
				unset($out[$i]);
				continue;
			}
			$out[$i]['firsttitle']=$fp_rows[0]['title'];
			if ($show_first_posts)
			{
				global $LAX_COMCODE;
				$temp=$LAX_COMCODE;
				$LAX_COMCODE=true;
				$out[$i]['firstpost']=comcode_to_tempcode(@html_entity_decode($fp_rows[0]['pagetext'],ENT_QUOTES,get_charset()),$fp_rows[0]['userid']);
				$LAX_COMCODE=$temp;
			}
			$fp_rows=$this->connection->query('SELECT title,pagetext,userid FROM '.$this->connection->get_table_prefix().'post WHERE pagetext NOT LIKE \''.db_encode_like(do_lang('SPACER_POST','','','',get_site_default_lang()).'%').'\' AND threadid='.strval((integer)$out[$i]['id']).' ORDER BY dateline DESC',1);
			$out[$i]['lastmemberid']=$fp_rows[0]['userid'];
		}
		if (count($out)!=0) return $out;
		return NULL;
	}

	/**
	 * Get an array of members who are in at least one of the given array of usergroups.
	 *
	 * @param  array			The array of usergroups
	 * @param  ?integer		Return up to this many entries for primary members and this many entries for secondary members (NULL: no limit, only use no limit if querying very restricted usergroups!)
	 * @param  integer		Return primary members after this offset and secondary members after this offset
	 * @return ?array			The array of members (NULL: no members)
	 */
	function member_group_query($groups,$max=NULL,$start=0)
	{
		$_groups='';
		foreach ($groups as $group)
		{
			if ($_groups!='') $_groups.=' OR ';
			$_groups.='usergroupid='.strval((integer)$group);
		}
		return $this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'user WHERE '.$_groups.' ORDER BY usergroupid,userid ASC',$max,$start);
	}

	/**
	 * This is the opposite of the get_next_member function.
	 *
	 * @param  MEMBER			The member id to decrement
	 * @return ?MEMBER		The previous member id (NULL: no previous member)
	 */
	function get_previous_member($member)
	{
		$tempid=$this->connection->query_value_null_ok_full('SELECT userid FROM '.$this->connection->get_table_prefix().'user WHERE userid<'.strval((integer)$member).' AND userid<>0 ORDER BY userid DESC');
		return $tempid;
	}

	/**
	 * Get the member id of the next member after the given one, or NULL.
	 * It cannot be assumed there are no gaps in member ids, as members may be deleted.
	 *
	 * @param  MEMBER			The member id to increment
	 * @return ?MEMBER		The next member id (NULL: no next member)
	 */
	function get_next_member($member)
	{
		$tempid=$this->connection->query_value_null_ok_full('SELECT userid FROM '.$this->connection->get_table_prefix().'user WHERE userid>'.strval((integer)$member).' ORDER BY userid');
		return $tempid;
	}

	/**
	 * Try to find a member with the given IP address
	 *
	 * @param  IP				The IP address
	 * @return array			The distinct rows found
	 */
	function probe_ip($ip)
	{
		$a=$this->connection->query_select('user',array('DISTINCT userid AS id'),array('ipaddress'=>$ip));
		$b=$this->connection->query_select('post',array('DISTINCT userid AS id'),array('ipaddress'=>$ip));
		return array_merge($a,$b);
	}

	/**
	 * Get the name relating to the specified member id.
	 * If this returns NULL, then the member has been deleted. Always take potential NULL output into account.
	 *
	 * @param  MEMBER			The member id
	 * @return ?SHORT_TEXT	The member name (NULL: member deleted)
	 */
	function _get_username($member)
	{
		if ($member==$this->get_guest_id()) return do_lang('GUEST');
		return $this->get_member_row_field($member,'username');
	}

	/**
	 * Get the e-mail address for the specified member id.
	 *
	 * @param  MEMBER			The member id
	 * @return SHORT_TEXT	The e-mail address
	 */
	function _get_member_email_address($member)
	{
		return $this->get_member_row_field($member,'email');
	}

	/**
	 * Find if this member may have e-mails sent to them
	 *
	 * @param  MEMBER			The member id
	 * @return boolean		Whether the member may have e-mails sent to them
	 */
	function get_member_email_allowed($member)
	{
		return ($this->get_member_row_field($member,'options')&16)!=0;
	}

	/**
	 * Get the timestamp of a member's join date.
	 *
	 * @param  MEMBER			The member id
	 * @return TIME			The timestamp
	 */
	function get_member_join_timestamp($member)
	{
		return $this->get_member_row_field($member,'joindate');
	}

	/**
	 * Find all members with a name matching the given SQL LIKE string.
	 *
	 * @param  string			The pattern
	 * @param  ?integer		Maximum number to return (limits to the most recent active) (NULL: no limit)
	 * @return ?array			The array of matched members (NULL: none found)
	 */
	function get_matching_members($pattern,$limit=NULL)
	{
		$rows=$this->connection->query('SELECT * FROM '.$this->connection->get_table_prefix().'user WHERE username LIKE \''.db_encode_like($pattern).'\' AND userid<>'.strval($this->get_guest_id()).' ORDER BY lastactivity DESC',$limit);
		global $M_SORT_KEY;
		$M_SORT_KEY='username';
		uasort($rows,'multi_sort');
		return $rows;
	}

	/**
	 * Get the given member's post count.
	 *
	 * @param  MEMBER			The member id
	 * @return integer		The post count
	 */
	function get_post_count($member)
	{
		$c=$this->get_member_row_field($member,'posts');
		if (is_null($c)) return 0;
		return $c;
	}

	/**
	 * Get the given member's topic count.
	 *
	 * @param  MEMBER			The member id
	 * @return integer		The topic count
	 */
	function get_topic_count($member)
	{
		return $this->connection->query_value('thread','COUNT(*)',array('postuserid'=>$member));
	}

	/**
	 * Find the base URL to the emoticons.
	 *
	 * @return URLPATH		The base URL
	 */
	function get_emo_dir()
	{
		return get_forum_base_url().'/';
	}

	/**
	 * Get a map between smiley codes and templates representing the HTML-image-code for this smiley. The smilies present of course depend on the forum involved.
	 *
	 * @return array			The map
	 */
	function find_emoticons()
	{
		global $EMOTICON_CACHE;
		if (!is_null($EMOTICON_CACHE)) return $EMOTICON_CACHE;
		$EMOTICON_CACHE=array();
		$rows=$this->connection->query_select('smilie',array('*'));
		$EMOTICON_CACHE=array();
		foreach ($rows as $myrow)
		{
			$src=$myrow['smiliepath'];
			if (url_is_local($src)) $src=$this->get_emo_dir().$src;
			$EMOTICON_CACHE[$myrow['smilietext']]=array('EMOTICON_IMG_CODE_DIR',$src,$myrow['smilietext']);
		}
		uksort($EMOTICON_CACHE,'strlen_sort');
		$EMOTICON_CACHE=array_reverse($EMOTICON_CACHE);
		return $EMOTICON_CACHE;
	}

	/**
	 * Find a list of all forum skins (aka themes).
	 *
	 * @return array			The list of skins
	 */
	function get_skin_list()
	{
		$table='style';
		$codename='title';

		$rows=$this->connection->query_select($table,array($codename));
		return collapse_1d_complexity($codename,$rows);
	}

	/**
	 * Try to find the theme that the logged-in/guest member is using, and map it to an ocPortal theme.
	 * The themes/map.ini file functions to provide this mapping between forum themes, and ocPortal themes, and has a slightly different meaning for different forum drivers. For example, some drivers map the forum themes theme directory to the ocPortal theme name, whilst others made the humanly readeable name.
	 *
	 * @param  boolean		Whether to avoid member-specific lookup
	 * @return ID_TEXT		The theme
	 */
	function _get_theme($skip_member_specific=false)
	{
		$def='';

		// Load in remapper
		$map=file_exists(get_file_base().'/themes/map.ini')?better_parse_ini_file(get_file_base().'/themes/map.ini'):array();

		if (!$skip_member_specific)
		{
			// Work out
			$member=get_member();
			if ($member>0)
				$skin=$this->get_member_row_field($member,'styleid'); else $skin=0;
			if ($skin>0) // User has a custom theme
			{
				$vb=$this->connection->query_value_null_ok('style','title',array('styleid'=>$skin));
				if (!is_null($vb)) $def=array_key_exists($vb,$map)?$map[$vb]:$vb;
			}
		}

		// Look for a skin according to our site name (we bother with this instead of 'default' because ocPortal itself likes to never choose a theme when forum-theme integration is on: all forum [via map] or all ocPortal seems cleaner, although it is complex)
		if ((!(strlen($def)>0)) || (!file_exists(get_custom_file_base().'/themes/'.$def)))
		{
			$vb=$this->connection->query_value_null_ok('style','title',array('title'=>get_site_name()));
			if (!is_null($vb)) $def=array_key_exists($vb,$map)?$map[$vb]:$vb;
		}

		// Hmm, just the very-default then
		if ((!(strlen($def)>0)) || (!file_exists(get_custom_file_base().'/themes/'.$def)))
		{
			$def=array_key_exists('default',$map)?$map['default']:'default';
		}

		return $def;
	}

	/**
	 * Get the number of members currently online on the forums.
	 *
	 * @return integer		The number of members
	 */
	function get_num_users_forums()
	{
		return $this->connection->query_value_null_ok_full('SELECT COUNT(DISTINCT userid) FROM '.$this->connection->get_table_prefix().'session WHERE lastactivity>'.strval(time()-60*intval(get_option('users_online_time'))));
	}

	/**
	 * Get the number of members registered on the forum.
	 *
	 * @return integer		The number of members
	 */
	function get_members()
	{
		return $this->connection->query_value('user','COUNT(*)');
	}

	/**
	 * Get the total topics ever made on the forum.
	 *
	 * @return integer		The number of topics
	 */
	function get_topics()
	{
		return $this->connection->query_value('thread','COUNT(*)');
	}

	/**
	 * Get the total posts ever made on the forum.
	 *
	 * @return integer		The number of posts
	 */
	function get_num_forum_posts()
	{
		return $this->connection->query_value('post','COUNT(*)');
	}

	/**
	 * Get the number of new forum posts.
	 *
	 * @return integer		The number of posts
	 */
	function _get_num_new_forum_posts()
	{
		return $this->connection->query_value_null_ok_full('SELECT COUNT(*) FROM '.$this->connection->get_table_prefix().'post WHERE dateline>'.strval(time()-60*60*24));
	}

	/**
	 * Set a custom profile fields value. It should not be called directly.
	 *
	 * @param  MEMBER			The member id
	 * @param  string			The field name
	 * @param  string			The value
	 */
	function set_custom_field($member,$field,$amount)
	{
		if ((!isset($GLOBALS['SITE_INFO']['vb_version'])) || ($GLOBALS['SITE_INFO']['vb_version']>=3.6))
		{
			$id=$this->connection->query_value_null_ok_full('SELECT f.profilefieldid FROM '.$this->connection->get_table_prefix().'profilefield f LEFT JOIN '.$this->connection->get_table_prefix().'phrase p ON ('.db_string_equal_to('product','vbulletin').' AND p.varname=CONCAT(\'field\',f.profilefieldid,\'_title\')) WHERE '.db_string_equal_to('p.text','ocp_'.$field));
		} else
		{
			$id=$this->connection->query_value_null_ok('profilefield','profilefieldid',array('title'=>'ocp_'.$field));
		}
		if (is_null($id)) return;
		$old=$this->connection->query_value_null_ok('userfield','userid',array('userid'=>$member));
		if (is_null($old)) $this->connection->query_insert('userfield',array('userid'=>$member));
		$this->connection->query_update('userfield',array('field'.strval($id)=>$amount),array('userid'=>$member),'',1);
	}

	/**
	 * Get custom profile fields values for all 'ocp_' prefixed keys.
	 *
	 * @param  MEMBER			The member id
	 * @return ?array			A map of the custom profile fields, key_suffix=>value (NULL: no fields)
	 */
	function get_custom_fields($member)
	{
		if ((!isset($GLOBALS['SITE_INFO']['vb_version'])) || ($GLOBALS['SITE_INFO']['vb_version']>=3.6))
		{
			$rows=$this->connection->query('SELECT f.profilefieldid,p.text AS title FROM '.$this->connection->get_table_prefix().'profilefield f LEFT JOIN '.$this->connection->get_table_prefix().'phrase p ON ('.db_string_equal_to('product','vbulletin').' AND p.varname=CONCAT(\'field\',f.profilefieldid,\'_title\')) WHERE p.text LIKE \''.db_encode_like('ocp_%').'\'');
		} else
		{
			$rows=$this->connection->query('SELECT profilefieldid,title FROM '.$this->connection->get_table_prefix().'profilefield WHERE title LIKE \''.db_encode_like('ocp_%').'\'');
		}
		$values=$this->connection->query_select('userfield',array('*'),array('userid'=>$member),'',1);
		if (!array_key_exists(0,$values)) return NULL;

		$out=array();
		foreach ($rows as $row)
		{
			$title=substr($row['title'],4);
			$out[$title]=$values[0]['field'.strval($row['profilefieldid'])];
		}
		return $out;
	}

	/**
	 * Get a member id from the given member's username.
	 *
	 * @param  SHORT_TEXT	The member name
	 * @return MEMBER			The member id
	 */
	function get_member_from_username($name)
	{
		return $this->connection->query_value_null_ok('user','userid',array('username'=>$name));
	}

	/**
	 * Get a first known IP address of the given member.
	 *
	 * @param  MEMBER			The member id
	 * @return IP				The IP address
	 */
	function get_member_ip($member)
	{
		return $this->get_member_row_field($member,'ipaddress');
	}

	/**
	 * Gets a whole member row from the database.
	 *
	 * @param  MEMBER			The member id
	 * @return ?array			The member row (NULL: no such member)
	 */
	function get_member_row($member)
	{
		if (array_key_exists($member,$this->MEMBER_ROWS_CACHED)) return $this->MEMBER_ROWS_CACHED[$member];

		$rows=$this->connection->query_select('user',array('*'),array('userid'=>$member),'',1);
		if ($member==$this->get_guest_id())
		{
			$rows[0]['username']=do_lang('GUEST');
			$rows[0]['email']=NULL;
			$rows[0]['emailnotification']=0;
			$rows[0]['joindate']=time();
			$rows[0]['posts']=0;
			$rows[0]['styleid']=NULL;
			$rows[0]['usergroupid']=1;
		}
		if (!array_key_exists(0,$rows)) return NULL;
		$this->MEMBER_ROWS_CACHED[$member]=$rows[0];
		return $this->MEMBER_ROWS_CACHED[$member];
	}

	/**
	 * Gets a named field of a member row from the database.
	 *
	 * @param  MEMBER			The member id
	 * @param  string			The field identifier
	 * @return mixed			The field
	 */
	function get_member_row_field($member,$field)
	{
		$row=$this->get_member_row($member);
		return is_null($row)?NULL:$row[$field];
	}

}


