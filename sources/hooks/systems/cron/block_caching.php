<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2015

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
class Hook_cron_block_caching
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        if ((get_param('keep_lang', null) === null) || (get_param('keep_theme', null) === null)) {
            // We need to run this for each language and for each theme
            $langs = find_all_langs();
            require_code('themes2');
            $themes = find_all_themes();
            foreach (array_keys($langs) as $lang) {
                foreach (array_keys($themes) as $theme) {
                    if (($theme == 'default') || (has_category_access(get_member(), 'theme', $theme))) {
                        $where = array('c_theme' => $theme, 'c_lang' => $lang);
                        $count = $GLOBALS['SITE_DB']->query_select_value('cron_caching_requests', 'COUNT(*)', $where);
                        if ($count > 0) {
                            $url = get_base_url() . '/data/cron_bridge.php?limit_hook=block_caching&keep_lang=' . urlencode($lang) . '&keep_theme=' . urlencode($theme);
                            http_download_file($url, null, false);
                        }
                    }
                }
            }

            // Force re-loading of values that we use to mark progress (as above calls probably resulted in changes happening)
            global $VALUE_OPTIONS_CACHE;
            $VALUE_OPTIONS_CACHE = $GLOBALS['SITE_DB']->query_select('values', array('*'));
            $VALUE_OPTIONS_CACHE = list_to_map('the_name', $VALUE_OPTIONS_CACHE);

            return;
        }

        $where = array('c_theme' => $GLOBALS['FORUM_DRIVER']->get_theme(), 'c_lang' => user_lang());
        $requests = $GLOBALS['SITE_DB']->query_select('cron_caching_requests', array('*'), $where);
        foreach ($requests as $request) {
            $GLOBALS['NO_QUERY_LIMIT'] = true;

            $codename = $request['c_codename'];
            $map = unserialize($request['c_map']);

            list($object, $new_security_scope) = do_block_hunt_file($codename, $map);

            if ($new_security_scope) {
                _solemnly_enter();
            }

            if (is_object($object)) {
                global $LANGS_REQUESTED, $LANGS_REQUESTED, $DO_NOT_CACHE_THIS, $TIMEZONE_MEMBER_CACHE, $JAVASCRIPTS, $CSSS;

                $backup_langs_requested = $LANGS_REQUESTED;
                get_users_timezone();
                $backup_timezone = $TIMEZONE_MEMBER_CACHE[get_member()];
                $LANGS_REQUESTED = array();
                push_output_state(false, true);
                $cache = $object->run($map);
                $TIMEZONE_MEMBER_CACHE[get_member()] = $backup_timezone;
                $cache->handle_symbol_preprocessing();
                if (!$DO_NOT_CACHE_THIS) {
                    if (method_exists($object, 'cacheing_environment')) {
                        $info = $object->cacheing_environment();
                    } else {
                        $info = array();
                        $info['cache_on'] = 'array($map)';
                        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
                        $info['ttl'] = 60 * 24;
                    }
                    $ttl = $info['ttl'];

                    $_cache_identifier = array();
                    $cache_on = $info['cache_on'];
                    if (is_array($cache_on)) {
                        $_cache_identifier = call_user_func($cache_on[0], $map);
                    } else {
                        if ($cache_on != '') {
                            $_cache_on = eval('return ' . $cache_on . ';'); // NB: This uses $map, as $map is referenced inside $cache_on
                            if (is_null($_cache_on)) {
                                return null;
                            }
                            foreach ($_cache_on as $on) {
                                $_cache_identifier[] = $on;
                            }
                        }
                    }
                    $cache_identifier = serialize($_cache_identifier);

                    require_code('caches2');
                    if ($request['c_store_as_tempcode'] == 1) {
                        $cache = make_string_tempcode($cache->evaluate());
                    }
                    put_into_cache($codename, $ttl, $cache_identifier, $request['c_staff_status'], $request['c_member'], $request['c_groups'], $request['c_is_bot'], $request['c_timezone'], $cache, array_keys($LANGS_REQUESTED), array_keys($JAVASCRIPTS), array_keys($CSSS), true);
                }
                $LANGS_REQUESTED += $backup_langs_requested;
                restore_output_state(false, true);
            }

            if ($new_security_scope) {
                _solemnly_leave();
            }

            $GLOBALS['SITE_DB']->query_delete('cron_caching_requests', $request);
        }
    }
}
