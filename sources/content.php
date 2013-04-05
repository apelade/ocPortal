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
 * @package		core
 */

/*

Notes about hook info...
 - id_field may be array (which means that ":" works as a delimiter) (if so, the first one is the main ID, while the second one is assumed to be a qualifier)
  - unless, parent_spec__table_name!=table, where we require a single id_field, knowing it is a join field in all tables
 - category_field may be array of two (if so, the second one is assumed the main category, while the first is assumed to be for supplemental permission checking)
 - category_field may be NULL
 - category_type may be array
 - category_type may be '<page>' or '<zone>' (meaning "use page/zone permissions instead")
 - category_type may be NULL
 - category_type may be missing
 - add_url may contain '!' (meaning "parent category ID goes here")
 - submitter_field may be a field:regexp

*/

/**
 * Get the CMA hook object for a content type. Also works for resource types (i.e. if it's a resource, although not actually considered content technically).
 *
 * @param  ID_TEXT	The content type
 * @return ?object	The object (NULL: could not get one)
 */
function get_content_object($content_type)
{
	$path='hooks/systems/content_meta_aware/'.filter_naughty_harsh($content_type);
	if ((file_exists(get_file_base().'/sources/'.$path.'.php')) || (file_exists(get_file_base().'/sources_custom/'.$path.'.php')))
	{
		require_code($path);
		$ob=object_factory('Hook_content_meta_aware_'.filter_naughty_harsh($content_type),true);
	} else // Okay, maybe it's a resource type (more limited functionality).
	{
		require_code('hooks/systems/resource_meta_aware/'.filter_naughty_harsh($content_type));
		$ob=object_factory('Hook_resource_meta_aware_'.filter_naughty_harsh($content_type),true);
	}
	return $ob;
}

/**
 * Find a different content type code from the one had. In future we intend to change everything to be content_type internally.
 *
 * @param  ID_TEXT		Content type type we know
 * @set addon content_type meta_hook search_hook seo_type_code feedback_type_code permissions_type_code module table
 * @param  ID_TEXT		Content type ID we know
 * @param  ID_TEXT		Desired content type
 * @set addon content_type meta_hook search_hook seo_type_code feedback_type_code permissions_type_code module table
 * @return ID_TEXT		Corrected content type type (blank: could not find)
 */
function convert_ocportal_type_codes($type_has,$type_id,$type_wanted)
{
	$real_type_wanted=$type_wanted;

	$type_id=preg_replace('#^catalogues__\w+_#','catalogues_',$type_id);

	$type_id=preg_replace('#^catalogues__\w+_#','catalogues_',$type_id);

	// Search content-meta-aware hooks
	$found_type_id='';
	$cma_hooks=find_all_hooks('systems','content_meta_aware');
	foreach (array_keys($cma_hooks) as $content_type)
	{
		if ((($type_has=='content_type') && ($content_type==$type_id)) || ($type_has!='content_type'))
		{
			require_code('content');
			$cma_ob=get_content_object($content_type);
			$cma_info=$cma_ob->info();
			$cma_info['content_type']=$content_type;
			if ((isset($cma_info[$type_has])) && (isset($cma_info[$type_wanted])) && (($cma_info[$type_has]==$type_id) || ($cma_info[$type_has]==preg_replace('#__.*$#','',$type_id))))
			{
				$found_type_id=$cma_info[$type_wanted];
				break;
			}
		}
	}

	if ($found_type_id===NULL) $found_type_id='';
	return $found_type_id;
}

/**
 * Get meta details of a content item
 *
 * @param  ID_TEXT		Content type
 * @param  ID_TEXT		Content ID
 * @return array			Tuple: title, submitter, content hook info, URL (for use within current browser session), URL (for use in emails / sharing)
 */
function content_get_details($content_type,$content_id)
{
	require_code('content');
	$cma_ob=get_content_object($content_type);
	$cma_info=$cma_ob->info();

	$db=$GLOBALS[(substr($cma_info['table'],0,2)=='f_')?'FORUM_DB':'SITE_DB'];

	$content_row=content_get_row($content_id,$cma_info);
	if (is_null($content_row))
	{
		if (($content_type=='comcode_page') && (strpos($content_id,':')!==false))
		{
			list($zone,$page)=explode(':',$content_id,2);

			$members=$GLOBALS['FORUM_DRIVER']->member_group_query($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),1);
			if (count($members)!=0)
			{
				$submitter_id=$GLOBALS['FORUM_DRIVER']->pname_id($members[key($members)]);
			} else
			{
				$submitter_id=db_get_first_id()+1; // On OCF and most forums, this is the first admin member
			}

			$content_row=array(
				'the_zone'=>$zone,
				'the_page'=>$page,
				'p_parent_page'=>'',
				'p_validated'=>1,
				'p_edit_date'=>NULL,
				'p_add_date'=>time(),
				'p_submitter'=>$submitter_id,
				'p_show_as_edit'=>0
			);

			$content_url=build_url(array('page'=>$page),$zone,NULL,false,false,false);
			$content_url_email_safe=build_url(array('page'=>$page),$zone,NULL,false,false,true);

			$_content_title=$GLOBALS['SITE_DB']->query_select_value_if_there('cached_comcode_pages','cc_page_title',array('the_zone'=>$zone,'the_page'=>$page));
			if (!is_null($_content_title))
			{
				$content_title=get_translated_text($_content_title);
			} else
			{
				$content_title=$zone.':'.$page;
			}

			return array($content_title,$submitter_id,$cma_info,$content_row,$content_url,$content_url_email_safe);
		}

		return array(NULL,NULL,NULL,NULL,NULL,NULL);
	}

	if (is_null($cma_info['title_field']))
	{
		$content_title=do_lang($cma_info['content_type_label']);
	} else
	{
		if (strpos($cma_info['title_field'],'CALL:')!==false)
		{
			$content_title=call_user_func(trim(substr($cma_info['title_field'],5)),array('id'=>$content_id));
		} else
		{
			$_content_title=$content_row[$cma_info['title_field']];
			$content_title=$cma_info['title_field_dereference']?get_translated_text($_content_title,$db):$_content_title;
			if ($content_title=='')
			{
				$content_title=do_lang($cma_info['content_type_label']).' (#'.(is_string($content_id)?$content_id:strval($content_id)).')';
				if ($content_type=='image' || $content_type=='video') // A bit of a fudge, but worth doing
				{
					require_lang('galleries');
					$fullname=$GLOBALS['SITE_DB']->query_select_value_if_there('galleries','fullname',array('name'=>$content_row['cat']));
					if (!is_null($fullname))
						$content_title=do_lang('VIEW_'.strtoupper($content_type).'_IN',get_translated_text($fullname));
				}
			}
		}
	}

	if (isset($cma_info['submitter_field']))
	{
		if (strpos($cma_info['submitter_field'],':')!==false)
		{
			$bits=explode(':',$cma_info['submitter_field']);
			$matches=array();
			if (preg_match('#'.$bits[1].'#',$content_row[$bits[0]],$matches)!=0)
			{
				$submitter_id=intval($matches[1]);
			} else $submitter_id=$GLOBALS['FORUM_DRIVER']->get_guest_id();
		} else
		{
			$submitter_id=$content_row[$cma_info['submitter_field']];
		}
	} else
	{
		$submitter_id=$GLOBALS['FORUM_DRIVER']->get_guest_id();
	}

	list($zone,$url_bits,$hash)=page_link_decode(str_replace('_WILD',$content_id,$cma_info['view_pagelink_pattern']));
	$content_url=build_url($url_bits,$zone,NULL,false,false,false,$hash);
	$content_url_email_safe=build_url($url_bits,$zone,NULL,false,false,true,$hash);

	return array($content_title,$submitter_id,$cma_info,$content_row,$content_url,$content_url_email_safe);
}

/**
 * Get the content row of a content item.
 *
 * @param  ID_TEXT			The content ID
 * @param  array				The info array for the content type
 * @return ?array				The row (NULL: not found)
 */
function content_get_row($content_id,$cma_info)
{
	$db=$GLOBALS[(substr($cma_info['table'],0,2)=='f_')?'FORUM_DB':'SITE_DB'];

	$id_field_numeric=array_key_exists('id_field_numeric',$cma_info)?$cma_info['id_field_numeric']:true;
	$where=get_content_where_for_str_id($content_id,$cma_info);
	$_content=$db->query_select($cma_info['table'].' r',array('r.*'),$where,'',1);
	return array_key_exists(0,$_content)?$_content[0]:NULL;
}

/**
 * Get the string content ID for some data.
 *
 * @param  array				The data row
 * @param  array				The info array for the content type
 * @return ID_TEXT			The ID
 */
function extract_content_str_id_from_data($data,$cma_info)
{
	$id_field=$cma_info['id_field'];
	$id='';
	foreach (is_array($id_field)?$id_field:array($id_field) as $id_field_part)
	{
		if ($id!='') $id.=':';
		$id.=(is_integer($data[$id_field_part])?strval($data[$id_field_part]):$data[$id_field_part]);
	}
	return $id;
}

/**
 * Given the string content ID get a mapping we could use as a WHERE map.
 *
 * @param  ID_TEXT			The ID
 * @param  array				The info array for the content type
 * @param  ?string			The table alias (NULL: none)
 * @return array				The mapping
 */
function get_content_where_for_str_id($str_id,$cma_info,$table_alias=NULL)
{
	$where=array();
	$id_field=$cma_info['id_field'];
	$id_parts=explode(':',$str_id);
	foreach (is_array($id_field)?$id_field:array($id_field) as $i=>$id_field_part)
	{
		$val=array_key_exists($i,$id_parts)?$id_parts[$i]:'';
		$where[(is_null($table_alias)?'':($table_alias.'.')).$id_field_part]=$cma_info['id_field_numeric']?intval($val):$val;
	}
	return $where;
}

/**
 * Given the string content ID get a mapping we could use as a WHERE map.
 *
 * @param  array				The ID
 * @param  array				The info array for the content type
 * @param  ?string			The table alias (NULL: none)
 */
function append_content_select_for_id(&$select,$cma_info,$table_alias=NULL)
{
	foreach (is_array($cma_info['id_field'])?$cma_info['id_field']:array($cma_info['id_field']) as $id_field_part)
	{
		$select[]=(is_null($table_alias)?'':($table_alias.'.')).$id_field_part;
	}
}
