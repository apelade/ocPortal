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
 * @package    bookmarks
 */

/**
 * Hook class.
 */
class Hook_sitemap_bookmarks extends Hook_sitemap_base
{
    /**
     * Find if a page-link will be covered by this node.
     *
     * @param  ID_TEXT                  The page-link.
     * @return integer                  A SITEMAP_NODE_* constant.
     */
    public function handles_page_link($page_link)
    {
        $matches = array();
        if (preg_match('#^([^:]*):bookmarks(:|$)#', $page_link, $matches) != 0) {
            $zone = $matches[1];
            $page = 'bookmarks';

            require_code('site');
            $test = _request_page($page, $zone);
            if (($test !== false) && (($test[0] == 'MODULES_CUSTOM') || ($test[0] == 'MODULES'))) { // Ensure the relevant module really does exist in the given zone
                return SITEMAP_NODE_HANDLED_VIRTUALLY;
            }
        }
        return SITEMAP_NODE_NOT_HANDLED;
    }

    /**
     * Find details of a virtual position in the sitemap. Virtual positions have no structure of their own, but can find child structures to be absorbed down the tree. We do this for modularity reasons.
     *
     * @param  ID_TEXT                  The page-link we are finding.
     * @param  ?string                  Callback function to send discovered page-links to (NULL: return).
     * @param  ?array                   List of node types we will return/recurse-through (NULL: no limit)
     * @param  ?integer                 Maximum number of children before we cut off all children (NULL: no limit).
     * @param  ?integer                 How deep to go from the sitemap root (NULL: no limit).
     * @param  integer                  Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by Google sitemap [deeper is typically less important]).
     * @param  boolean                  Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  Whether to filter out non-validated content.
     * @param  integer                  A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  boolean                  Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   List of node structures (NULL: working via callback).
     */
    public function get_virtual_nodes($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $require_permission_support = false, $zone = '_SEARCH', $use_page_groupings = false, $consider_secondary_categories = false, $consider_validation = false, $meta_gather = 0, $return_anyway = false)
    {
        $nodes = ($callback === null || $return_anyway) ? array() : mixed();

        if (($valid_node_types !== null) && (!in_array('_bookmark_folder', $valid_node_types))) {
            return $nodes;
        }

        if ($require_permission_support) {
            return $nodes;
        }

        if (is_guest()) {
            return $nodes;
        }

        if ($child_cutoff !== null) {
            $where = array('b_owner' => get_member(), 'b_folder' => '');
            $count = $GLOBALS['SITE_DB']->query_select_value('bookmarks', 'COUNT(*)', $where);
            if ($count > $child_cutoff) {
                return $nodes;
            }
        }

        $page = $this->_make_zone_concrete($zone, $page_link);

        $subfolder_rows = $GLOBALS['SITE_DB']->query_select('bookmarks', array('DISTINCT b_folder'), array('b_owner' => get_member()), ' AND ' . db_string_not_equal_to('b_folder', ''));
        $children_rows = $GLOBALS['SITE_DB']->query_select('bookmarks', array('*'), array('b_owner' => get_member(), 'b_folder' => ''), '', 1);

        if ($child_cutoff !== null) {
            if (count($subfolder_rows) + count($children_rows) > $child_cutoff) {
                return $nodes;
            }
        }

        $children_folders = array();
        foreach ($subfolder_rows as $child_row) {
            $child_page_link = $zone . ':' . $page . ':misc:' . urlencode($child_row['b_folder']);
            $child_node = $this->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $child_row);
            if ($child_node !== null) {
                $children_folders[] = $child_node;
            }
        }
        sort_maps_by($children_folders, 'title');

        $children = array();
        foreach ($children_rows as $child_row) {
            $child_page_link = $zone . ':' . $page . ':view:' . strval($child_row['id']);
            $child_node = $this->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $child_row);
            if ($child_node !== null) {
                $children[] = $child_node;
            }
        }
        sort_maps_by($children, 'title');

        return is_null($nodes) ? null : array_merge($children_folders, $children);
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT                  The page-link we are finding.
     * @param  ?string                  Callback function to send discovered page-links to (NULL: return).
     * @param  ?array                   List of node types we will return/recurse-through (NULL: no limit)
     * @param  ?integer                 Maximum number of children before we cut off all children (NULL: no limit).
     * @param  ?integer                 How deep to go from the Sitemap root (NULL: no limit).
     * @param  integer                  Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  boolean                  Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
     * @param  ID_TEXT                  The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  boolean                  Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
     * @param  boolean                  Whether to consider secondary categorisations for content that primarily exists elsewhere.
     * @param  boolean                  Whether to filter out non-validated content.
     * @param  integer                  A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array                   Database row (NULL: lookup).
     * @param  boolean                  Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array                   Node structure (NULL: working via callback / error).
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $require_permission_support = false, $zone = '_SEARCH', $use_page_groupings = false, $consider_secondary_categories = false, $consider_validation = false, $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $nodes = ($callback === null || $return_anyway) ? array() : mixed();

        if (($valid_node_types !== null) && (!in_array('_bookmark', $valid_node_types))) {
            return $nodes;
        }

        if ($require_permission_support) {
            return $nodes;
        }

        if (is_guest()) {
            return $nodes;
        }

        $matches = array();
        preg_match('#^([^:]*):([^:]*):([^:]*):([^:]*)#', $page_link, $matches);
        $screen = $matches[3];

        $page = $this->_make_zone_concrete($zone, $page_link);

        if ($screen == 'view') { // Bookmark (NB: a 'view' page-link isn't real, it's just used as a call identifier - the real page-link is what the bookmark says)
            $id = intval($matches[4]);

            if ($row === null) {
                $rows = $GLOBALS['SITE_DB']->query_select('bookmarks', array('*'), array('id' => $id, 'b_owner' => get_member()/*over-specified to include a security check*/), '', 1);
                $row = $rows[0];
            }

            $struct = array(
                'title' => make_string_tempcode(escape_html($row['b_title'])),
                'content_type' => '_bookmark',
                'content_id' => null,
                'modifiers' => array(),
                'only_on_page' => '',
                'page_link' => $row['b_page_link'],
                'url' => null,
                'extra_meta' => array(
                    'description' => null,
                    'image' => (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) ? find_theme_image('icons/24x24/menu/_generic_spare/page') : null,
                    'image_2x' => (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) ? find_theme_image('icons/48x48/menu/_generic_spare/page') : null,
                    'add_date' => null,
                    'edit_date' => null,
                    'submitter' => null,
                    'views' => null,
                    'rating' => null,
                    'meta_keywords' => null,
                    'meta_description' => null,
                    'categories' => null,
                    'validated' => null,
                    'db_row' => (($meta_gather & SITEMAP_GATHER_DB_ROW) != 0) ? $row : null,
                ),
                'permissions' => array(),
                'children' => null,
                'has_possible_children' => true,

                // These are likely to be changed in individual hooks
                'sitemap_priority' => SITEMAP_IMPORTANCE_NONE,
                'sitemap_refreshfreq' => 'yearly',

                'privilege_page' => null,
            );

            if (!$this->_check_node_permissions($struct)) {
                return null;
            }

            if ($callback !== null) {
                call_user_func($callback, $struct);
            }
        } else { // Folder
            if (($max_recurse_depth === null) || ($recurse_level < $max_recurse_depth)) {
                $folder = $matches[4];

                $struct = array(
                    'title' => make_string_tempcode(escape_html($folder)),
                    'content_type' => '_bookmark_folder',
                    'content_id' => null,
                    'modifiers' => array(),
                    'only_on_page' => '',
                    'page_link' => '',
                    'url' => null,
                    'extra_meta' => array(
                        'description' => null,
                        'image' => (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) ? find_theme_image('icons/24x24/menu/_generic_admin/view_this_category') : null,
                        'image_2x' => (($meta_gather & SITEMAP_GATHER_IMAGE) != 0) ? find_theme_image('icons/48x48/menu/_generic_admin/view_this_category') : null,
                        'add_date' => null,
                        'edit_date' => null,
                        'submitter' => null,
                        'views' => null,
                        'rating' => null,
                        'meta_keywords' => null,
                        'meta_description' => null,
                        'categories' => null,
                        'validated' => null,
                        'db_row' => (($meta_gather & SITEMAP_GATHER_DB_ROW) != 0) ? $row : null,
                    ),
                    'permissions' => array(),
                    'children' => null,
                    'has_possible_children' => true,

                    // These are likely to be changed in individual hooks
                    'sitemap_priority' => SITEMAP_IMPORTANCE_NONE,
                    'sitemap_refreshfreq' => 'yearly',

                    'privilege_page' => null,
                );

                if (!$this->_check_node_permissions($struct)) {
                    return null;
                }

                $children_rows = $GLOBALS['SITE_DB']->query_select('bookmarks', array('*'), array('b_owner' => get_member(), 'b_folder' => $folder), '', 1);

                if ($child_cutoff !== null) {
                    if (count($children_rows) > $child_cutoff) {
                        return $nodes;
                    }
                }

                $children = array();
                foreach ($children_rows as $child_row) {
                    $child_page_link = $zone . ':' . $page . ':view:' . strval($child_row['id']);
                    $child_node = $this->get_node($child_page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level + 1, $require_permission_support, $zone, $use_page_groupings, $consider_secondary_categories, $consider_validation, $meta_gather, $child_row);
                    if ($child_node !== null) {
                        $children[] = $child_node;
                    }
                }
                sort_maps_by($children, 'title');

                $struct['children'] = $children;

                if ($callback !== null) {
                    call_user_func($callback, $struct);
                }
            }
        }

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}
