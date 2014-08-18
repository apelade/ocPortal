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

class Hook_awards_forum
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		if (get_forum_type()!='ocf') return NULL;

		$info=array();
		$info['connection']=$GLOBALS['FORUM_DB'];
		$info['table']='f_forums';
		$info['date_field']=NULL;
		$info['id_field']='id';
		$info['add_url']='';
		$info['category_field']='id';
		$info['parent_spec__table_name']='f_forums';
		$info['parent_spec__parent_name']='f_parent_forum';
		$info['parent_spec__field_name']='id';
		$info['parent_field_name']='f_parent_forum';
		$info['id_is_string']=false;
		$info['title']=do_lang_tempcode('SECTION_FORUMS');
		$info['category_is_string']=false;
		$info['archive_url']=build_url(array('page'=>'forumview'),get_module_zone('forumview'));
		$info['cms_page']='topics';
		$info['supports_custom_fields']=true;

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @return tempcode	Results
	 */
	function run($row,$zone)
	{
		unset($zone);

		$just_forum_row=db_map_restrict($row,array('id','f_description'));

		$view_map=array('page'=>'forumview');
		if ($row['id']!=db_get_first_id()) $view_map['id']=$row['id'];
		$url=build_url($view_map,get_module_zone('forumview'));

		return do_template('SIMPLE_PREVIEW_BOX',array('TITLE'=>$row['f_name'],'SUMMARY'=>get_translated_tempcode('f_forums',$just_forum_row,'f_description'),'URL'=>$url));
	}

}


