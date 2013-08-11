<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		tickets
 */

/**
 * Get the forum ID for a given ticket type and member, taking the ticket_member_forums and ticket_type_forums options
 * into account.
 *
 * @param  ?AUTO_LINK		The member ID (NULL: no member)
 * @param  ?integer			The ticket type (NULL: all ticket types)
 * @param  boolean			Create the forum if it's missing
 * @param  boolean			Whether to skip showing errors, returning NULL instead
 * @return ?AUTO_LINK		Forum ID (NULL: not found)
 */
function get_ticket_forum_id($member=NULL,$ticket_type=NULL,$create=false,$silent_error_handling=false)
{
	static $fid_cache=array();
	if (isset($fid_cache[$member][$ticket_type])) return $fid_cache[$member][$ticket_type];

	$root_forum=get_option('ticket_forum_name');

	// Check the root ticket forum is valid
	$fid=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($root_forum);
	if (is_null($fid))
	{
		if ($silent_error_handling) return NULL;
		warn_exit(do_lang_tempcode('NO_FORUM'));
	}

	// Only the root ticket forum is supported for non-OCF installations
	if (get_forum_type()!='ocf')
		return $fid;

	require_code('ocf_forums_action');
	require_code('ocf_forums_action2');

	$category_id=$GLOBALS['FORUM_DB']->query_select_value('f_forums','f_forum_grouping_id',array('id'=>$fid));

	if ((!is_null($member)) && (get_option('ticket_member_forums')=='1'))
	{
		$username=$GLOBALS['FORUM_DRIVER']->get_username($member);
		$rows=$GLOBALS['FORUM_DB']->query_select('f_forums',array('id'),array('f_parent_forum'=>$fid,'f_name'=>$username),'',1);
		if (count($rows)==0)
			$fid=ocf_make_forum($username,do_lang('SUPPORT_TICKETS_FOR_MEMBER',$username),$category_id,NULL,$fid);
		else $fid=$rows[0]['id'];
	}

	if ((!is_null($ticket_type)) && (get_option('ticket_type_forums')=='1'))
	{
		$ticket_type_text=get_translated_text($ticket_type);
		$rows=$GLOBALS['FORUM_DB']->query_select('f_forums',array('id'),array('f_parent_forum'=>$fid,'f_name'=>$ticket_type_text),'',1);
		if (count($rows)==0)
			$fid=ocf_make_forum($ticket_type_text,do_lang('SUPPORT_TICKETS_FOR_TYPE',$ticket_type),$category_id,NULL,$fid);
		else $fid=$rows[0]['id'];
	}

	$fid_cache[$member][$ticket_type]=$fid;

	return $fid;
}

/**
 * Returns whether the given forum ID is for a ticket forum (subforum of the root ticket forum).
 *
 * @param  AUTO_LINK		The forum ID
 * @return boolean		Whether the given forum is a ticket forum
 */
function is_ticket_forum($forum_id)
{
	if (is_null($forum_id)) return NULL;

	$root_forum_id=get_ticket_forum_id(NULL,NULL,false,true);
	if ($forum_id===$root_forum_id) return true;

	$query='SELECT COUNT(*) AS cnt FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($forum_id).' AND f_parent_forum IN (SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($root_forum_id).' OR f_parent_forum IN (SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($root_forum_id).'))';

	$rows=$GLOBALS['FORUM_DB']->query($query);
	return ($rows[0]['cnt']!=0);
}

