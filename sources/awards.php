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
 * @package		awards
 */

/*

Notes about hook info...
 - id_field may be array
 - category_field may be array
 - category_field may be NULL
 - category_type may be array
 - category_type may be '!'
 - category_type may be NULL
 - category_type may be missing
 - add_url may contain '!'
 - submitter_field may be a field:regexp

*/

/**
 * Get details of awards won for a content item.
 *
 * @param  ID_TEXT			The award content type
 * @param  ID_TEXT			The content ID
 * @return array				List of awards won
 */
function find_awards_for($content_type,$id)
{
	$awards=array();

	$rows=$GLOBALS['SITE_DB']->query_select('award_archive a LEFT JOIN '.get_table_prefix().'award_types t ON t.id=a.a_type_id',array('date_and_time','a_type_id'),array('a_content_type'=>$content_type,'content_id'=>$id),'ORDER BY date_and_time DESC');
	foreach ($rows as $row)
	{
		require_lang('awards');
		$awards[]=array(
			'AWARD_TYPE'=>get_translated_text($GLOBALS['SITE_DB']->query_value('award_types','a_title',array('id'=>$row['a_type_id']))),
			'AWARD_TIMESTAMP'=>strval($row['date_and_time'])
		);
	}

	return $awards;
}

/**
 * Give an award.
 *
 * @param  AUTO_LINK			The award ID
 * @param  ID_TEXT			The content ID
 * @param  ?TIME				Time the award was given (NULL: now)
 */
function give_award($award_id,$content_id,$time=NULL)
{
	require_lang('awards');

	if (is_null($time)) $time=time();

	$awards=$GLOBALS['SITE_DB']->query_select('award_types',array('*'),array('id'=>$award_id),'',1);
	if (!array_key_exists(0,$awards)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$award_title=get_translated_text($awards[0]['a_title']);
	log_it('GIVE_AWARD',strval($award_id),$award_title);

	require_code('hooks/systems/awards/'.filter_naughty_harsh($awards[0]['a_content_type']));
	$object=object_factory('Hook_awards_'.$awards[0]['a_content_type']);
	$info=$object->info();
	if (is_null($info)) fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
	if ((array_key_exists('submitter_field',$info)) && (!is_null($info['submitter_field'])))
	{
		require_code('content');
		list($content_title,$member_id,,$content)=content_get_details($awards[0]['a_content_type'],$content_id);

		if (is_null($content)) warn_exit(do_lang_tempcode('_MISSING_RESOURCE',escape_html($awards[0]['a_content_type'].':'.$content_id)));

		// Lots of fiddling around to work out how to check permissions for this
		$permission_type_code=convert_ocportal_type_codes('award_hook',$awards[0]['a_content_type'],'permissions_type_code');
		$module=convert_ocportal_type_codes('module',$awards[0]['a_content_type'],'permissions_type_code');
		if ($module=='') $module=$content_id;
		$category_id=mixed();
		if (isset($info['category_field']))
		{
			if (is_array($info['category_field']))
			{
				$category_id=$content[$info['category_field'][1]];
			} else
			{
				$category_id=$content[$info['category_field']];
			}
		}
		if ((has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'awards')) && (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),$module)) && (($permission_type_code=='') || (is_null($category_id)) || (has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),$permission_type_code,is_integer($category_id)?strval($category_id):$category_id))))
		{
			syndicate_described_activity(((is_null($member_id)) || (is_guest($member_id)))?'awards:_ACTIVITY_GIVE_AWARD':'awards:ACTIVITY_GIVE_AWARD',$award_title,$content_title,'','_SEARCH:awards:award:'.strval($award_id),'','','awards',1,NULL,false,$member_id);
		}
	} else $member_id=NULL;
	if (is_null($member_id)) $member_id=$GLOBALS['FORUM_DRIVER']->get_guest_id();

	if ((!is_guest($member_id)) && (addon_installed('points')))
	{
		require_code('points2');
		system_gift_transfer(do_lang('_AWARD',get_translated_text($awards[0]['a_title'])),$awards[0]['a_points'],$member_id);
	}

	$GLOBALS['SITE_DB']->query_insert('award_archive',array('a_type_id'=>$award_id,'member_id'=>$member_id,'content_id'=>$content_id,'date_and_time'=>$time));

	decache('main_awards');
	decache('main_multi_content');
}

/**
 * Make an award type.
 *
 * @param  SHORT_TEXT	The title
 * @param  LONG_TEXT		The description
 * @param  integer		How many points are given to the awardee
 * @param  ID_TEXT		The content type the award type is for
 * @param  BINARY			Whether to not show the awardee when displaying this award
 * @param  integer		The approximate time in hours between awards (e.g. 168 for a week)
 * @return AUTO_LINK		The ID
 */
function add_award_type($title,$description,$points,$content_type,$hide_awardee,$update_time_hours)
{
	$id=$GLOBALS['SITE_DB']->query_insert('award_types',array('a_title'=>insert_lang_comcode($title,2),'a_description'=>insert_lang($description,2),'a_points'=>$points,'a_content_type'=>filter_naughty_harsh($content_type),'a_hide_awardee'=>$hide_awardee,'a_update_time_hours'=>$update_time_hours),true);
	log_it('ADD_AWARD_TYPE',strval($id),$title);
	return $id;
}

/**
 * Edit an award type
 *
 * @param  AUTO_LINK		The ID
 * @param  SHORT_TEXT	The title
 * @param  LONG_TEXT		The description
 * @param  integer		How many points are given to the awardee
 * @param  ID_TEXT		The content type the award type is for
 * @param  BINARY			Whether to not show the awardee when displaying this award
 * @param  integer		The approximate time in hours between awards (e.g. 168 for a week)
 */
function edit_award_type($id,$title,$description,$points,$content_type,$hide_awardee,$update_time_hours)
{
	$_title=$GLOBALS['SITE_DB']->query_value_null_ok('award_types','a_title',array('id'=>$id));
	if (is_null($_title)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_description=$GLOBALS['SITE_DB']->query_value('award_types','a_description',array('id'=>$id));
	$GLOBALS['SITE_DB']->query_update('award_types',array('a_title'=>lang_remap_comcode($_title,$title),'a_description'=>lang_remap($_description,$description),'a_points'=>$points,'a_content_type'=>filter_naughty_harsh($content_type),'a_hide_awardee'=>$hide_awardee,'a_update_time_hours'=>$update_time_hours),array('id'=>$id));
	log_it('EDIT_AWARD_TYPE',strval($id),$title);
}

/**
 * Delete an award type.
 *
 * @param  AUTO_LINK		The ID
 */
function delete_award_type($id)
{
	$_title=$GLOBALS['SITE_DB']->query_value_null_ok('award_types','a_title',array('id'=>$id));
	if (is_null($_title)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_description=$GLOBALS['SITE_DB']->query_value('award_types','a_description',array('id'=>$id));
	log_it('DELETE_AWARD_TYPE',strval($id),get_translated_text($_title));
	$GLOBALS['SITE_DB']->query_delete('award_types',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('award_archive',array('a_type_id'=>$id),'',1);
	delete_lang($_title);
	delete_lang($_description);
}

/**
 * Get all the award selection fields for a content type and content ID
 *
 * @param  ID_TEXT		The content type
 * @param  ?ID_TEXT		The content ID (NULL: not added yet - therefore can't be holding the award yet)
 * @return tempcode		The fields
 */
function get_award_fields($content_type,$id=NULL)
{
	require_code('form_templates');

	$fields=new ocp_tempcode();
	$rows=$GLOBALS['SITE_DB']->query_select('award_types',array('*'),array('a_content_type'=>$content_type));

	require_lang('awards');

	foreach ($rows as $row)
	{
		if (has_category_access(get_member(),'award',strval($row['id'])))
		{
			if (!is_null($id))
			{
				$test=$GLOBALS['SITE_DB']->query_value_null_ok('award_archive','content_id',array('a_type_id'=>$row['id']),'ORDER BY date_and_time DESC');
				$has_award=($test===$id);
			} else $has_award=(get_param_integer('award',NULL)===$row['id']);

			$fields->attach(form_input_tick(get_translated_text($row['a_title']),(get_translated_text($row['a_description'])=='')?new ocp_tempcode():do_lang_tempcode('PRESENT_AWARD',get_translated_tempcode($row['a_description'])),'award_'.strval($row['id']),$has_award));
		}
	}

	if (!$fields->is_empty())
	{
		$help=paragraph(do_lang_tempcode('AWARDS_AFTER_VALIDATION'));
		if (get_option('show_docs')=='1')
			$help->attach(paragraph(symbol_tempcode('URLISE_LANG',array(do_lang('TUTORIAL_ON_THIS'),brand_base_url().'/docs'.strval(ocp_version()).'/pg/tut_featured','tut_featured','1'))));
		$_fields=do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>is_null(get_param_integer('award',NULL)),'TITLE'=>do_lang_tempcode('AWARDS'),'HELP'=>protect_from_escaping($help)));
		$_fields->attach($fields);
		$fields=$_fields;
	}

	return $fields;
}

/**
 * Situation: something that may have awards has just been added/edited. Action: add any specified awards.
 *
 * @param  ID_TEXT		The content type
 * @param  ID_TEXT		The content ID
 */
function handle_award_setting($content_type,$id)
{
	if (fractional_edit()) return;

	$rows=$GLOBALS['SITE_DB']->query_select('award_types',array('*'),array('a_content_type'=>$content_type));

	foreach ($rows as $row)
	{
		if (has_category_access(get_member(),'award',strval($row['id'])))
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('award_archive','content_id',array('a_type_id'=>$row['id']),'ORDER BY date_and_time DESC');
			$has_award=(!is_null($test)) && ($test===$id);
			$will_have_award=(post_param_integer('award_'.strval($row['id']),0)==1);

			if (($will_have_award) && ($has_award)) // Has to be recached
			{
				decache('main_awards');
			}

			if (($will_have_award) && (!$has_award)) // Set
			{
				give_award($row['id'],$id);
			} elseif ((!$will_have_award) && ($has_award)) // Unset
			{
				$GLOBALS['SITE_DB']->query_delete('award_archive',array('a_type_id'=>$row['id'],'content_id'=>strval($id)),'',1);
			} // Otherwise we're happy with the current situation (regardless of whether it is set or unset)
		}
	}
}


