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
 * @package		core_fields
 */

class Hook_fields_content_link_multi
{

	/**
	 * Find what field types this hook can serve. This method only needs to be defined if it is not serving a single field type with a name corresponding to the hook itself.
	 *
	 * @return array			Map of field type to field type title
	 */
	function get_field_types()
	{
		$hooks=find_all_hooks('systems','content_meta_aware');
		$ret=array();
		foreach (array_keys($hooks) as $hook)
		{
			if ($hook!='catalogue_entry'/*got a better field hook specifically for catalogue entries*/)
			{
				// HACKHACK: imperfect content type naming schemes
				$declared_hook=$hook;
				if ($hook=='topic') $declared_hook='forum_topic';

				$ret['ax_'.$declared_hook]=do_lang_tempcode('FIELD_TYPE_content_link_multi_x',escape_html($hook));
			}
		}
		return $ret;
	}

	// ==============
	// Module: search
	// ==============

	/**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
	function get_search_inputter($row)
	{
		return NULL;
	}

	/**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
	function inputted_to_sql_for_search($row,$i)
	{
		return exact_match_sql($row,$i);
	}

	// ===================
	// Backend: fields API
	// ===================

	/**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL)
	{
		unset($field);
		/*if (!is_null($required))
		{
			Nothing special for this hook
		}*/
		return array('long_unescaped',$default,'long');
	}

	/**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @return mixed			Rendered field (tempcode or string)
	 */
	function render_field_value($field,$ev)
	{
		if (is_object($ev)) return $ev;

		if ($ev=='') return new ocp_tempcode();

		$type=preg_replace('#^choose\_#','',substr($field['cf_type'],3));

		// HACKHACK: imperfect content type naming schemes
		if ($type=='forum_topic') $type='topic';

		$ret=new ocp_tempcode();
		$evs=explode(chr(10),$ev);
		foreach ($evs as $ev)
		{
			require_code('content');
			list($title,,$info)=content_get_details($type,$ev);

			$page_link=str_replace('_WILD',$ev,$info['view_pagelink_pattern']);
			list($zone,$map)=page_link_decode($page_link);

			$ret->attach(paragraph(hyperlink(build_url($map,$zone),$title,false,true)));
		}
		return $ret;
	}

	// ======================
	// Frontend: fields input
	// ======================

	/**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?tempcode		The Tempcode for the input field (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
	{
		$options=array();
		$type=substr($field['cf_type'],3);

		// Nice tree list selection
		if ((is_file(get_file_base().'/sources/hooks/systems/ajax_tree/choose_'.$type.'.php')) || (is_file(get_file_base().'/sources_custom/hooks/systems/ajax_tree/choose_'.$type.'.php')))
		{
			return form_input_tree_list($_cf_name,$_cf_description,'field_'.strval($field['id']),NULL,'choose_'.$type,$options,$field['cf_required']==1,str_replace(chr(10),',',$actual_value),false,NULL,true);
		}

		// Simple list selection
		require_code('hooks/systems/content_meta_aware/'.filter_naughty($type));
		$ob=object_factory('Hook_content_meta_aware_'.$type);
		$info=$ob->info();
		$db=$GLOBALS[(substr($type,0,4)=='ocf_')?'FORUM_DB':'SITE_DB'];
		$select=array();
		$select[]=$info['id_field'];
		if ($type=='comcode_page') $select[]='the_zone';
		if (!is_null($info['title_field'])) $select[]=$info['title_field'];
		$rows=$db->query_select($info['table'],$select,NULL,is_null($info['add_time_field'])?'':('ORDER BY '.$info['add_time_field'].' DESC'),2000/*reasonable limit*/);
		$list=new ocp_tempcode();
		$_list=array();
		foreach ($rows as $row)
		{
			$id=$info['id_field_numeric']?strval($row[$info['id_field']]):$row[$info['id_field']];
			if ($type=='comcode_page') $id=$row['the_zone'].':'.$id;
			if (is_null($info['title_field']))
			{
				$text=$id;
			} else
			{
				$text=$info['title_field_dereference']?get_translated_text($row[$info['title_field']]):$row[$info['title_field']];
			}
			$_list[$id]=$text;
		}
		if (count($_list)<2000) asort($_list);
		foreach ($_list as $id=>$text)
		{
			if (!is_string($id)) $id=strval($id);
			$list->attach(form_input_list_entry($id,is_null($actual_value)?false:(strpos(chr(10).$actual_value.chr(10),$id)!==false),$text));
		}
		return form_input_multi_list($_cf_name,$_cf_description,'field_'.strval($field['id']),$list,NULL,5,$field['cf_required']==1);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  string			Where the files will be uploaded to
	 * @param  ?string		Former value of field (NULL: none)
	 * @return string			The value
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		$id=$field['id'];
		$i=0;
		$value='';
		$tmp_name='field_'.strval($id);
		if (!array_key_exists($tmp_name,$_POST)) return $editing?STRING_MAGIC_NULL:'';
		foreach (is_array($_POST[$tmp_name])?$_POST[$tmp_name]:explode(',',$_POST[$tmp_name]) as $_value)
		{
			if ($_value!='')
			{
				if ($value!='') $value.=chr(10);
				$value.=$_value;
			}
		}
		return $value;
	}

}

