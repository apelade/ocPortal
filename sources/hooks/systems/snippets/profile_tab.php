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
class Hook_profile_tab
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        $member_id_viewing = get_member();
        $member_id_of = get_param_integer('member_id');

        $hook = filter_naughty_harsh(get_param('tab'));

        require_code('urls2');

        $keep_get = array();
        foreach (array_keys($_GET) as $key) {
            if (in_array($key, array('snippet', 'tab', 'url', 'title', 'member_id', 'utheme'))) {
                continue;
            }
            $keep_get[$key] = get_param($key, null, true);
        }
        set_execution_context(array('page' => 'members', 'type' => 'view', 'id' => $member_id_of) + $keep_get);

        require_code('hooks/systems/profiles_tabs/' . $hook);
        $ob = object_factory('Hook_profiles_tabs_' . $hook);
        if ($ob->is_active($member_id_of, $member_id_viewing)) {
            // We need to minimise the dependency stuff that comes out, we don't need any default values
            push_output_state(false, true);

            // And, go
            $ret = $ob->render_tab($member_id_of, $member_id_viewing);
            $out = new Tempcode();
            $out->attach(symbol_tempcode('CSS_TEMPCODE'));
            $out->attach(symbol_tempcode('JS_TEMPCODE'));
            $out->attach($ret[1]);
            return $out;
        }
        return do_template('INLINE_WIP_MESSAGE', array('_GUID' => 'aae58043638dac785405a42e9578202b', 'MESSAGE' => do_lang_tempcode('INTERNAL_ERROR')));
    }
}
