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
 * Block class.
 */
class Block_top_login
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (NULL: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array();
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        if (!is_guest()) {
            return new Tempcode();
        }

        require_css('personal_stats');

        $title = do_lang_tempcode('NOT_LOGGED_IN');

        if ((get_page_name() != 'join') && (get_page_name() != 'login')) {
            if (count($_POST) > 0) {
                $_this_url = build_url(array('page' => ''), '', array('keep_session' => 1, 'redirect' => 1));
            } else {
                $_this_url = build_url(array('page' => '_SELF'), '_SELF', array('keep_session' => 1, 'redirect' => 1), true);
            }
        } else {
            $_this_url = build_url(array('page' => ''), '', array('keep_session' => 1, 'redirect' => 1));
        }
        $this_url = $_this_url->evaluate();
        $login_url = build_url(array('page' => 'login', 'type' => 'login', 'redirect' => $this_url), get_module_zone('login'));
        $full_link = build_url(array('page' => 'login', 'type' => 'misc', 'redirect' => $this_url), get_module_zone('login'));
        $join_url = (get_forum_type() != 'none') ? $GLOBALS['FORUM_DRIVER']->join_url() : '';
        return do_template('BLOCK_TOP_LOGIN', array('TITLE' => $title, 'FULL_LOGIN_URL' => $full_link, 'JOIN_URL' => $join_url, 'LOGIN_URL' => $login_url));
    }
}
