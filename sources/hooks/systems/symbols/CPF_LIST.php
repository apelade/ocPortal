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
 * @package    core_ocf
 */

/**
 * Hook class.
 */
class Hook_symbol_CPF_LIST
{
    /**
     * Run function for symbol hooks. Searches for tasks to perform.
     *
     * @param  array                     Symbol parameters
     * @return string                    Result
     */
    public function run($param)
    {
        $value = '';

        if (isset($param[0])) {
            static $cache = array();
            if (isset($cache[$param[0]])) {
                return $cache[$param[0]];
            }

            if (($param[0] == 'm_primary_group|gm_group_id') || ($param[0] == 'm_primary_group') || ($param[0] == 'gm_group_id')) {
                $map = has_privilege(get_member(), 'see_hidden_groups') ? array() : array('g_hidden' => 0);
                $group_count = $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'COUNT(*)');
                $map_extended = $map;
                if ($group_count > 200) {
                    $map_extended += array('g_is_private_club' => 0);
                }
                $_m = $GLOBALS['FORUM_DB']->query_select('f_groups', array('id', 'g_name'), $map_extended, 'ORDER BY g_order');
                foreach ($_m as $i => $m) {
                    $_m[$i]['text'] = get_translated_text($m['g_name'], $GLOBALS['FORUM_DB']);
                }
                sort_maps_by($_m, 'text');
                foreach ($_m as $m) {
                    if ($m['id'] == db_get_first_id()) {
                        continue;
                    }

                    if ($value != '') {
                        $value .= ',';
                    }
                    $value .= strval($m['id']) . '=' . $m['text'];
                }
            }
            require_code('ocf_members');
            $cpf_id = find_cpf_field_id($param[0]);
            if (!is_null($cpf_id)) {
                $test = $GLOBALS['FORUM_DB']->query_select('f_custom_fields', array('cf_default', 'cf_type'), array('id' => $cpf_id));
                if (array_key_exists(0, $test)) {
                    switch ($test[0]['cf_type']) {
                        case 'radiolist':
                        case 'list':
                        case 'multilist':
                        case 'combo':
                        case 'combo_multi':
                            $bits = explode('|', $test[0]['cf_default']);
                            sort($bits);
                            if (trim($k, '-') == '' && $value == '') {
                                continue;
                            }
                            foreach ($bits as $k) {
                                if ($value != '') {
                                    $value .= ',';
                                }
                                $value .= $k . '=' . $k;
                            }
                            break;

                        case 'tick':
                            $value = '=|0=' . do_lang('NO') . '|1=' . do_lang('YES');
                            break;
                    }
                }
            }

            $cache[$param[0]] = $value;
        }

        return $value;
    }
}
