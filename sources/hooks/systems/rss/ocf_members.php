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
class Hook_rss_ocf_members
{
    /**
     * Run function for RSS hooks.
     *
     * @param  string                   A list of categories we accept from
     * @param  TIME                     Cutoff time, before which we do not show results from
     * @param  string                   Prefix that represents the template set we use
     * @set    RSS_ ATOM_
     * @param  string                   The standard format of date to use for the syndication type represented in the prefix
     * @param  integer                  The maximum number of entries to return, ordering by date
     * @return ?array                   A pair: The main syndication section, and a title (NULL: error)
     */
    public function run($_filters, $cutoff, $prefix, $date_string, $max)
    {
        if (!has_actual_page_access(get_member(), 'members')) {
            return null;
        }

        $rows = $GLOBALS['FORUM_DB']->query('SELECT * FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members p WHERE m_join_time>' . strval($cutoff) . (((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) ? ' AND m_validated=1 AND m_validated_email_confirm_code=\'\' ' : '') . ' ORDER BY m_join_time DESC', $max);
        $categories = $GLOBALS['FORUM_DRIVER']->get_usergroup_list(true);

        $content = new Tempcode();
        foreach ($rows as $row) {
            $id = strval($row['id']);
            $author = '';

            $news_date = date($date_string, $row['m_join_time']);
            $edit_date = '';

            $news_title = xmlentities($row['m_username']);
            $summary = '';
            $news = '';

            $category = array_key_exists($row['m_primary_group'], $categories) ? $categories[$row['m_primary_group']] : '';
            $category_raw = strval($row['m_primary_group']);

            $view_url = build_url(array('page' => 'members', 'type' => 'view', 'id' => $row['id']), get_module_zone('members'), null, false, false, true);

            $if_comments = new Tempcode();

            $content->attach(do_template($prefix . 'ENTRY', array('VIEW_URL' => $view_url, 'SUMMARY' => $summary, 'EDIT_DATE' => $edit_date, 'IF_COMMENTS' => $if_comments, 'TITLE' => $news_title, 'CATEGORY_RAW' => $category_raw, 'CATEGORY' => $category, 'AUTHOR' => $author, 'ID' => $id, 'NEWS' => $news, 'DATE' => $news_date)));
        }

        require_lang('ocf');
        return array($content, do_lang('MEMBERS'));
    }
}
