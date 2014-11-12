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
 * @package    core_configuration
 */

/**
 * Hook class.
 */
class Hook_config_forum_show_personal_stats_topics
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (NULL: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'COUNT_TOPICSCOUNT',
            'type' => 'tick',
            'category' => 'BLOCKS',
            'group' => 'PERSONAL_BLOCK',
            'explanation' => 'CONFIG_OPTION_forum_show_personal_stats_topics',
            'shared_hosting_restricted' => '0',
            'list_options' => '',

            'addon' => 'core_configuration',
        );
    }

    /**
     * Gets the default value for the config option.
     *
     * @return ?string                  The default value (NULL: option is disabled)
     */
    public function get_default()
    {
        return ((has_no_forum()) || (get_forum_type() != 'ocf')) ? null : '1';
    }
}
