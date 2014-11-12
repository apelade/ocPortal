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
class Hook_occle_command_find_entry_points
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
            return array('', do_command_help('find_entry_points', array('h'), array(true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'find_entry_points'));
            }

            // NOTE: this code assumes the search-string is contained within the zone:page portion of the entry point, not any part of the parameterisation
            $entry_points = array();
            $zones = find_all_zones();
            require_all_lang();
            foreach ($zones as $zone) {
                $pages = find_all_pages_wrap($zone);
                foreach ($pages as $page => $type) {
                    if (strpos($zone . ':' . $page, $parameters[0]) !== false) {
                        if (($type == 'modules') || ($type == 'modules_custom')) {
                            require_code(zone_black_magic_filterer(filter_naughty_harsh($zone) . '/pages/' . filter_naughty_harsh($type) . '/' . filter_naughty_harsh($page) . '.php'));

                            if (class_exists('Mx_' . filter_naughty_harsh($page))) {
                                $object = object_factory('Mx_' . filter_naughty_harsh($page), true);
                            } else {
                                $object = object_factory('Module_' . filter_naughty_harsh($page), true);
                            }
                            if ((!is_null($object)) && (method_exists($object, 'get_entry_points'))) {
                                $_entry_points = $object->get_entry_points();
                                foreach ($_entry_points as $key => $_val) {
                                    $val = $_val[0];

                                    if (strpos($key, ':') !== false) {
                                        $page_link = $key;
                                    } else {
                                        $page_link = $zone . ':' . $page . ':' . $key;
                                    }

                                    if (is_object($val)) {
                                        $_title = $val;
                                    } else {
                                        $_title = (preg_match('#^[A-Z\_]+$#', $val) == 0) ? $val : do_lang($val);
                                    }

                                    $entry_points[$page_link] = $_title;
                                }
                            }
                        } else {
                            $entry_points[$zone . ':' . $page] = $page;
                        }
                    }
                }
            }
            return array('', do_template('OCCLE_ENTRY_POINTS', array('_GUID' => 'afaf0b0451ccbdae399dd56e39359c0e', 'ENTRY_POINTS' => $entry_points)), '', '');
        }
    }
}
