<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 */

class Hook_page_groupings_ocgifts
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
		require_lang('ocgifts');

		return array(
			array('setup','menu/ocgifts',array('admin_ocgifts',array(),get_page_zone('admin_ocgifts')),do_lang_tempcode('ocgifts:MANAGE_GIFTS'),'ocgifts:DOC_OCGIFTS'),
		);
	}
}


