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
 * @package		core
 */

/**
 * Module page class.
 */
class Module_forums
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
		// Omitted due to being OCF
		if ((get_forum_type()=='ocf') || (get_forum_type()=='none'))
			return NULL;

		return array(
			'!'=>array('SECTION_FORUMS','menu/social/forums'),
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

		$base_url=get_forum_base_url();

		$forums=get_param('url',$base_url.'/');

		if (substr($forums,0,strlen($base_url))!=$base_url)
		{
			$GLOBALS['OUTPUT_STREAMING']=false; // Too complex to do a pre_run for this properly
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
		$base_url=get_forum_base_url();

		$forums=get_param('url',$base_url.'/');

		if (substr($forums,0,strlen($base_url))!=$base_url)
		{
			$base_url=rtrim($forums,'/');
			if ((strpos($base_url,'.php')!==false) || (strpos($base_url,'?')!==false)) $base_url=dirname($base_url);

			//log_hack_attack_and_exit('REFERRER_IFRAME_HACK'); No longer a hack attack becase people webmasters changed their forum base URL at some point, creating problems with old bookmarks!
			require_code('site2');
			smart_redirect(get_self_url(true,false,array('url'=>get_forum_base_url())));
		}

		$old_method=false;
		if ($old_method)
		{
			return do_template('FORUMS_EMBED',array('_GUID'=>'159575f6b83c5366d29e184a8dd5fc49','FORUMS'=>$forums));
		}

		$GLOBALS['SCREEN_TEMPLATE_CALLED']='';

		require_code('integrator');
		return do_template('COMCODE_SURROUND',array('_GUID'=>'4d5a8ce37df94f7d61f1a96f5689b9c0','CLASS'=>'float_surrounder','CONTENT'=>protect_from_escaping(reprocess_url($forums,$base_url))));
	}

}


