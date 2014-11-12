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
class Hook_invite_missing
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        $val = get_param('name');

        $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_invites', 'i_email_address', array('i_email_address' => $val, 'i_taken' => 0));
        if (!is_null($test)) {
            return new Tempcode(); // All ok
        }

        // Some kind of issue...

        require_lang('ocf');

        $test = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_invites', 'i_email_address', array('i_email_address' => $val));
        if (!is_null($test)) {
            return make_string_tempcode(strip_html(do_lang('INVITE_ALREADY_JOINED')));
        }

        return make_string_tempcode(strip_html(do_lang('NO_INVITE')));
    }
}
