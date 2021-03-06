<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		quizzes
 */


class Hook_do_next_menus_quiz
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		if (!addon_installed('quizzes')) return array();

		return array(
			array('usage','quiz',array('admin_quiz',array('type'=>'misc'),get_module_zone('admin_quiz')),do_lang_tempcode('QUIZZES'),('DOC_QUIZZES')),
			array('cms','quiz',array('cms_quiz',array('type'=>'misc'),get_module_zone('cms_quiz')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('QUIZZES'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_value_null_ok('quizzes','COUNT(*)',NULL,'',true))))),('DOC_QUIZZES')),
		);
	}

}


