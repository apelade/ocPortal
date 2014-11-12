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
class Hook_css_compile__text
{
    /**
     * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
     *
     * @return tempcode                 The snippet
     */
    public function run()
    {
        if (has_actual_page_access(get_member(), 'admin_themes')) {
            require_code('tempcode_compiler');
            return template_to_tempcode(post_param('css'));
        }
        return new Tempcode();
    }
}
