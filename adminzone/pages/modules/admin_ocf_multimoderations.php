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
 * @package		ocf_multi_moderations
 */

require_code('crud_module');

/**
 * Module page class.
 */
class Module_admin_ocf_multimoderations extends standard_crud_module
{
	var $lang_type='MULTI_MODERATION';
	var $select_name='NAME';
	var $archive_entry_point='_SEARCH:forumview';
	var $archive_label='SECTION_FORUMS';
	var $menu_label='MULTI_MODERATIONS';
	var $table='f_multi_moderations';
	var $orderer='mm_name';
	var $title_is_multi_lang=true;

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
		return array('misc'=>'MULTI_MODERATIONS')+parent::get_entry_points();
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
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_moderation_action');
		require_code('ocf_moderation_action2');
		require_code('ocf_general_action2');

		$this->add_one_label=do_lang_tempcode('ADD_MULTI_MODERATION');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_MULTI_MODERATION');
		$this->edit_one_label=do_lang_tempcode('EDIT_MULTI_MODERATION');

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
		return do_next_manager(get_screen_title('MULTI_MODERATIONS'),comcode_lang_string('DOC_MULTI_MODERATIONS'),
			array(
				array('menu/_generic_admin/add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_MULTI_MODERATION')),
				array('menu/_generic_admin/edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_MULTI_MODERATION')),
			),
			do_lang('MULTI_MODERATIONS')
		);
	}

	/**
	 * Get tempcode for adding/editing form.
	 *
	 * @param  SHORT_TEXT	The name of the multi moderation
	 * @param  LONG_TEXT		The text to place as a post in the topic when the multi moderation is performed
	 * @param  ?AUTO_LINK	Move the topic to this forum (NULL: don't move)
	 * @param  ?BINARY		What to change the pin state to (NULL: don't change)
	 * @param  ?BINARY		What to change the open state to (NULL: don't change)
	 * @param  ?BINARY		What to change the sink state to (NULL: don't change)
	 * @param  SHORT_TEXT	The forum multicode identifying where the multimoderation is applicable
	 * @param  SHORT_TEXT	The title suffix
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function get_form_fields($name='',$post_text='',$move_to=NULL,$pin_state=NULL,$open_state=NULL,$sink_state=NULL,$forum_multi_code='*',$title_suffix='')
	{
		require_code('ocf_forums2');

		$fields=new ocp_tempcode();
		$fields->attach(form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_NAME'),'name',$name,true));
		$fields->attach(form_input_text_comcode(do_lang_tempcode('_POST'),do_lang_tempcode('DESCRIPTION_MULTI_MODERATION_POST'),'post_text',$post_text,false));
		$fields->attach(form_input_tree_list(do_lang_tempcode('DESTINATION'),do_lang_tempcode('DESCRIPTION_DESTINATION_FORUM'),'move_to',NULL,'choose_forum',array(),false,is_null($move_to)?NULL:strval($move_to)));
		$pin_state_list=new ocp_tempcode();
		$pin_state_list->attach(form_input_radio_entry('pin_state','-1',is_null($pin_state),do_lang_tempcode('NA_EM')));
		$pin_state_list->attach(form_input_radio_entry('pin_state','0',$pin_state===0,do_lang_tempcode('UNPIN_TOPIC')));
		$pin_state_list->attach(form_input_radio_entry('pin_state','1',$pin_state===1,do_lang_tempcode('PIN_TOPIC')));
		$fields->attach(form_input_radio(do_lang_tempcode('PIN_STATE'),do_lang_tempcode('DESCRIPTION_PIN_STATE'),'pin_state',$pin_state_list));
		$open_state_list=new ocp_tempcode();
		$open_state_list->attach(form_input_radio_entry('open_state','-1',is_null($open_state),do_lang_tempcode('NA_EM')));
		$open_state_list->attach(form_input_radio_entry('open_state','0',$open_state===0,do_lang_tempcode('CLOSE_TOPIC')));
		$open_state_list->attach(form_input_radio_entry('open_state','1',$open_state===1,do_lang_tempcode('OPEN_TOPIC')));
		$fields->attach(form_input_radio(do_lang_tempcode('OPEN_STATE'),do_lang_tempcode('DESCRIPTION_OPEN_STATE'),'open_state',$open_state_list));
		$sink_state_list=new ocp_tempcode();
		$sink_state_list->attach(form_input_radio_entry('sink_state','-1',is_null($sink_state),do_lang_tempcode('NA_EM')));
		$sink_state_list->attach(form_input_radio_entry('sink_state','0',$sink_state===0,do_lang_tempcode('SINK_TOPIC')));
		$sink_state_list->attach(form_input_radio_entry('sink_state','1',$sink_state===1,do_lang_tempcode('UNSINK_TOPIC')));
		$fields->attach(form_input_radio(do_lang_tempcode('SINK_STATE'),do_lang_tempcode('DESCRIPTION_SINK_STATE'),'sink_state',$sink_state_list));
		$fields->attach(ocf_get_forum_multi_code_field($forum_multi_code));
		$fields->attach(form_input_line(do_lang_tempcode('TITLE_SUFFIX'),do_lang_tempcode('DESCRIPTION_TITLE_SUFFIX'),'title_suffix',$title_suffix,false));

		return array($fields,new ocp_tempcode());
	}

	/**
	 * Standard crud_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A pair: The choose table, Whether re-ordering is supported from this screen.
	 */
	function create_selection_list_choose_table($url_map)
	{
		require_code('templates_results_table');

		$current_ordering=get_param('sort','mm_name ASC');
		if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		list($sortable,$sort_order)=explode(' ',$current_ordering,2);
		$sortables=array(
			'mm_name'=>do_lang_tempcode('NAME'),
			'mm_pin_state'=>do_lang_tempcode('PIN_STATE'),
			'mm_open_state'=>do_lang_tempcode('OPEN_STATE'),
			'mm_sink_state'=>do_lang_tempcode('SINK_STATE'),
		);
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');

		$header_row=results_field_title(array(
			do_lang_tempcode('NAME'),
			do_lang_tempcode('DESTINATION'),
			do_lang_tempcode('PIN_STATE'),
			do_lang_tempcode('OPEN_STATE'),
			do_lang_tempcode('SINK_STATE'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering);
		foreach ($rows as $row)
		{
			$pin_state=do_lang_tempcode('NA_EM');
			if (!is_null($row['mm_pin_state']))
			{
				switch ($row['mm_pin_state'])
				{
					case 0:
						$pin_state=do_lang_tempcode('UNPIN_TOPIC');
						break;
					case 1:
						$pin_state=do_lang_tempcode('PIN_TOPIC');
						break;
				}
			}
			$open_state=do_lang_tempcode('NA_EM');
			if (!is_null($row['mm_open_state']))
			{
				switch ($row['mm_open_state'])
				{
					case 0:
						$open_state=do_lang_tempcode('CLOSE_TOPIC');
						break;
					case 1:
						$open_state=do_lang_tempcode('OPEN_TOPIC');
						break;
				}
			}
			$sink_state=do_lang_tempcode('NA_EM');
			if (!is_null($row['mm_sink_state']))
			{
				switch ($row['mm_sink_state'])
				{
					case 0:
						$sink_state=do_lang_tempcode('SINK_TOPIC');
						break;
					case 1:
						$sink_state=do_lang_tempcode('UNSINK_TOPIC');
						break;
				}
			}

			$destination=is_null($row['mm_move_to'])?NULL:$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums','f_name',array('id'=>$row['mm_move_to']));
			if (is_null($destination)) $destination=do_lang_tempcode('NA_EM');

			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			$fields->attach(results_entry(array(get_translated_text($row['mm_name'],$GLOBALS['FORUM_DB']),$destination,$pin_state,$open_state,$sink_state,protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id']))))),true);
		}

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order),false);
	}

	/**
	 * Standard crud_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function create_selection_list_entries()
	{
		$_m=$GLOBALS['FORUM_DB']->query_select('f_multi_moderations',array('id','mm_name'));
		$entries=new ocp_tempcode();
		foreach ($_m as $m)
		{
			$entries->attach(form_input_list_entry(strval($m['id']),false,get_translated_text($m['mm_name'],$GLOBALS['FORUM_DB'])));
		}

		return $entries;
	}

	/**
	 * Standard crud_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function fill_in_edit_form($id)
	{
		$m=$GLOBALS['FORUM_DB']->query_select('f_multi_moderations',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$m)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$r=$m[0];

		return $this->get_form_fields(get_translated_text($r['mm_name'],$GLOBALS['FORUM_DB']),$r['mm_post_text'],$r['mm_move_to'],$r['mm_pin_state'],$r['mm_open_state'],$r['mm_sink_state'],$r['mm_forum_multi_code'],$r['mm_title_suffix']);
	}

	/**
	 * Standard crud_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		$pin_state=mixed();
		$pin_state=post_param_integer('pin_state',0);
		if ($pin_state==-1) $pin_state=NULL;

		$sink_state=mixed();
		$sink_state=post_param_integer('sink_state',0);
		if ($sink_state==-1) $sink_state=NULL;

		$open_state=mixed();
		$open_state=post_param_integer('open_state',0);
		if ($open_state==-1) $open_state=NULL;

		require_code('form_templates');
		return strval(ocf_make_multi_moderation(post_param('name'),post_param('post_text'),post_param_integer('move_to',NULL),$pin_state,$sink_state,$open_state,read_multi_code('forum_multi_code'),post_param('title_suffix')));
	}

	/**
	 * Standard crud_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 */
	function edit_actualisation($id)
	{
		$pin_state=mixed();
		$pin_state=post_param_integer('pin_state',0);
		if ($pin_state==-1) $pin_state=NULL;

		$sink_state=mixed();
		$sink_state=post_param_integer('sink_state',0);
		if ($sink_state==-1) $sink_state=NULL;

		$open_state=mixed();
		$open_state=post_param_integer('open_state',0);
		if ($open_state==-1) $open_state=NULL;

		require_code('form_templates');
		ocf_edit_multi_moderation(intval($id),post_param('name'),post_param('post_text'),post_param_integer('move_to',NULL),$pin_state,$sink_state,$open_state,read_multi_code('forum_multi_code'),post_param('title_suffix'));
	}

	/**
	 * Standard crud_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		ocf_delete_multi_moderation(intval($id));
	}

}


