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
 * @package		points
 */

class Hook_occle_command_give
{
	/**
	 * Standard modular run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  object	A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('give',array('h','a'),array(true,true,true)),'','');
		else
		{
			if (!array_key_exists(0,$parameters)) return array('','','',do_lang('MISSING_PARAM','1','give'));
			if (!array_key_exists(1,$parameters)) return array('','','',do_lang('MISSING_PARAM','2','give'));
			if (!array_key_exists(2,$parameters)) return array('','','',do_lang('MISSING_PARAM','3','give'));

			require_code('points2');

			$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($parameters[0]);

			give_points($parameters[1],$member_id,get_member(),$parameters[2],((array_key_exists('a',$options)) || (array_key_exists('anonymous',$options))));
			return array('','',do_lang('SUCCESS'),'');
		}
	}
}

