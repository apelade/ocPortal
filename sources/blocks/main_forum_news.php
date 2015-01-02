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
 * @package    forum_blocks
 */

/**
 * Block class.
 */
class Block_main_forum_news
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param', 'forum', 'member_based', 'date_key', 'title', 'optimise');
        return $info;
    }

    /**
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = 'array(array_key_exists(\'optimise\',$map)?$map[\'optimise\']:\'0\',array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'member_based\',$map)?$map[\'member_based\']:\'0\',array_key_exists(\'forum\',$map)?$map[\'forum\']:\'Announcements\',array_key_exists(\'param\',$map)?intval($map[\'param\']):14,array_key_exists(\'date_key\',$map)?$map[\'date_key\']:\'firsttime\')';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT | CACHE_AGAINST_PERMISSIVE_GROUPS;
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 60;
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        if (has_no_forum()) {
            return new Tempcode();
        }

        require_lang('news');
        require_css('news');
        require_code('xhtml');

        $num_topics = array_key_exists('param', $map) ? intval($map['param']) : 14;
        $forum_name = array_key_exists('forum', $map) ? $map['forum'] : do_lang('NEWS');

        $optimise = (array_key_exists('optimise', $map)) && ($map['optimise'] == '1');

        $num_topics = intval($num_topics);

        $date_key = array_key_exists('date_key', $map) ? $map['date_key'] : 'firsttime';

        $rows = array();
        $archive_url = null;
        $submit_url = new Tempcode();

        $forum_ids = array();
        if ((get_forum_type() == 'ocf') && ((strpos($forum_name, ',') !== false) || (preg_match('#\d[-\*\+]#', $forum_name) != 0) || (is_numeric($forum_name)))) {
            require_code('ocfiltering');
            $forum_names = ocfilter_to_idlist_using_db($forum_name, 'id', 'f_forums', 'f_forums', 'f_parent_forum', 'f_parent_forum', 'id', true, true, $GLOBALS['FORUM_DB']);
        } else {
            $forum_names = explode(',', $forum_name);
        }
        foreach ($forum_names as $forum_name) {
            $forum_name = is_integer($forum_name) ? strval($forum_name) : trim($forum_name);

            if ($forum_name == '<announce>') {
                $forum_id = null;
            } else {
                $forum_id = is_numeric($forum_name) ? intval($forum_name) : $GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum_name);
            }

            if (!is_null($forum_id)) {
                $forum_ids[$forum_id] = $forum_name;
                if (is_null($archive_url)) {
                    $archive_url = $GLOBALS['FORUM_DRIVER']->forum_url($forum_id, true); // First forum will count as archive
                    if (get_forum_type() == 'ocf') {
                        $submit_url = build_url(array('page' => 'topics', 'type' => 'new_topic', 'id' => $forum_id), get_module_zone('topics'));
                    }
                }
            }
        }

        $max_rows = 0;
        $rows = $GLOBALS['FORUM_DRIVER']->show_forum_topics($forum_ids, $num_topics, 0, $max_rows, '', true, $date_key);
        if (is_null($rows)) {
            $rows = array();
        }

        sort_maps_by($rows, $date_key);
        $rows = array_reverse($rows, false);

        $_title = do_lang_tempcode('NEWS');
        if ((array_key_exists('title', $map)) && ($map['title'] != '')) {
            $_title = protect_from_escaping(escape_html($map['title']));
        }

        $i = 0;
        $news_text = new Tempcode();
        while (array_key_exists($i, $rows)) {
            $myrow = $rows[$i];

            $id = $myrow['id'];
            $date = get_timezoned_date($myrow[$date_key]);
            $author_url = (((array_key_exists('member_based', $map)) && ($map['member_based'] == '1')) || (!addon_installed('authors'))) ? new Tempcode() : build_url(array('page' => 'authors', 'type' => 'browse', 'author' => $myrow['firstusername']), get_module_zone('authors'));
            $author = $myrow['firstusername'];
            $news_title = $myrow['title'];
            if (is_object($myrow['firstpost'])) {
                $news = $myrow['firstpost'];
                if ($optimise) {
                    $news = make_string_tempcode($news->evaluate());
                    if (multi_lang_content()) {
                        $GLOBALS['SITE_DB']->query_update('translate', array('text_parsed' => $news->to_assembly()), array('id' => $myrow['firstpost_language_string'], 'language' => user_lang()), '', 1);
                    } else {
                        $GLOBALS['SITE_DB']->query_update('f_posts', array('p_post__text_parsed' => $news->to_assembly()), array('id' => $myrow['id']), '', 1);
                    }
                }
            } else {
                $news = make_string_tempcode(xhtmlise_html($myrow['firstpost']));
            }
            if (is_null($news)) {
                $news = '';
            }
            $full_url = $GLOBALS['FORUM_DRIVER']->topic_url($id, '', true);
            $news_text->attach(do_template('NEWS_BOX', array(
                '_GUID' => '12fa98717a768ccbe28884bdbae0313b',
                'GIVE_CONTEXT' => false,
                'TRUNCATE' => false,
                'BLOG' => false,
                'FIRSTTIME' => strval($myrow['firsttime']),
                'LASTTIME' => strval($myrow['lasttime']),
                'CLOSED' => strval($myrow['closed']),
                'FIRSTUSERNAME' => $myrow['firstusername'],
                'LASTUSERNAME' => $myrow['lastusername'],
                'FIRSTMEMBERID' => strval($myrow['firstmemberid']),
                'LASTMEMBERID' => strval($myrow['lastmemberid']),
                'ID' => strval($id),
                'FULL_URL' => $full_url,
                'SUBMITTER' => strval($myrow['firstmemberid']),
                'DATE' => $date,
                'DATE_RAW' => strval($myrow[$date_key]),
                'NEWS_TITLE' => $news_title,
                'NEWS_TITLE_PLAIN' => $news_title,
                'CATEGORY' => '',
                'IMG' => '',
                '_IMG' => '',
                'AUTHOR' => $author,
                'AUTHOR_URL' => $author_url,
                'NEWS' => $news,
                'FORUM_ID' => isset($myrow['forum_id']) ? strval($myrow['forum_id']) : '',
            )));

            $i++;

            if ($i == $num_topics) {
                break;
            }
        }
        if ($news_text->is_empty()) {
            return do_template('BLOCK_NO_ENTRIES', array('_GUID' => 'f55c90205b4c80162494fc5e2b565ce6', 'HIGH' => false, 'TITLE' => $_title, 'MESSAGE' => do_lang_tempcode('NO_NEWS'), 'ADD_NAME' => do_lang_tempcode('ADD_TOPIC'), 'SUBMIT_URL' => $submit_url));
        }

        if (is_null($forum_id)) {
            $archive_url = '';
        }

        return do_template('BLOCK_MAIN_FORUM_NEWS', array(
            '_GUID' => '36b05da9aed5a2056bdb266e2ce4be9f',
            'TITLE' => $_title,
            'FORUM_NAME' => array_key_exists('forum', $map) ? $map['forum'] : do_lang('NEWS'),
            'CONTENT' => $news_text,
            'BRIEF' => new Tempcode(),
            'ARCHIVE_URL' => $archive_url,
            'SUBMIT_URL' => $submit_url,
            'RSS_URL' => '',
            'ATOM_URL' => '',
        ));
    }
}
