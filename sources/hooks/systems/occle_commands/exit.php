<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    occle
 */

/**
 * Hook class.
 */
class Hook_occle_command_exit
{
    /**
     * Run function for OcCLE hooks.
     *
     * @param  array                    The options with which the command was called
     * @param  array                    The parameters with which the command was called
     * @param  object                   A reference to the OcCLE filesystem object
     * @return array                    Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$occle_fs)
    {
        require_code('xml');

        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('exit', array('h'), array()), '', '');
        } else {
            return array('if (document.getElementById(\'occle_box\')) load_occle(); else window.location.href=\'' . xmlentities(addslashes(static_evaluate_tempcode(build_url(array('page' => ''), '')))) . '\';', '', do_lang('SUCCESS'), '');
        }
    }
}
