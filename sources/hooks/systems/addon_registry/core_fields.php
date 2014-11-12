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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_addon_registry_core_fields
{
    /**
     * Get a list of file permissions to set
     *
     * @return array                    File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of ocPortal this addon is for
     *
     * @return float                    Version number
     */
    public function get_version()
    {
        return ocp_version_number();
    }

    /**
     * Get the description of the addon
     *
     * @return string                   Description of the addon
     */
    public function get_description()
    {
        return '(Core fields API)';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_catalogues',
        );
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array                    File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
     * Explicitly say which icon should be used
     *
     * @return URLPATH                  Icon
     */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'lang/EN/fields.ini',
            'sources/fields.php',
            'sources/hooks/systems/addon_registry/core_fields.php',
            'sources/hooks/systems/fields/.htaccess',
            'sources/hooks/systems/fields/video.php',
            'sources/hooks/systems/fields/video_multi.php',
            'sources/hooks/systems/fields/content_link_multi.php',
            'sources/hooks/systems/fields/picture_multi.php',
            'sources/hooks/systems/fields/reference_multi.php',
            'sources/hooks/systems/fields/upload_multi.php',
            'sources/hooks/systems/fields/url_multi.php',
            'sources/hooks/systems/fields/guid.php',
            'sources/hooks/systems/fields/auto_increment.php',
            'sources/hooks/systems/fields/page_link.php',
            'sources/hooks/systems/fields/date.php',
            'sources/hooks/systems/fields/email.php',
            'sources/hooks/systems/fields/float.php',
            'sources/hooks/systems/fields/index.html',
            'sources/hooks/systems/fields/integer.php',
            'sources/hooks/systems/fields/isbn.php',
            'sources/hooks/systems/fields/list.php',
            'sources/hooks/systems/fields/radiolist.php',
            'sources/hooks/systems/fields/long_text.php',
            'sources/hooks/systems/fields/long_trans.php',
            'sources/hooks/systems/fields/picture.php',
            'sources/hooks/systems/fields/random.php',
            'sources/hooks/systems/fields/reference.php',
            'sources/hooks/systems/fields/short_text.php',
            'sources/hooks/systems/fields/short_trans.php',
            'sources/hooks/systems/fields/tick.php',
            'sources/hooks/systems/fields/upload.php',
            'sources/hooks/systems/fields/url.php',
            'sources/hooks/systems/fields/member.php',
            'sources/hooks/systems/fields/posting_field.php',
            'sources/hooks/systems/fields/codename.php',
            'sources/hooks/systems/fields/author.php',
            'sources/hooks/systems/fields/color.php',
            'sources/hooks/systems/fields/password.php',
            'sources/hooks/systems/fields/just_time.php',
            'sources/hooks/systems/fields/just_date.php',
            'sources/hooks/systems/fields/theme_image.php',
            'sources/hooks/systems/fields/content_link.php',
            'sources/hooks/systems/fields/short_text_multi.php',
            'sources/hooks/systems/fields/short_trans_multi.php',
            'sources/hooks/systems/fields/member_multi.php',
            'sources/hooks/systems/fields/multilist.php',
            'sources/hooks/systems/fields/tick_multi.php',
            'sources/hooks/systems/fields/combo.php',
            'sources/hooks/systems/fields/combo_multi.php',
            'themes/default/templates/CATALOGUE_DEFAULT_FIELD_MULTILIST.tpl',
            'themes/default/templates/CATALOGUE_DEFAULT_FIELD_PICTURE.tpl',
            'sources/hooks/systems/symbols/CATALOGUE_ENTRY_FOR.php',
        );
    }

    /**
     * Get mapping between template names and the method of this class that can render a preview of them
     *
     * @return array                    The mapping
     */
    public function tpl_previews()
    {
        return array(
            'CATALOGUE_DEFAULT_FIELD_MULTILIST.tpl' => 'catalogue_multilist',
            'CATALOGUE_DEFAULT_FIELD_PICTURE.tpl' => 'catalogue_picture'
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array                    Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__catalogue_multilist()
    {
        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_FIELD_MULTILIST', array(
                'ALL' => array(
                    array(
                        'HAS' => true,
                        'OPTION' => lorem_phrase(),
                    ),
                    array(
                        'HAS' => false,
                        'OPTION' => lorem_phrase(),
                    )
                ),
                'FIELD_ID' => placeholder_id(),
            )), null, '', true)
        );
    }

    /**
     * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
     * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
     * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
     *
     * @return array                    Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
     */
    public function tpl_preview__catalogue_picture()
    {
        return array(
            lorem_globalise(do_lorem_template('CATALOGUE_DEFAULT_FIELD_PICTURE', array(
                'URL' => placeholder_url(),
                'THUMB_URL' => placeholder_image_url(),
                'I' => '0',
            )), null, '', true)
        );
    }
}
