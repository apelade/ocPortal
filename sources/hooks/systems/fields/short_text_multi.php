<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

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

class Hook_fields_short_text_multi
{
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
		return NULL;
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
	 * @param  ?object		Database connection (NULL: main site database)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL,$db=NULL)
	{
		if ($required!==NULL)
		{
			if (($required) && ($default=='')) $default='default';
			$default=strval(insert_lang_comcode($default,3,$db));
		}
		return array('long_text',$default,'long');
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
		$ret=new ocp_tempcode();
		foreach (($ev=='')?array():explode("\n",$ev) as $ev)
		{
			$ret->attach(paragraph(escape_html($ev)));
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
		if (is_null($actual_value)) $actual_value=''; // Plug anomaly due to unusual corruption
		return form_input_line_multi($_cf_name,$_cf_description,'field_'.strval($field['id']),explode("\n",$actual_value),($field['cf_required']==1)?1:0);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  ?string		Where the files will be uploaded to (NULL: do not store an upload, return NULL if we would need to do so)
	 * @param  ?string		Former value of field (NULL: none)
	 * @return ?string		The value (NULL: could not process)
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		$id=$field['id'];
		$i=0;
		$value='';
		do
		{
			$tmp_name='field_'.strval($id).'_'.strval($i);
			$_value=post_param($tmp_name,NULL);
			if ((is_null($_value)) && ($i==0)) return $editing?STRING_MAGIC_NULL:'';
			if (($_value!==NULL) && ($_value!=''))
			{
				if ($value!='') $value.="\n";
				$value.=$_value;
			}
			$i++;
		}
		while ($_value!==NULL);
		return $value;
	}
}


