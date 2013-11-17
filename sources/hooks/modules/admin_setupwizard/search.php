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
 * @package		search
 */

class Hook_sw_search
{
	/**
	 * Standard modular run function for blocks in the setup wizard.
	 *
	 * @return array		Map of block names, to display types.
	 */
	function get_blocks()
	{
		return array(array(),array('side_tag_cloud'=>array('PANEL_NONE','PANEL_RIGHT'),'main_search'=>array('PANEL_NONE','PANEL_RIGHT')));
	}
}


