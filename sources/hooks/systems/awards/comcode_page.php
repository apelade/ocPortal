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
 * @package		core_comcode_pages
 */

class Hook_awards_comcode_page
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		$info=array();
		$info['connection']=$GLOBALS['SITE_DB'];
		$info['table']='comcode_pages';
		$info['date_field']='p_add_date';
		$info['id_field']=array('the_zone','the_page');
		$info['add_url']=(has_submit_permission('high',get_member(),get_ip_address(),'cms_comcode_pages'))?build_url(array('page'=>'cms_comcode_pages','type'=>'ed'),get_module_zone('cms_comcode_pages')):new ocp_tempcode();
		$info['category_field']=array('the_zone','the_page');
		$info['category_type']='!';
		$info['submitter_field']='p_submitter';
		$info['id_is_string']=true;
		require_lang('zones');
		$info['title']=do_lang_tempcode('COMCODE_PAGES');
		$info['validated_field']='p_validated';
		$info['category_is_string']=true;
		$info['archive_url']=build_url(array('page'=>'sitemap'),get_page_zone('sitemap'));
		$info['cms_page']='cms_comcode_pages';

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
		unset($zone); // Meaningless here

		$url=build_url(array('page'=>$row['the_page']),$row['the_zone']);

		$_summary=seo_meta_get_for('comcode_page',$row['the_zone'].':'.$row['the_page']);
		$summary=$_summary[1];

		if (get_option('is_on_comcode_page_cache')=='1') // Try and force a parse of the page
		{
			request_page($row['the_page'],false,$row['the_zone'],NULL,true);
		}

		$row2=$GLOBALS['SITE_DB']->query_select('cached_comcode_pages',array('*'),array('the_zone'=>$row['the_zone'],'the_page'=>$row['the_page']),'',1);
		if (array_key_exists(0,$row2))
		{
			$cc_page_title=get_translated_text($row2[0]['cc_page_title'],NULL,NULL,true);
			if (is_null($cc_page_title)) $cc_page_title='';

			if ($summary=='')
			{
				$summary=get_translated_tempcode('cached_comcode_pages',$row2[0],'string_index');
			}
		} else
		{
			$cc_page_title='';
		}

		return do_template('COMCODE_PAGE_BOX',array('_GUID'=>'ac70e0b5a003f8dac1ff42f46af28e1d','TITLE'=>$cc_page_title,'PAGE'=>$row['the_page'],'ZONE'=>$row['the_zone'],'URL'=>$url,'SUMMARY'=>$summary));
	}

}


