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
class Hook_config_crypt_ratchet
{
    /**
     * Gets the details relating to the config option.
     *
     * @return ?array                   The details (NULL: disabled)
     */
    public function get_details()
    {
        return array(
            'human_name' => 'CRYPT_RATCHET',
            'type' => 'integer',
            'category' => 'SECURITY',
            'group' => 'ADVANCED',
            'explanation' => 'CONFIG_OPTION_crypt_ratchet',
            'shared_hosting_restricted' => '0',
            'list_options' => '4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31',

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
        if (function_exists('password_hash')) {
            /**
             * This code will benchmark your server to determine how high of a cost you can
             * afford. You want to set the highest cost that you can without slowing down
             * you server too much. 10 is a good baseline, and more is good if your servers
             * are fast enough. The code below aims for <= 50 milliseconds stretching time,
             * which is a good baseline for systems handling interactive logins.
             */
            $time_target = 0.05; // 50 milliseconds

            $cost = 10;
            do {
                $start = microtime(true);
                password_hash('test', PASSWORD_BCRYPT, array('cost' => $cost));
                $end = microtime(true);
                $time_dif = ($end - $start);
                $cost++;
            }
            while (($time_dif < $time_target) && ($cost <= 31));
            $cost--;
        } else {
            $cost = 10;
        }

        return strval($cost);
    }
}
