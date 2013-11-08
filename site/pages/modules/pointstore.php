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
 * @package		pointstore
 */

/**
 * Module page class.
 */
class Module_pointstore
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Allen Ellis';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=6;
		$info['locked']=false;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('prices');
		$GLOBALS['SITE_DB']->drop_table_if_exists('sales');
		$GLOBALS['SITE_DB']->drop_table_if_exists('pstore_customs');
		$GLOBALS['SITE_DB']->drop_table_if_exists('pstore_permissions');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			$GLOBALS['SITE_DB']->create_table('prices',array(
				'name'=>'*ID_TEXT',
				'price'=>'INTEGER'
			));

			$GLOBALS['SITE_DB']->create_table('sales',array(
				'id'=>'*AUTO',
				'date_and_time'=>'TIME',
				'memberid'=>'MEMBER',
				'purchasetype'=>'ID_TEXT',
				'details'=>'SHORT_TEXT',
				'details2'=>'SHORT_TEXT'
			));

			// Custom
				$GLOBALS['SITE_DB']->create_table('pstore_customs',array(
					'id'=>'*AUTO',
					'c_title'=>'SHORT_TRANS',
					'c_description'=>'LONG_TRANS',
					'c_mail_subject'=>'SHORT_TRANS',
					'c_mail_body'=>'LONG_TRANS',
					'c_enabled'=>'BINARY',
					'c_cost'=>'INTEGER',
					'c_one_per_member'=>'BINARY',
				));
			// Permissions
				$GLOBALS['SITE_DB']->create_table('pstore_permissions',array(
					'id'=>'*AUTO',
					'p_title'=>'SHORT_TRANS',
					'p_description'=>'LONG_TRANS',
					'p_mail_subject'=>'SHORT_TRANS',
					'p_mail_body'=>'LONG_TRANS',
					'p_enabled'=>'BINARY',
					'p_cost'=>'INTEGER',
					'p_hours'=>'?INTEGER',
					'p_type'=>'ID_TEXT', // member_privileges,member_category_access,member_page_access,member_zone_access
					'p_privilege'=>'ID_TEXT', // privilege only
					'p_zone'=>'ID_TEXT', // zone and page only
					'p_page'=>'ID_TEXT', // page and ?privilege only
					'p_module'=>'ID_TEXT', // category and ?privilege only
					'p_category'=>'ID_TEXT', // category and ?privilege only
				));
		}

		if (($upgrade_from<5) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->add_table_field('pstore_permissions','p_mail_subject','SHORT_TRANS');
			$GLOBALS['SITE_DB']->add_table_field('pstore_permissions','p_mail_body','LONG_TRANS');

			$GLOBALS['SITE_DB']->add_table_field('pstore_customs','c_mail_subject','SHORT_TRANS');
			$GLOBALS['SITE_DB']->add_table_field('pstore_customs','c_mail_body','LONG_TRANS');
		}

		if (($upgrade_from<6) && (!is_null($upgrade_from)))
		{
			rename_config_option('text','community_billboard');
			rename_config_option('is_on_flagrant_buy','is_on_community_billboard_buy');

			$GLOBALS['SITE_DB']->alter_table_field('pstore_permissions','p_hours','?INTEGER');
		}
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
		if (!$check_perms || !is_guest($member_id))
		{
			return array(
				'!'=>array('POINTSTORE','menu/social/pointstore'),
			);
		}
		return array();
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

		require_lang('pointstore');

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('POINTSTORE'))));

		$this->title=get_screen_title('POINTSTORE');

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_code('pointstore');
		require_lang('points');
		require_code('points');
		require_css('points');

		$type=get_param('type','misc');
		$hook=get_param('id','');

		// Not logged in
		if (is_guest())
		{
			access_denied('NOT_AS_GUEST');
		}

		if ($hook!='')
		{
			require_code('hooks/modules/pointstore/'.filter_naughty_harsh($hook),true);
			$object=object_factory('Hook_pointstore_'.filter_naughty_harsh($hook));
			$object->init();
			if (method_exists($object,$type))
			{
				require_code('form_templates');

				url_default_parameters__enable();
				$ret=call_user_func(array($object,$type));
				url_default_parameters__disable();
				return $ret;
			}
		}

		if ($type=='misc') return $this->do_module_gui();
		return new ocp_tempcode();
	}

	/**
	 * The UI to choose a section of the Point Store.
	 *
	 * @return tempcode		The UI
	 */
	function do_module_gui()
	{
		$points_left=available_points(get_member());

		$items=new ocp_tempcode();

		$_hooks=find_all_hooks('modules','pointstore');
		foreach (array_keys($_hooks) as $hook)
		{
			require_code('hooks/modules/pointstore/'.filter_naughty_harsh($hook),true);
			$object=object_factory('Hook_pointstore_'.filter_naughty_harsh($hook),true);
			if (is_null($object)) continue;
			$object->init();
			$tpls=$object->info();
			foreach ($tpls as $tpl)
			{
				$item=do_template('POINTSTORE_ITEM',array('_GUID'=>'1316f918b3c19331d5d8e55402a7ae45','ITEM'=>$tpl));
				$items->attach($item);
			}
		}

		if (get_option('is_on_forw_buy')=='1')
		{
			$forwarding_url=build_url(array('page'=>'_SELF','type'=>'newforwarding','id'=>'forwarding'),'_SELF');

			if ($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'prices WHERE name LIKE \''.db_encode_like('forw_%').'\'')>0)
				$_pointstore_mail_forwarding_link=$forwarding_url;
			else $_pointstore_mail_forwarding_link=NULL;
			$pointstore_mail_forwarding_link=do_template('POINTSTORE_MFORWARDING_LINK',array('_GUID'=>'e93666809dc3e47e3660245711f545ee','FORWARDING_URL'=>$_pointstore_mail_forwarding_link));

		} else $pointstore_mail_forwarding_link=new ocp_tempcode();
		if (get_option('is_on_pop3_buy')=='1')
		{
			$pop3_url=build_url(array('page'=>'_SELF','type'=>'pop3info','id'=>'pop3'),'_SELF');

			if ($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'prices WHERE name LIKE \''.db_encode_like('pop3_%').'\'')>0)
				$_pointstore_mail_pop3_link=$pop3_url;
			else $_pointstore_mail_pop3_link=NULL;
			$pointstore_mail_pop3_link=do_template('POINTSTORE_MPOP3_LINK',array('_GUID'=>'42925a17262704450e451ad8502bce0d','POP3_URL'=>$_pointstore_mail_pop3_link));

		} else $pointstore_mail_pop3_link=new ocp_tempcode();

		if ((!$pointstore_mail_pop3_link->is_empty()) || (!$pointstore_mail_pop3_link->is_empty()))
		{
			$mail_tpl=do_template('POINTSTORE_MAIL',array('_GUID'=>'4a024f39a4065197b2268ecd2923b8d6','POINTSTORE_MAIL_POP3_LINK'=>$pointstore_mail_pop3_link,'POINTSTORE_MAIL_FORWARDING_LINK'=>$pointstore_mail_forwarding_link));
			$items->attach(do_template('POINTSTORE_ITEM',array('_GUID'=>'815b00b651757d4052cb494ed6a8d926','ITEM'=>$mail_tpl)));
		}

		$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		return do_template('POINTSTORE_SCREEN',array('_GUID'=>'1b66923dd1a3da6afb934a07909b8aa7','TITLE'=>$this->title,'ITEMS'=>$items,'POINTS_LEFT'=>integer_format($points_left),'USERNAME'=>$username));
	}

}


