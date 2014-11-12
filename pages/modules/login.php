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
 * Module page class.
 */
class Module_login
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (NULL: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = true;
        return $info;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('failedlogins');
    }

    /**
     * Install the module.
     *
     * @param  ?integer                 What version we're upgrading from (NULL: new install)
     * @param  ?integer                 What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        $GLOBALS['SITE_DB']->create_table('failedlogins', array(
            'id' => '*AUTO',
            'failed_account' => 'ID_TEXT',
            'date_and_time' => 'TIME',
            'ip' => 'IP'
        ));
        $GLOBALS['SITE_DB']->create_index('failedlogins', 'failedlogins_by_ip', array('ip'));
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (NULL: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        if ($check_perms && is_guest($member_id)) {
            return array(
                'misc' => array('_LOGIN', 'menu/site_meta/user_actions/login'),
            );
        }
        $ret = array(
            'misc' => array('_LOGIN', 'menu/site_meta/user_actions/login'),
            //'logout'=>array('LOGOUT','menu/site_meta/user_actions/logout'), Don't show an immediate action, don't want accidental preloading
            //'concede'=>array('CONCEDED_MODE','menu/site_meta/user_actions/concede'), Don't show an immediate action, don't want accidental preloading
        );
        /*
        if (get_option('is_on_invisibility')=='1')
            $ret['invisible']=array('INVISIBLE','menu/site_meta/user_actions/invisible'); Don't show an immediate action, don't want accidental preloading
        */
        return $ret;
    }

    public $title;
    public $visible;
    public $username;
    public $feedback;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (NULL: none).
     */
    public function pre_run()
    {
        $type = get_param('type', 'misc');

        if ($type == 'misc') {
            $this->title = get_screen_title('_LOGIN');

            attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

            breadcrumb_set_parents(array());
        }

        if ($type == 'login') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:misc', do_lang_tempcode('_LOGIN'))));

            $username = trim(post_param('login_username'));

            $feedback = $GLOBALS['FORUM_DRIVER']->forum_authorise_login($username, null, apply_forum_driver_md5_variant(trim(post_param('password')), $username), trim(post_param('password')));
            if (!is_null($feedback['id'])) {
                $this->title = get_screen_title('LOGGED_IN');
            } else {
                $this->title = get_screen_title('MEMBER_LOGIN_ERROR');
            }

            $this->username = $username;
            $this->feedback = $feedback;
        }

        if ($type == 'logout') {
            $this->title = get_screen_title('LOGGED_OUT');
        }

        if ($type == 'concede') {
            $this->title = get_screen_title('CONCEDED_MODE');
        }

        if ($type == 'invisible') {
            if (get_option('is_on_invisibility') == '1') {
                $visible = (array_key_exists(get_session_id(), $GLOBALS['SESSION_CACHE'])) && ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible'] == 0);
            } else {
                $visible = false; // Small fudge: always say thay are not visible now, so this will make them visible -- because they don't have permission to be invisible
            }

            $this->title = get_screen_title($visible ? 'INVISIBLE' : 'BE_VISIBLE');

            $this->visible = $visible;
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        $type = get_param('type', 'misc');

        if ($type == 'misc') {
            return $this->login_before();
        }
        if ($type == 'login') {
            return $this->login_after();
        }
        if ($type == 'logout') {
            return $this->logout();
        }
        if ($type == 'concede') {
            return $this->concede();
        }
        if ($type == 'invisible') {
            return $this->invisible();
        }

        return new Tempcode();
    }

    /**
     * The UI for logging in.
     *
     * @return tempcode                 The UI.
     */
    public function login_before()
    {
        $passion = new Tempcode(); // Hidden fields

        // Where we will be redirected to after login, for GET requests (POST requests are handled further in the code)
        $redirect_default = get_self_url(true, true); // The default is to go back to where we are after login. Note that this is not necessarily the URL to the login module, as login screens happen on top of screens you're not allowed to access. If it is the URL to the login module, we'll realise this later in this code. This URL is coded to not redirect to root if we have $_POST, because we relay $_POST values and have intelligence (via $passion).
        $redirect = get_param('redirect', $redirect_default); // ... but often the login screen's URL tells us where to go back to
        $unhelpful_redirect = false;
        $unhelpful_url_stubs = array(
            static_evaluate_tempcode(build_url(array('page' => 'login'), '', null, false, false, true)),
            static_evaluate_tempcode(build_url(array('page' => 'login', 'type' => 'misc'), '', null, false, false, true)),
            static_evaluate_tempcode(build_url(array('page' => 'login', 'type' => 'logout'), '', null, false, false, true)),
        );
        foreach ($unhelpful_url_stubs as $unhelpful_url_stub) {
            if (substr($redirect, 0, strlen($unhelpful_url_stub)) == $unhelpful_url_stub) {
                $unhelpful_redirect = true;
                break;
            }
        }
        if (($redirect != '') && (!$unhelpful_redirect)) {
            $passion->attach(form_input_hidden('redirect', $redirect));
        } else { // We will only go to the zone-default page if an explicitly blank redirect URL is given or if the redirect would take us direct to another login or logout page
            global $ZONE;
            $_url = build_url(array('page' => $ZONE['zone_default_page']), '_SELF');
            $url = $_url->evaluate();
            $passion->attach(form_input_hidden('redirect', $url));
        }

        // POST field relaying
        if (count($_FILES) == 0) { // Only if we don't have _FILES (which could never be relayed)
            $passion->attach(build_keep_post_fields(array('redirect')));
            $redirect_passon = post_param('redirect', null);
            if (!is_null($redirect_passon)) {
                $passion->attach(form_input_hidden('redirect_passon', $redirect_passon)); // redirect_passon is used when there are POST fields, as it says what the redirect will be on the post-login-check hop (post fields prevent us doing an immediate HTTP-level redirect).
            }
        }

        // Lost password link
        if (get_forum_type() == 'ocf') {
            require_lang('ocf');
            $forgotten_link = build_url(array('page' => 'lost_password', 'wide_high' => get_param_integer('wide_high', null)), get_module_zone('lost_password'));
            $extra = do_lang_tempcode('IF_FORGOTTEN_PASSWORD', escape_html($forgotten_link->evaluate()));
        } else {
            $extra = new Tempcode();
        }

        // Render
        $login_url = build_url(array('page' => '_SELF', 'type' => 'login'), '_SELF');
        require_css('login');
        $username = trim(get_param('username', ''));
        if (!is_guest()) {
            $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
        }
        return do_template('LOGIN_SCREEN', array('_GUID' => '0940dbf2c42493c53b7e99eb50ca51f1', 'EXTRA' => $extra, 'USERNAME' => $username, 'JOIN_URL' => $GLOBALS['FORUM_DRIVER']->join_url(), 'TITLE' => $this->title, 'LOGIN_URL' => $login_url, 'PASSION' => $passion));
    }

    /**
     * The actualiser for logging in.
     *
     * @return tempcode                 The UI.
     */
    public function login_after()
    {
        $username = $this->username;
        $feedback = $this->feedback;

        $id = $feedback['id'];
        if (!is_null($id)) {
            $url = enforce_sessioned_url(either_param('redirect')); // Now that we're logged in, we need to ensure the redirect URL contains our new session ID

            if (count($_POST) <= 4) { // Only the login username, password, remember-me and redirect
                require_code('site2');
                assign_refresh($url, 0.0);
                $post = new Tempcode();
                $refresh = new Tempcode();
            } else {
                $post = build_keep_post_fields(array('login_username', 'password', 'remember', 'login_invisible', 'redirect'));
                $redirect_passon = post_param('redirect_passon', null); // redirect_passon is used when there are POST fields, as it says what the redirect will be on this post-login-check hop (post fields prevent us doing an immediate HTTP-level redirect).
                if (!is_null($redirect_passon)) {
                    $post->attach(form_input_hidden('redirect', enforce_sessioned_url($redirect_passon)));
                }
                $refresh = do_template('JS_REFRESH', array('_GUID' => 'c7d2f9e7a2cc637f3cf9ac4d1cf97eca', 'FORM_NAME' => 'redir_form'));
            }
            decache('side_users_online');

            return do_template('LOGIN_REDIRECT_SCREEN', array('_GUID' => '82e056de9150bbed185120eac3571f40', 'REFRESH' => $refresh, 'TITLE' => $this->title, 'TEXT' => do_lang_tempcode('_LOGIN_TEXT'), 'URL' => $url, 'POST' => $post));
        } else {
            $text = $feedback['error'];

            attach_message($text, 'warn');

            if (get_forum_type() == 'ocf') {
                require_lang('ocf');
                $forgotten_link = build_url(array('page' => 'lost_password'), get_module_zone('lost_password'));
                $extra = do_lang_tempcode('IF_FORGOTTEN_PASSWORD', escape_html($forgotten_link->evaluate()));

                attach_message($extra, 'inform');
            }

            return $this->login_before();
        }
    }

    /**
     * The actualiser for logging out.
     *
     * @return tempcode                 The UI.
     */
    public function logout()
    {
        decache('side_users_online');

        $url = get_param('redirect', null);
        if (is_null($url)) {
            $_url = build_url(array('page' => ''), '', array('keep_session' => 1));
            $url = $_url->evaluate();
        }
        return redirect_screen($this->title, $url, do_lang_tempcode('_LOGGED_OUT'));
    }

    /**
     * The actualiser for entering conceded mode.
     *
     * @return tempcode                 The UI.
     */
    public function concede()
    {
        $GLOBALS['SITE_DB']->query_update('sessions', array('session_confirmed' => 0), array('member_id' => get_member(), 'the_session' => get_session_id()), '', 1);
        global $SESSION_CACHE;
        if ($SESSION_CACHE[get_session_id()]['member_id'] == get_member()) { // A little security
            $SESSION_CACHE[get_session_id()]['session_confirmed'] = 0;
            if (get_option('session_prudence') == '0') {
                persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
            }
        }

        $url = get_param('redirect', null);
        if (is_null($url)) {
            $_url = build_url(array('page' => ''), '');
            $url = $_url->evaluate();
        }
        return redirect_screen($this->title, $url, do_lang_tempcode('LOGIN_CONCEDED'));
    }

    /**
     * The actualiser for toggling invisible mode.
     *
     * @return tempcode                 The UI.
     */
    public function invisible()
    {
        $visible = $this->visible;

        $GLOBALS['SITE_DB']->query_update('sessions', array('session_invisible' => $visible ? 1 : 0), array('member_id' => get_member(), 'the_session' => get_session_id()), '', 1);
        global $SESSION_CACHE;
        if ($SESSION_CACHE[get_session_id()]['member_id'] == get_member()) { // A little security
            $SESSION_CACHE[get_session_id()]['session_invisible'] = $visible ? 1 : 0;
            if (get_option('session_prudence') == '0') {
                persistent_cache_set('SESSION_CACHE', $SESSION_CACHE);
            }
        }

        decache('side_users_online');

        // Store in cookie, if we have login cookies around
        if (array_key_exists(get_member_cookie(), $_COOKIE)) {
            require_code('users_active_actions');
            ocp_setcookie(get_member_cookie() . '_invisible', strval($visible ? 1 : 0));
            $_COOKIE[get_member_cookie() . '_invisible'] = strval($visible ? 1 : 0);
        }

        $url = get_param('redirect', null);
        if (is_null($url)) {
            $_url = build_url(array('page' => ''), '');
            $url = $_url->evaluate();
        }
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
