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
 * @package		calendar
 */

class Hook_unvalidated_calendar
{
	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (!module_installed('calendar')) return NULL;

		require_lang('calendar');

		$info=array();
		$info['db_table']='calendar_events';
		$info['db_identifier']='id';
		$info['db_validated']='validated';
		$info['db_add_date']='e_add_date';
		$info['db_edit_date']='e_edit_date';
		$info['edit_module']='cms_calendar';
		$info['edit_type']='ed';
		$info['edit_identifier']='id';
		$info['title']=do_lang_tempcode('EVENT');

		return $info;
	}
}


