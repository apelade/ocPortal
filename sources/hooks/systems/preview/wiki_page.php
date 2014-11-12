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
 * @package    wiki
 */

/**
 * Hook class.
 */
class Hook_preview_wiki_page
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array                    Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
     */
    public function applies()
    {
        $applies = (get_param('page', '') == 'cms_wiki');
        return array($applies, 'wiki_page', false);
    }
}
