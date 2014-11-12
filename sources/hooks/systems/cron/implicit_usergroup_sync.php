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
 * @package    core
 */

/**
 * Hook class.
 */
class Hook_cron_implicit_usergroup_sync
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        if (get_value('implicit_usergroup_sync') === '1') {
            $last = get_long_value('last_implicit_sync');
            if ((is_null($last)) || (intval($last) < time() - 60 * 60)) {
                $hooks = find_all_hooks('systems', 'ocf_implicit_usergroups');
                foreach (array_keys($hooks) as $hook) {
                    require_code('hooks/systems/ocf_implicit_usergroups/' . $hook);
                    $ob = object_factory('Hook_implicit_usergroups_' . $hook);
                    $group_ids = $ob->get_bound_group_ids();
                    foreach ($group_ids as $group_id) {
                        $GLOBALS['FORUM_DB']->query_delete('f_group_members', array('gm_group_id' => $group_id));
                        $list = $ob->get_member_list($group_id);
                        if (!is_null($list)) {
                            foreach ($list as $member_row) {
                                $GLOBALS['FORUM_DB']->query_insert('f_group_members', array('gm_group_id' => $group_id, 'gm_member_id' => $member_row['id'], 'gm_validated' => 1));
                            }
                        } else {
                            $start = 0;
                            do {
                                $members = collapse_1d_complexity('id', $GLOBALS['FORUM_DB']->query_select('f_members', array('id'), null, '', 400, $start));
                                foreach ($members as $member_id) {
                                    if ($ob->is_member_within($member_id, $group_id)) {
                                        $GLOBALS['FORUM_DB']->query_insert('f_group_members', array('gm_group_id' => $group_id, 'gm_member_id' => $member_id, 'gm_validated' => 1));
                                    }
                                }
                                $start += 400;
                            }
                            while (count($members) == 400);
                        }
                    }
                }

                set_long_value('last_implicit_sync', strval(time()));
            }
        }
    }
}
