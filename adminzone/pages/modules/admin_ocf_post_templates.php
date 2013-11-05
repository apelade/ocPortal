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
 * @package		ocf_post_templates
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ocf_post_templates extends standard_crud_module
{
	var $lang_type='POST_TEMPLATE';
	var $select_name='TITLE';
	var $table_prefix='t_';
	var $title_is_multi_lang=false;
	var $archive_entry_point='_SEARCH:forumview';
	var $archive_label='SECTION_FORUMS';
	var $menu_label='POST_TEMPLATES';
	var $table='f_post_templates';
	var $orderer='t_title';

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-pagelink rather than a screen-name).
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true)
	{
		return array('misc'=>'POST_TEMPLATES')+parent::get_entry_points();
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @param  boolean		Whether this is running at the top level, prior to having sub-objects called.
	 * @param  ?ID_TEXT		The screen type to consider for meta-data purposes (NULL: read from environment).
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run($top_level=true,$type=NULL)
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		set_helper_panel_tutorial('tut_forum_helpdesk');

		return parent::pre_run($top_level);
	}

	/**
	 * Standard crud_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$this->add_one_label=do_lang_tempcode('ADD_POST_TEMPLATE');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_POST_TEMPLATE');
		$this->edit_one_label=do_lang_tempcode('EDIT_POST_TEMPLATE');

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_general_action');
		require_code('ocf_general_action2');

		if ($type=='misc') return $this->misc();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		return do_next_manager(get_screen_title('POST_TEMPLATES'),comcode_lang_string('DOC_POST_TEMPLATES'),
			array(
				array('menu/_generic_admin/add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_POST_TEMPLATE')),
				array('menu/_generic_admin/edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_POST_TEMPLATE')),
			),
			do_lang('POST_TEMPLATES')
		);
	}

	/**
	 * Standard crud_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
	 */
	function create_selection_list_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','t_title ASC',true);
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			't_title'=>do_lang_tempcode('TITLE'),
		);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');

		$header_row=results_field_title(array(
			do_lang_tempcode('TITLE'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fields->attach(results_entry(array($row['t_title'],protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id']))))),true);
		}

		$search_url=NULL;
		$archive_url=NULL;

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false,$search_url,$archive_url);
	}

	/**
	 * Get tempcode for a post template adding/editing form.
	 *
	 * @param  SHORT_TEXT	The title (name) of the post template
	 * @param  LONG_TEXT		The actual post template text
	 * @param  SHORT_TEXT	Multi-code identifying forums it is applicable to
	 * @param  BINARY			Whether to use as the default post for applicable forums
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function get_form_fields($title='',$text='',$forum_multi_code='',$use_default_forums=0)
	{
		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_TITLE'),'title',$title,true));
		$fields->attach(form_input_text_comcode(do_lang_tempcode('_POST'),do_lang_tempcode('DESCRIPTION_POST_TEMPLATE_X'),'text',$text,true));
		$fields->attach(ocf_get_forum_multi_code_field($forum_multi_code));
		$fields->attach(form_input_tick(do_lang_tempcode('DEFAULT'),do_lang_tempcode('USE_AS_DEFAULT_ON_APPLICABLE_FORUMS'),'use_default_forums',$use_default_forums==1));

		return array($fields,new ocp_tempcode());
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['FORUM_DB']->query_select('f_post_templates',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		return $this->get_form_fields($r['t_title'],$r['t_text'],$r['t_forum_multi_code'],$r['t_use_default_forums']);
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		require_code('form_templates');
		return strval(ocf_make_post_template(post_param('title'),post_param('text'),read_multi_code('forum_multi_code'),post_param_integer('use_default_forums',0)));
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		require_code('form_templates');
		ocf_edit_post_template(intval($id),post_param('title'),post_param('text'),read_multi_code('forum_multi_code'),post_param_integer('use_default_forums',0));
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		ocf_delete_post_template(intval($id));
	}
}


