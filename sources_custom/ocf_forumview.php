<?php

/**
 * Render a topic row (i.e. a row in a forum or results view), from given details (from ocf_get_topic_array).
 *
 * @param  array                        The details (array containing: last_post_id, id, modifiers, emoticon, first_member_id, first_username, first_post, num_posts, num_views).
 * @param  boolean                      Whether the viewing member has the facility to mark off topics (send as false if there are no actions for them to perform).
 * @param  boolean                      Whether the topic is a Private Topic.
 * @param  ?string                      The forum name (NULL: do not show the forum name).
 * @return tempcode                     The topic row.
 */
function ocf_render_topic($topic, $has_topic_marking, $pt = false, $show_forum = null)
{
    $ret = non_overridden__ocf_render_topic($topic, $has_topic_marking, $pt, $show_forum);

    if (empty($topic['forum_id'])) {
        return $ret;
    }

    $forum_id = $topic['forum_id'];

    $is_ticket = false;
    if (addon_installed('tickets')) {
        require_code('tickets');
        if (is_ticket_forum($forum_id)) {
            $is_ticket = true;
        }
    }
    if ($is_ticket) {
        require_lang('tickets');
        require_code('feedback');
        $ticket_id = extract_topic_identifier($topic['description']);
        $ticket_type_id = $GLOBALS['SITE_DB']->query_select_value_if_there('tickets', 'ticket_type', array('ticket_id' => $ticket_id));
        $ticket_type_name = mixed();
        if (!is_null($ticket_type_id)) {
            $_ticket_type_name = $GLOBALS['SITE_DB']->query_select_value_if_there('ticket_types', 'ticket_type_name', array('id' => $ticket_type_id));

            $d = new ocp_tempcode();
            $d->attach(div(escape_html($topic['description'])));
            $ticket_type_name = get_translated_text($_ticket_type_name);
            $d->attach(div(escape_html($ticket_type_name)));

            $d->attach(get_ocportal_support_timings(!in_array('closed', $topic['modifiers']), $topic['last_member_id'], $ticket_type_name, $topic['last_time']));

            $ret->singular_bind('DESCRIPTION', protect_from_escaping($d));
        }
    }

    return $ret;
}