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
 * @package		bookmarks
 */

/**
 * Module page class.
 */
class Module_bookmarks
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
		$info['version']=2;
		$info['locked']=true;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('bookmarks');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('bookmarks',array(
			'id'=>'*AUTO',
			'b_owner'=>'MEMBER',
			'b_folder'=>'SHORT_TEXT',
			'b_title'=>'SHORT_TEXT',
			'b_page_link'=>'SHORT_TEXT',
		));
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		if ($check_perms && is_guest($member_id)) return array();
		return array(
			'misc'=>array('MANAGE_BOOKMARKS','menu/site_meta/bookmarks'),
			'ad'=>array('ADD_BOOKMARK','menu/_generic_admin/add_one'),
		);
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

		require_lang('bookmarks');

		if ($type=='misc' || $type=='_manage')
		{
			$this->title=get_screen_title('MANAGE_BOOKMARKS');
		}

		if ($type=='ad' || $type=='_ad')
		{
			$this->title=get_screen_title('ADD_BOOKMARK');
		}

		if ($type=='_edit')
		{
			$this->title=get_screen_title('EDIT_BOOKMARK');
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('bookmarks');
		require_css('bookmarks');

		if (is_guest()) access_denied('NOT_AS_GUEST');

		// Decide what we're doing
		$type=get_param('type','misc');

		if ($type=='misc') return $this->manage_bookmarks();
		if ($type=='_manage') return $this->_manage_bookmarks();
		if ($type=='ad') return $this->ad();
		if ($type=='_ad') return $this->_ad();
		if ($type=='_edit') return $this->_edit_bookmark();

		return new ocp_tempcode();
	}

	/**
	 * The UI to manage bookmarks.
	 *
	 * @return tempcode		The UI
	 */
	function manage_bookmarks()
	{
		require_code('form_templates');
		require_lang('zones');

		$fields=new ocp_tempcode();
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'2efc21de71434c715f920c7dbd14e687','TITLE'=>do_lang_tempcode('MOVE'))));
		$rows=$GLOBALS['SITE_DB']->query_select('bookmarks',array('DISTINCT b_folder'),array('b_owner'=>get_member()),'ORDER BY b_folder');
		$list=form_input_list_entry('',false,do_lang_tempcode('NA_EM'));
		$list->attach(form_input_list_entry('!',false,do_lang_tempcode('ROOT_EM')));
		foreach ($rows as $row)
		{
			if ($row['b_folder']!='') $list->attach(form_input_list_entry($row['b_folder']));
		}

		$set_name='choose_folder';
		$required=true;
		$set_title=do_lang_tempcode('BOOKMARK_FOLDER');
		$field_set=alternate_fields_set__start($set_name);

		$field_set->attach(form_input_list(do_lang_tempcode('EXISTING'),do_lang_tempcode('DESCRIPTION_OLD_BOOKMARK_FOLDER'),'folder',$list,NULL,false,false));

		$field_set->attach(form_input_line(do_lang_tempcode('NEW'),do_lang_tempcode('DESCRIPTION_NEW_BOOKMARK_FOLDER'),'folder_new','',false));

		$fields->attach(alternate_fields_set__end($set_name,$set_title,'',$field_set,$required));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('_GUID'=>'ec1bb050b1a6b31a8c2774c6994f3fb2','TITLE'=>do_lang_tempcode('ACTIONS'))));
		$fields->attach(form_input_tick(do_lang_tempcode('DELETE'),do_lang_tempcode('DESCRIPTION_DELETE'),'delete',false));
		$post_url=build_url(array('page'=>'_SELF','type'=>'_manage'),'_SELF');
		$form=do_template('FORM',array('_GUID'=>'5d9a17c5be18674991c3b17a4a4e7bfe','HIDDEN'=>'','FIELDS'=>$fields,'TEXT'=>'','URL'=>$post_url,'SUBMIT_NAME'=>do_lang_tempcode('MOVE_OR_DELETE_BOOKMARKS')));

		$bookmarks=array();
		$_bookmarks=$GLOBALS['SITE_DB']->query_select('bookmarks',array('*'),array('b_owner'=>get_member()),'ORDER BY b_folder');
		foreach ($_bookmarks as $bookmark)
		{
			$bookmarks[]=array('ID'=>strval($bookmark['id']),'CAPTION'=>$bookmark['b_title'],'FOLDER'=>$bookmark['b_folder'],'PAGE_LINK'=>$bookmark['b_page_link']);
		}

		return do_template('BOOKMARKS_SCREEN',array('_GUID'=>'685f020d6407543271ce99b5775bb357','TITLE'=>$this->title,'FORM_URL'=>$post_url,'FORM'=>$form,'BOOKMARKS'=>$bookmarks));
	}

	/**
	 * The actualiser to manage bookmarks.
	 *
	 * @return tempcode		The UI
	 */
	function _manage_bookmarks()
	{
		$bookmarks=$GLOBALS['SITE_DB']->query_select('bookmarks',array('id'),array('b_owner'=>get_member()));
		if (post_param('delete','')!='') // A delete
		{
			foreach ($bookmarks as $bookmark)
			{
				if (get_param_integer('bookmark_'.$bookmark['id'],0)==1)
				{
					$GLOBALS['SITE_DB']->query_delete('bookmarks',array('id'=>$bookmark['id']),'',1);
				}
			}
		} else // Otherwise it's a move
		{
			$folder=post_param('folder_new','');
			if ($folder=='') $folder=post_param('folder');
			if ($folder=='!') $folder='';

			foreach ($bookmarks as $bookmark)
			{
				if (get_param_integer('bookmark_'.$bookmark['id'],0)==1)
				{
					$GLOBALS['SITE_DB']->query_update('bookmarks',array('b_folder'=>$folder),array('id'=>$bookmark['id']),'',1);
				}
			}
		}

		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI to add a bookmark.
	 *
	 * @return tempcode		The UI
	 */
	function ad()
	{
		require_code('form_templates');

		url_default_parameters__enable();
		$ret=add_bookmark_form(build_url(array('page'=>'_SELF','type'=>'_ad','do_redirect'=>(get_param_integer('no_redirect',0)==0)?'1':'0'),'_SELF'));
		url_default_parameters__disable();
		return $ret;
	}

	/**
	 * The actualiser to add a bookmark.
	 *
	 * @return tempcode		The UI
	 */
	function _ad()
	{
		$folder=post_param('folder_new','');
		if ($folder=='') $folder=post_param('folder');
		if ($folder=='!') $folder='';

		add_bookmark(get_member(),$folder,post_param('title'),post_param('page_link'));

		if (get_param_integer('do_redirect')==1)
		{
			$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
			return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
		} else
		{
			return inform_screen($this->title,do_lang_tempcode('SUCCESS'));
		}
	}

	/**
	 * The actualiser to edit a bookmark.
	 *
	 * @return tempcode		The UI
	 */
	function _edit_bookmark()
	{
		$id=get_param_integer('id');

		if (post_param('delete',NULL)!==NULL) // A delete
		{
			$member=get_member();
			delete_bookmark($id,$member);
		} else
		{
			$caption=post_param('caption');
			$page_link=post_param('page_link');
			$member=get_member();
			edit_bookmark($id,$member,$caption,$page_link);
		}

		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}
}


