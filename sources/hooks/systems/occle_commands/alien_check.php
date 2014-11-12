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
class Hook_occle_command_alien_check
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
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('alien_check', array('h'), array()), '', '');
        } else {
            require_code('upgrade');
            $master_data = @unserialize(file_get_contents(get_file_base() . '/data/files.dat'));
            if ($master_data === false) {
                $master_data = array();
            }
            $addon_files = collapse_2d_complexity('filename', 'addon_name', $GLOBALS['SITE_DB']->query_select('addons_files', array('filename', 'addon_name')));
            list($result,) = check_alien($addon_files, file_exists(get_file_base() . '/data/files_previous.dat') ? unserialize(file_get_contents(get_file_base() . '/data/files_previous.dat')) : array(), $master_data, get_file_base() . '/', '', true);
            if ($result == '') {
                $result = do_lang('NO_ACTION_REQUIRED');
            } else {
                require_lang('upgrade');
                $result .= do_lang('RM_HINT');
            }

            return array('', $result, '', '');
        }
    }
}
