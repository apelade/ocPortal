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
 * @package    ocf_signatures
 */

/**
 * Hook class.
 */
class Hook_preview_ocf_signature
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array                    Quartet: Whether it applies, the attachment ID type, whether the forum DB is used [optional], list of fields to limit to [optional]
     */
    public function applies()
    {
        require_lang('ocf');

        $member_id = get_param_integer('id', get_member());

        $applies = (get_param('page', '') == 'members') && (post_param('signature', null) !== null);
        if ($applies) {
            require_code('ocf_groups');
            $max_sig_length = ocf_get_member_best_group_property($member_id, 'max_sig_length_comcode');
            if (strlen(post_param('post', '')) > $max_sig_length) {
                warn_exit(do_lang_tempcode('SIGNATURE_TOO_BIG'));
            }
        }
        return array($applies, 'ocf_signature', true, array('post'));
    }
}
