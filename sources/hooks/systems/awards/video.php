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
 * @package		galleries
 */

class Hook_awards_video
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		$info=array();
		$info['connection']=$GLOBALS['SITE_DB'];
		$info['table']='videos';
		$info['date_field']='add_date';
		$info['id_field']='id';
		$info['add_url']=(has_submit_permission('mid',get_member(),get_ip_address(),'cms_galleries'))?build_url(array('page'=>'cms_galleries','type'=>'av'),get_module_zone('cms_galleries')):new ocp_tempcode();
		$info['category_field']='cat';
		$info['category_type']='galleries';
		$info['parent_spec__table_name']='galleries';
		$info['parent_spec__parent_name']='parent_id';
		$info['parent_spec__field_name']='name';
		$info['parent_field_name']='cat';
		$info['submitter_field']='submitter';
		$info['id_is_string']=false;
		require_lang('galleries');
		$info['title']=do_lang_tempcode('VIDEOS');
		$info['validated_field']='validated';
		$info['category_is_string']=true;
		$info['archive_url']=build_url(array('page'=>'galleries'),(!is_null($zone))?$zone:get_module_zone('galleries'));
		$info['cms_page']='cms_galleries';
		$info['where']='cat NOT LIKE \''.db_encode_like('download\_%').'\'';
		$info['views_field']='video_views';
		$info['supports_custom_fields']=true;

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @return tempcode	Results
	 */
	function run($row,$zone,$give_context=true,$include_breadcrumbs=true)
	{
		require_code('galleries');

		return render_video_box($row,$zone,$give_context,$include_breadcrumbs);
	}

}


