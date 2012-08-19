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
 * @package		core
 */

/**
 * Find a different content type code from the one had. In future we intend to change everything to be cma_hook internally.
 *
 * @param  ID_TEXT		Content type type we know
 * @set addon cma_hook award_hook search_hook table seo_type_code feedback_type_code permissions_type_code module table
 * @param  ID_TEXT		Content type ID we know
 * @param  ID_TEXT		Desired content type
 * @set addon cma_hook search_hook table seo_type_code feedback_type_code permissions_type_code module table
 * @return ID_TEXT		Corrected content type type (blank: could not find)
 */
function convert_ocportal_type_codes($type_has,$type_id,$type_wanted)
{
	$real_type_wanted=$type_wanted;
	if ($type_wanted=='award_hook') $type_wanted='cma_hook';

	// TODO: remove legacy later
	if ($type_has=='award_hook')
	{
		if ($type_id=='wiki_page') $type_id='wiki_page';
		if ($type_id=='wiki_post') $type_id='wiki_post';
		$type_has='cma_hook';
	}

	// Search content-meta-aware hooks
	$found_type_id='';
	$cma_hooks=find_all_hooks('systems','content_meta_aware');
	foreach (array_keys($cma_hooks) as $cma_hook)
	{
		if ((($type_has=='cma_hook') && ($cma_hook==$type_id)) || ($type_has!='cma_hook'))
		{
			require_code('hooks/systems/content_meta_aware/'.$cma_hook);
			$cms_ob=object_factory('Hook_content_meta_aware_'.$cma_hook);
			$cma_info=$cms_ob->info();
			$cma_info['cma_hook']=$cma_hook;
			if ((isset($cma_info[$type_has])) && (isset($cma_info[$type_wanted])) && ($cma_info[$type_has]==$type_id))
			{
				$found_type_id=$cma_info[$type_wanted];
				break;
			}
		}
	}

	if ($real_type_wanted=='award_hook')
	{
		// TODO: remove legacy later
		if ($found_type_id=='wiki_page') $found_type_id='wiki_page';
		if ($found_type_id=='wiki_post') $found_type_id='wiki_post';
		if ($found_type_id=='iotd') $found_type_id=''; // TODO: No award hook right now
	}

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
	require_code('hooks/systems/content_meta_aware/'.$content_type);
	$cma_ob=object_factory('Hook_content_meta_aware_'.$content_type);
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

			return array($zone.':'.$page,$submitter_id,$cma_info,$content_row,$content_url,$content_url_email_safe);
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
	if (is_array($cma_info['id_field']))
	{
		$bits=explode(':',$content_id);
		$where=array();
		foreach ($bits as $i=>$bit)
		{
			$where[$cma_info['id_field'][$i]]=$id_field_numeric?intval($bit):$bit;
		}
	} else
	{
		if ($id_field_numeric)
		{
			$where=array($cma_info['id_field']=>intval($content_id));
		} else
		{
			$where=array($cma_info['id_field']=>$content_id);
		}
	}
	$_content=$db->query_select($cma_info['table'].' r',array('r.*'),$where,'',1);
	return array_key_exists(0,$_content)?$_content[0]:NULL;
}
