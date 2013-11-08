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
 * @package		content_reviews
 */

/**
 * Module page class.
 */
class Module_admin_content_reviews
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-pagelink rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		return array(
			'!'=>array('_CONTENT_NEEDING_REVIEWING','menu/adminzone/audit/content_reviews'),
		);
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('content_reviews');

		delete_privilege('set_content_review_settings');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		add_privilege('SUBMISSION','set_content_review_settings',false);

		$GLOBALS['SITE_DB']->create_table('content_reviews',array(
			'content_type'=>'*ID_TEXT',
			'content_id'=>'*ID_TEXT',
			'review_freq'=>'?INTEGER',
			'next_review_time'=>'TIME',
			'auto_action'=>'ID_TEXT', // leave|unvalidate|delete
			'review_notification_happened'=>'BINARY',
			'display_review_status'=>'BINARY',
			'last_reviewed_time'=>'TIME',
		));
		$GLOBALS['SITE_DB']->create_index('content_reviews','next_review_time',array('next_review_time','review_notification_happened'));
		$GLOBALS['SITE_DB']->create_index('content_reviews','needs_review',array('next_review_time','content_type'));
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('content_reviews');

		set_helper_panel_text(comcode_lang_string('DOC_CONTENT_REVIEWS'));

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$_title=get_screen_title('_CONTENT_NEEDING_REVIEWING');

		require_code('content');

		$out=new ocp_tempcode();
		require_code('form_templates');

		$_hooks=find_all_hooks('systems','content_meta_aware');
		foreach (array_keys($_hooks) as $content_type)
		{
			require_code('content');
			$object=get_content_object($content_type);
			if (is_null($object)) continue;
			$info=$object->info();
			if (is_null($info)) continue;

			if (is_null($info['edit_pagelink_pattern'])) continue;

			$content=new ocp_tempcode();
			$content_ids=collapse_1d_complexity('content_id',$GLOBALS['SITE_DB']->query('SELECT content_id FROM '.get_table_prefix().'content_reviews WHERE '.db_string_equal_to('content_type',$content_type).' AND next_review_time<='.strval(time()),100));
			foreach ($content_ids as $content_id)
			{
				list($title,)=content_get_details($content_type,$content_id);
				if (!is_null($title))
				{
					$content->attach(form_input_list_entry($content_id,false,strip_comcode($title)));
				} else
				{
					$GLOBALS['SITE_DB']->query_delete('content_reviews',array('content_type'=>$content_id,'content_id'=>$content_id),'',1); // The actual content was deleted, I guess
					continue;
				}
			}
			if (count($content_ids)==100) attach_message(do_lang_tempcode('TOO_MANY_TO_CHOOSE_FROM'),'warn');

			if (!$content->is_empty())
			{
				list($zone,$attributes,)=page_link_decode($info['edit_pagelink_pattern']);
				$edit_identifier='id';
				foreach ($attributes as $key=>$val)
				{
					if ($val=='_WILD')
					{
						$edit_identifier=$key;
						unset($attributes[$key]);
						break;
					}
				}
				$post_url=build_url($attributes+array('redirect'=>get_self_url(true)),$zone);
				$fields=form_input_list(do_lang_tempcode('CONTENT'),'',$edit_identifier,$content,NULL,true);

				// Could debate whether to include "'TARGET'=>'_blank',". However it does redirect back, so it's a nice linear process like this. If it was new window it could be more efficient, but also would confuse people with a lot of new windows opening and not closing.
				$content=do_template('FORM',array('_GUID'=>'288c2534a75e5af5bc7155594dfef68f','SKIP_REQUIRED'=>true,'GET'=>true,'HIDDEN'=>'','SUBMIT_NAME'=>do_lang_tempcode('EDIT'),'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>''));

				$out->attach(do_template('UNVALIDATED_SECTION',array('_GUID'=>'406d4c0a8abd36b9c88645df84692c7d','TITLE'=>do_lang_tempcode($info['content_type_label']),'CONTENT'=>$content)));
			}
		}

		return do_template('UNVALIDATED_SCREEN',array('_GUID'=>'c8574404597d25e3c027766c74d1a008','TITLE'=>$_title,'SECTIONS'=>$out));
	}

}

