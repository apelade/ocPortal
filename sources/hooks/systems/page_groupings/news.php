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

class Hook_page_groupings_news
{
	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @param  ?MEMBER		Member ID to run as (NULL: current member)
	 * @param  boolean		Whether to use extensive documentation tooltips, rather than short summaries
	 * @return array			List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
	 */
	function run($member_id=NULL,$extensive_docs=false)
	{
		if (!addon_installed('news')) return array();

		$cnt=$GLOBALS['SITE_DB']->query_select_value_if_there('news','COUNT(*)',NULL,'',true);
		$cnt_blogs=$cnt-$GLOBALS['SITE_DB']->query_select_value_if_there('news n LEFT JOIN '.get_table_prefix().'news_categories c ON c.id=n.news_category','COUNT(*)',array('nc_owner'=>NULL),'',true);

		return array(
			array('cms','menu/rich_content/news',array('cms_news',array('type'=>'misc'),get_module_zone('cms_news')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('NEWS'),make_string_tempcode(escape_html(integer_format($cnt)))),'news:DOC_NEWS'),
			array('cms','tabs/member_account/blog',array('cms_blogs',array('type'=>'misc'),get_module_zone('cms_blogs')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('news:BLOGS'),make_string_tempcode(escape_html(integer_format($cnt_blogs)))),'news:DOC_BLOGS'),
			array('rich_content','menu/rich_content/news',array('news',array(),get_module_zone('news')),do_lang_tempcode('NEWS')),
		);
	}
}


