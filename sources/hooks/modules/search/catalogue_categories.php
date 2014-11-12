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
 * @package    catalogues
 */

/**
 * Hook class.
 */
class Hook_search_catalogue_categories
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean                  Whether to check permissions.
     * @return ?array                   Map of search hook details (NULL: hook is disabled).
     */
    public function info($check_permissions = true)
    {
        if (!module_installed('catalogues')) {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access(get_member(), 'catalogues')) {
                return null;
            }
        }

        if ($GLOBALS['SITE_DB']->query_select_value('catalogue_categories', 'COUNT(*)') == 0) {
            return null;
        }

        require_lang('catalogues');

        $info = array();
        $info['lang'] = do_lang_tempcode('CATALOGUE_CATEGORIES');
        $info['default'] = false;

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('calendar'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('calendar'),
                'page_name' => 'calendar',
            ),
        );

        return $info;
    }

    /**
     * Run function for search results.
     *
     * @param  string                   Search string
     * @param  boolean                  Whether to only do a META (tags) search
     * @param  ID_TEXT                  Order direction
     * @param  integer                  Start position in total results
     * @param  integer                  Maximum results to return in total
     * @param  boolean                  Whether only to search titles (as opposed to both titles and content)
     * @param  string                   Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
     * @param  SHORT_TEXT               Username/Author to match for
     * @param  ?MEMBER                  Member-ID to match for (NULL: unknown)
     * @param  TIME                     Cutoff date
     * @param  string                   The sort type (gets remapped to a field in this function)
     * @set    title add_date
     * @param  integer                  Limit to this number of results
     * @param  string                   What kind of boolean search to do
     * @set    or and
     * @param  string                   Where constraints known by the main search code (SQL query fragment)
     * @param  string                   Comma-separated list of categories to search under
     * @param  boolean                  Whether it is a boolean search
     * @return array                    List of maps (template, orderer)
     */
    public function run($content, $only_search_meta, $direction, $max, $start, $only_titles, $content_where, $author, $author_id, $cutoff, $sort, $limit_to, $boolean_operator, $where_clause, $search_under, $boolean_search)
    {
        if (!module_installed('catalogues')) {
            return array();
        }

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'cc_title';
                break;

            case 'add_date':
                $remapped_orderer = 'cc_add_date';
                break;
        }

        require_code('catalogues');
        require_lang('catalogues');

        // Calculate our where clause (search)
        if ($author != '') {
            return array();
        }
        if (!is_null($cutoff)) {
            $where_clause .= ' AND ';
            $where_clause .= 'cc_add_date>' . strval($cutoff);
        }
        if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) {
            $where_clause .= ' AND ';
            $where_clause .= 'z.category_name IS NOT NULL';
            $where_clause .= ' AND ';
            $where_clause .= 'p.category_name IS NOT NULL';
        }

        $g_or = _get_where_clause_groups(get_member());

        // Calculate and perform query
        if ($g_or == '') {
            $rows = get_search_rows('catalogue_category', 'id', $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, 'catalogue_categories r', array('r.cc_title' => 'SHORT_TRANS', 'r.cc_description' => 'LONG_TRANS__COMCODE'), $where_clause, $content_where, $remapped_orderer, 'r.*');
        } else {
            $rows = get_search_rows('catalogue_category', 'id', $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, 'catalogue_categories r' . ((get_value('disable_cat_cat_perms') === '1') ? '' : (' JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'group_category_access z ON (' . db_string_equal_to('z.module_the_name', 'catalogues_category') . ' AND z.category_name=r.id AND ' . str_replace('group_id', 'z.group_id', $g_or) . ')')) . ' JOIN ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'group_category_access p ON (' . db_string_equal_to('p.module_the_name', 'catalogues_catalogue') . ' AND p.category_name=r.c_name AND ' . str_replace('group_id', 'p.group_id', $g_or) . ')', array('r.cc_title' => 'SHORT_TRANS', 'r.cc_description' => 'LONG_TRANS__COMCODE'), $where_clause, $content_where, $remapped_orderer, 'r.*');
        }

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer, '_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array                    The data row stored when we retrieved the result
     * @return tempcode                 The output
     */
    public function render($row)
    {
        require_code('catalogues');
        return render_catalogue_category_box($row);
    }
}
