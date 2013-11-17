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
 * @package		news
 */

class Hook_checklist_blog
{
	/**
	 * Standard modular run function.
	 *
	 * @return array		An array of tuples: The task row to show, the number of seconds until it is due (or NULL if not on a timer), the number of things to sort out (or NULL if not on a queue), The name of the config option that controls the schedule (or NULL if no option).
	 */
	function run()
	{
		if (!addon_installed('news')) return array();

		if (get_option('blog_update_time')=='') return array();

		require_lang('news');

		$admin_groups=array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),$GLOBALS['FORUM_DRIVER']->get_moderator_groups());
		$staff=$GLOBALS['FORUM_DRIVER']->member_group_query(array_keys($admin_groups),100);
		if (count($staff)>=100) return array();
		$or_list='';
		foreach (array_keys($staff) as $staff_id)
		{
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='c.nc_owner='.strval($staff_id);
		}
		if ($or_list=='') return array();

		$query='SELECT MAX(date_and_time) FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news n JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_categories c ON n.news_category=c.id WHERE validated=1 AND ('.$or_list.')';
		$date=$GLOBALS['SITE_DB']->query_value_if_there($query);

		$limit_hours=intval(get_option('blog_update_time'));

		$seconds_ago=mixed();
		if (!is_null($date))
		{
			$status=($seconds_ago>$limit_hours*60*60)?0:1;
		} else
		{
			$status=0;
		}

		$_status=($status==0)?do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_0'):do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM_STATUS_1');

		require_code('config2');
		$config_url=config_option_url('blog_update_time');

		$url=build_url(array('page'=>'cms_blogs','type'=>'ad'),get_module_zone('cms_blogs'));
		list($info,$seconds_due_in)=staff_checklist_time_ago_and_due($seconds_ago,$limit_hours);
		$tpl=do_template('BLOCK_MAIN_STAFF_CHECKLIST_ITEM',array('_GUID'=>'a75d4a165aa5e16ad3aa06d2e0bab5db','CONFIG_URL'=>$config_url,'URL'=>$url,'STATUS'=>$_status,'TASK'=>do_lang_tempcode('BLOG'),'INFO'=>$info));
		return array(array($tpl,$seconds_due_in,NULL,'blog_update_time'));
	}
}


