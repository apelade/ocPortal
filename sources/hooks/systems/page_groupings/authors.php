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
 * @package    authors
 */

/**
 * Hook class.
 */
class Hook_page_groupings_authors
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  Member ID to run as (NULL: current member)
     * @param  boolean                  Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        if (!addon_installed('authors')) {
            return array();
        }

        return array(
            array('cms', 'menu/rich_content/authors', array('cms_authors', array('type' => 'misc'), get_module_zone('cms_authors')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('authors:AUTHORS'), make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('authors', 'COUNT(*)', null, '', true))))), 'authors:DOC_AUTHORS'),
            array('rich_content', 'menu/rich_content/authors', array('authors', array(), get_module_zone('authors')), do_lang_tempcode('authors:AUTHORS')),
        );
    }
}
