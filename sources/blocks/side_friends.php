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
 * @package		chat
 */

class Block_side_friends
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
		$info['parameters']=array('max');
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		if (is_guest()) return new ocp_tempcode(); // Guest has no friends

		if ((get_page_name()=='chat') && (get_param('type','misc')=='misc')) // Don't want to show if actually on chat lobby, which already has this functionality
			return new ocp_tempcode();

		require_code('chat');
		require_code('chat_lobby');
		require_lang('chat');
		require_css('chat');
		require_javascript('javascript_chat');

		$max=array_key_exists('max',$map)?intval($map['max']):15;

		$friends=show_im_contacts(NULL,true,$max);

		return do_template('BLOCK_SIDE_FRIENDS',array('_GUID'=>'ce94db14f9a212f38d0fce1658866e2c','FRIENDS'=>$friends));
	}
}
