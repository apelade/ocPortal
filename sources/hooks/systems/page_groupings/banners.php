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
 * @package		banners
 */

class Hook_page_groupings_banners
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
		if (!addon_installed('banners')) return array();

		return array(
			array('cms','menu/cms/banners',array('cms_banners',array('type'=>'misc'),get_module_zone('cms_banners')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('BANNERS'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('banners','COUNT(*)',NULL,'',true))))),'banners:DOC_BANNERS'),
			array('audit','menu/cms/banners',array('admin_banners',array('type'=>'misc'),get_module_zone('admin_banners')),do_lang_tempcode('banners:BANNER_STATISTICS'),'banners:DOC_BANNERS'),
			(get_comcode_zone('donate',false)===NULL)?NULL:array('site_meta','menu/pages/donate',array('donate',array(),get_comcode_zone('donate')),do_lang_tempcode('banners:DONATE')),
			(get_comcode_zone('advertise',false)===NULL)?NULL:array('site_meta','menu/pages/advertise',array('advertise',array(),get_comcode_zone('advertise')),do_lang_tempcode('banners:ADVERTISE')),
			array('site_meta','menu/cms/banners',array('banners',array('type'=>'misc'),get_module_zone('banners')),do_lang_tempcode('banners:BANNERS')),
		);
	}
}


