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
 * @package		core_fields
 */

class Hook_fields_posting_field
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
		return array('long_trans',$default,'long_trans');
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
		return escape_html($ev);
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
	 * @param  boolean		Whether this is the last field in the catalogue
	 * @return ?tempcode		The Tempcode for the input field (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new,$last=true)
	{
		if (is_null($actual_value)) $actual_value=''; // Plug anomaly due to unusual corruption

		require_lang('javascript');
		require_javascript('javascript_posting');
		require_javascript('javascript_editing');
		require_javascript('javascript_ajax');
		require_javascript('javascript_swfupload');
		require_css('swfupload');

		require_lang('comcode');

		$tabindex=get_form_field_tabindex();

		$actual_value=filter_form_field_default($_cf_name,$actual_value);

		list($attachments,$attach_size_field)=get_attachments('field_'.strval($field['id']));

		$hidden_fields=new ocp_tempcode();
		$hidden_fields->attach($attach_size_field);

		$help_zone=get_comcode_zone('userguide_comcode',false);

		$emoticon_chooser=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser('field_'.strval($field['id']));

		$comcode_editor=get_comcode_editor('field_'.strval($field['id']));
		$comcode_editor_small=get_comcode_editor('field_'.strval($field['id']),true);

		$w=(has_js()) && (browser_matches('wysiwyg') && (strpos($actual_value,'{$,page hint: no_wysiwyg}')===false));

		$class='';
		attach_wysiwyg();
		if ($w) $class.=' wysiwyg';

		global $LAX_COMCODE;
		$temp=$LAX_COMCODE;
		$LAX_COMCODE=true;
		$GLOBALS['COMCODE_PARSE_URLS_CHECKED']=100; // Little hack to stop it checking any URLs
		/*We want to always reparse with semi-parse mode if (is_null($default_parsed)) */$default_parsed=comcode_to_tempcode($actual_value,NULL,false,60,NULL,NULL,true);
		$LAX_COMCODE=$temp;

		$attachments_done=true;

		$ret=do_template('POSTING_FIELD',array(
			'_GUID'=>'b6c65227a28e0650154393033e005f67',
			'REQUIRED'=>($field['cf_required']==1),
			'DESCRIPTION'=>$_cf_description,
			'HIDDEN_FIELDS'=>$hidden_fields,
			'PRETTY_NAME'=>$_cf_name,
			'NAME'=>'field_'.strval($field['id']),
			'TABINDEX_PF'=>strval($tabindex)/*not called TABINDEX due to conflict with FORM_STANDARD_END*/,
			'COMCODE_EDITOR'=>$comcode_editor,
			'COMCODE_EDITOR_SMALL'=>$comcode_editor_small,
			'CLASS'=>$class,
			'COMCODE_URL'=>is_null($help_zone)?new ocp_tempcode():build_url(array('page'=>'userguide_comcode'),$help_zone),
			'EMOTICON_CHOOSER'=>$emoticon_chooser,
			'POST'=>$actual_value,
			'DEFAULT_PARSED'=>$default_parsed,
			'ATTACHMENTS'=>$attachments,
		));

		if (!$last)
		{
			$ret->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'168edca41bd0c3da936d9154d696163e','TITLE'=>do_lang_tempcode('ADDITIONAL_INFO'))));
		}

		return $ret;
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
		$tmp_name='field_'.strval($id);
		return post_param($tmp_name,STRING_MAGIC_NULL);
	}

}


