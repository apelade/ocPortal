<?php

require_code('addons2');

function find_blocks_in_page($page)
{
    $blocks = array();
    $page_path = get_custom_file_base() . '/pages/comcode_custom/' . get_site_default_lang() . '/' . $page . '.txt';
    if (!is_file($page_path)) {
        $page_path = get_custom_file_base() . '/pages/comcode/' . get_site_default_lang() . '/' . $page . '.txt';
    }
    if (is_file($page_path)) {
        $page_contents = file_get_contents($page_path);
        $matches = array();
        $num_matches = preg_match_all('#\[block.*\](.*)\[/block\]#U', $page_contents, $matches);
        for ($i = 0; $i < $num_matches; $i++) {
            $full_tag = $matches[0][$i];
            $block = $matches[1][$i];
            $blocks[$block] = $full_tag;
        }
    }
    return $blocks;
}

$profile = <<<END
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
 * @package    setupwizard
 */

/**
 * Hook class.
 */
class Hook_admin_setupwizard_installprofiles_mycustomprofile
{
    /**
     * Get info about the installprofile
     *
     * @return array                    Map of installprofile details
     */
    function info()
    {
        return array(
            'title'=>'My Custom installprofile',
        );
    }

    /**
     * Get a list of addons that are kept with this installation profile (added to the list of addons always kept)
     *
     * @return array                    Triple: List of addons in the profile, Separated list of ones to show under advanced, Ones we really are shunning
     */
    function get_addon_list()
    {
        return array(
            array(

END;
$addons = find_installed_addons();
sort($addons);
foreach ($addons as $addon) {
    $profile .= "\t\t\t\t\"" . php_addslashes($addon['name']) . "\",\n";
}
$profile .= <<<END
            ),
            array(
            ),
            array(

END;
$non_installed_addons = find_available_addons(false);
sort($non_installed_addons);
foreach ($non_installed_addons as $addon) {
    $profile .= "\t\t\t\t\"" . php_addslashes($addon['name']) . "\",\n";
}
$profile .= <<<END
            ),
        );
    }

    /**
     * Get a map of default settings associated with this installation profile
     *
     * @return array                    Map of default settings
     */
    function field_defaults()
    {
        return array(

END;
$hooks = find_all_hooks('modules', 'admin_setupwizard');
foreach (array_keys($hooks) as $hook) {
    $path = get_file_base() . '/sources_custom/modules/systems/admin_setupwizard/' . filter_naughty_harsh($hook) . '.php';
    if (!file_exists($path)) {
        $path = get_file_base() . '/sources/hooks/modules/admin_setupwizard/' . filter_naughty_harsh($hook) . '.php';
    }
    $_hook_bits = extract_module_functions($path, array('get_current_settings'));
    if (!is_null($_hook_bits[0])) {
        if (is_array($_hook_bits[0])) {
            $settings = call_user_func_array($_hook_bits[0][0], $_hook_bits[0][1]);
        } else {
            $settings = @eval($_hook_bits[0]);
        }
        foreach ($settings as $key => $val) {
            $profile .= "\t\t\t\"" . php_addslashes($key) . "\"=>\"" . php_addslashes($val) . "\",\n";
        }
    }
}
$profile .= <<<END
        );
    }

    /**
     * Find details of desired blocks
     *
     * @return array                    Details of what blocks are wanted
     */
    function default_blocks()
    {
        return array(
            'YES'=>array(

END;
$blocks = find_blocks_in_page('start');
foreach (array_keys($blocks) as $block) {
    $profile .= "\t\t\t\t\"" . php_addslashes($block) . "\",\n";
}
$profile .= <<<END
            ),
            'YES_CELL'=>array(
            ),
            'PANEL_LEFT'=>array(

END;
$blocks = find_blocks_in_page('panel_left');
foreach (array_keys($blocks) as $block) {
    $profile .= "\t\t\t\t\"" . php_addslashes($block) . "\",\n";
}
$profile .= <<<END
            ),
            'PANEL_RIGHT'=>array(

END;
$blocks = find_blocks_in_page('panel_right');
foreach (array_keys($blocks) as $block) {
    $profile .= "\t\t\t\t\"" . php_addslashes($block) . "\",\n";
}
$profile .= <<<END
            ),
        );
    }

    /**
     * Get options for blocks in this profile
     *
     * @return array                    Details of what block options are wanted
     */
    function block_options()
    {
        return array(

END;
require_code('zones2');
$blocks = array_merge(find_blocks_in_page('start'), find_blocks_in_page('panel_left'), find_blocks_in_page('panel_right'));
foreach ($blocks as $block => $full_tag) {
    require_code('comcode_compiler');
    $parameters = parse_single_comcode_tag($full_tag, 'block');
    $profile .= "\t\t\t\t\"" . php_addslashes($block) . "\"=>array(\n";
    foreach ($parameters as $key => $val) {
        if ($key != '') {
            $profile .= "\t\t\t\t\t\"" . php_addslashes($key) . "\"=>\"" . php_addslashes($val) . "\",\n";
        }
    }
    $profile .= "\t\t\t\t),\n";
}
$profile .= <<<END
        );
    }

    /**
     * Execute any special code needed to put this install profile into play
     */
    function install_code()
    {

END;
$config_options = $GLOBALS['SITE_DB']->query_select('config', array('*'));
require_code('config2');
foreach ($config_options as $option) {
    $name = $option['c_name'];
    if (in_array($name, array('site_name', 'description', 'site_scope', 'copyright', 'staff_address', 'keywords', 'google_analytics', 'fixed_width', 'site_closed', 'closed', 'stats_store_time', 'show_content_tagging', 'show_content_tagging_inline', 'show_screen_actions', 'collapse_user_zones'))) {
        continue; // These are set separately
    }
    $value = get_option($name);
    if ($value == get_default_option($name)) {
        continue;
    }
    $_name = php_addslashes($name);
    $_value = php_addslashes($value);
    $profile .= "\t\tif (get_option(\"{$_name}\",true)!==NULL) set_option(\"{$_name}\",\"{$_value}\");\n";
}
$profile .= <<<END
    }
}
END;

$site_name = get_option('site_name');
$addoninf = <<<END
name="My Custom installprofile"
author="Me"
organisation="{$site_name}"
version="1"
incompatibilities=""
dependencies=""
description="Auto-generated installprofile for the Setup Wizard."
category="Installation profiles"
copyright_attribution=""
licence="(Unstated)"
END;

$filename = 'mycustomprofile.tar';
header('Content-Type: application/octet-stream' . '; authoritative=true;');
header('Content-Disposition: attachment; filename="' . str_replace("\r", '', str_replace("\n", '', addslashes($filename))) . '"');

require_code('tar');

$tar = tar_open(null, 'wb');

tar_add_file($tar, 'sources_custom/hooks/modules/admin_setupwizard_installprofiles/mycustomprofile.php', $profile);
tar_add_file($tar, 'addon.inf', $addoninf);

tar_close($tar);

$GLOBALS['SCREEN_TEMPLATE_CALLED'] = '';
exit();
