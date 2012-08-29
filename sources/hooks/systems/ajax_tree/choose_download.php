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
 * @package		downloads
 */

class Hook_choose_download
{

	/**
	 * Standard modular run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by Javascript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
	function run($id,$options,$default=NULL)
	{
		require_code('downloads');

		if ((!is_numeric($id)) && ($id!='')) // This code is actually for ocPortal.com, for the addon directory
		{
			if (substr($id,0,8)=='Version ')
			{
				$id_float=floatval(substr($id,8));
				do
				{
					$str='Version '.float_to_raw_string($id_float,1);
					$_id=$GLOBALS['SITE_DB']->query_select_value_if_there('download_categories c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON t.id=c.category','c.id',array('parent_id'=>3,'text_original'=>$str));
					if (is_null($_id)) $id_float-=0.1;
				}
				while ((is_null($_id)) && ($id_float!=0.0));
			} else
			{
				$_id=$GLOBALS['SITE_DB']->query_select_value_if_there('download_categories c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON t.id=c.category','c.id',array('text_original'=>$id));
			}
			if (is_null($_id)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
			$id=strval($_id);
		}

		$only_owned=array_key_exists('only_owned',$options)?(is_null($options['only_owned'])?NULL:intval($options['only_owned'])):NULL;
		$shun=array_key_exists('shun',$options)?$options['shun']:NULL;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;
		$tar_filter=array_key_exists('tar_filter',$options)?($options['original_filename']):false;
		$tree=get_downloads_tree($only_owned,is_null($id)?NULL:intval($id),NULL,NULL,$shun,is_null($id)?0:1,false,$editable_filter,$tar_filter);
		if (!has_actual_page_access(NULL,'downloads')) $tree=array();

		$file_type=get_param('file_type','');

		$out='';
		foreach ($tree as $t)
		{
			$_id=$t['id'];
			if ($id===strval($_id)) // Possible when we look under as a root
			{
				asort($t['entries']);

				foreach ($t['entries'] as $eid=>$etitle)
				{
					$row=$GLOBALS['SITE_DB']->query_select('download_downloads',array('description','original_filename'),array('id'=>$eid),'',1);

					if ($file_type!='')
					{
						if (substr($row[0]['original_filename'],-strlen($file_type)-1)!='.'.$file_type) continue;
					}

					$lang_id=$row[0]['description'];
					$description=get_translated_text($lang_id);
					$description_html=get_translated_tempcode($lang_id);

					if (addon_installed('galleries'))
					{
						// Images
						$images_details=new ocp_tempcode();
						$_out=new ocp_tempcode();
						require_lang('galleries');
						$cat='download_'.strval($eid);
						$map=array('cat'=>$cat);
						if (!has_privilege(get_member(),'see_unvalidated')) $map['validated']=1;
						$rows=$GLOBALS['SITE_DB']->query_select('images',array('*'),$map,'ORDER BY id',200/*Stop sillyness, could be a DOS attack*/);
						$counter=0;
						$div=2;
						$_out=new ocp_tempcode();
						$_row=new ocp_tempcode();
						require_code('images');
						while (array_key_exists($counter,$rows))
						{
							$row=$rows[$counter];

							$view_url=$row['url'];
							if (url_is_local($view_url)) $view_url=get_custom_base_url().'/'.$view_url;
							$thumb_url=ensure_thumbnail($row['url'],$row['thumb_url'],'galleries','images',$row['id']);
							$description=get_translated_tempcode($row['description']);
							$thumb=do_image_thumb($thumb_url,'');
							$iedit_url=new ocp_tempcode();
							$_content=do_template('DOWNLOAD_SCREEN_IMAGE',array('_GUID'=>'45905cd5823af4b066ccbc18a39edd74','ID'=>strval($row['id']),'VIEW_URL'=>$view_url,'EDIT_URL'=>$iedit_url,'THUMB'=>$thumb,'DESCRIPTION'=>$description));

							$_row->attach(do_template('DOWNLOAD_GALLERY_IMAGE_CELL',array('_GUID'=>'e016f7655dc6519d9536aa51e4bed57b','CONTENT'=>$_content)));

							if (($counter%$div==1) && ($counter!=0))
							{
								$_out->attach(do_template('DOWNLOAD_GALLERY_ROW',array('_GUID'=>'59744ea8227da11901ddb3f4de04c88d','CELLS'=>$_row)));
								$_row=new ocp_tempcode();
							}

							$counter++;
						}
						if (!$_row->is_empty())
							$_out->attach(do_template('DOWNLOAD_GALLERY_ROW',array('_GUID'=>'3f368a6baa7e544f76e66d4bd8291c4b','CELLS'=>$_row)));
						$description_html=do_template('DOWNLOAD_AND_IMAGES_SIMPLE_BOX',array('_GUID'=>'a273f4beb94672ee44bdfdf06bf328c8','DESCRIPTION'=>$description_html,'IMAGES'=>$_out));
					}

					$out.='<entry id="'.xmlentities(strval($eid)).'" description="'.xmlentities(strip_comcode($description)).'" description_html="'.xmlentities($description_html->evaluate()).'" title="'.xmlentities($etitle).'" selectable="true"></entry>';
				}
				continue;
			}
			$title=$t['title'];
			$has_children=($t['child_count']!=0) || ($t['child_entry_count']!=0);

			$out.='<category id="'.xmlentities(strval($_id)).'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="false"></category>';
		}

		// Mark parent cats for pre-expansion
		if ((!is_null($default)) && ($default!=''))
		{
			$cat=$GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads','category_id',array('id'=>intval($default)));
			while (!is_null($cat))
			{
				$out.='<expand>'.strval($cat).'</expand>';
				$cat=$GLOBALS['SITE_DB']->query_select_value_if_there('download_categories','parent_id',array('id'=>$cat));
			}
		}

		return '<result>'.$out.'</result>';
	}

	/**
	 * Standard modular simple function for ajax-tree hooks. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return tempcode		The nice list
	 */
	function simple($id,$options,$it=NULL)
	{
		require_code('downloads');

		$only_owned=array_key_exists('only_owned',$options)?(is_null($options['only_owned'])?NULL:intval($options['only_owned'])):NULL;
		$shun=array_key_exists('shun',$options)?$options['shun']:NULL;
		$editable_filter=array_key_exists('editable_filter',$options)?($options['editable_filter']):false;
		return nice_get_downloads_tree(is_null($it)?NULL:intval($it),$only_owned,$shun,false,$editable_filter);
	}

}


