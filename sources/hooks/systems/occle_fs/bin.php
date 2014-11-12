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
 * @package    occle
 */

/**
 * Hook class.
 */
class Hook_occle_fs_bin
{
    /**
     * Standard occle_fs listing function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  object                   A reference to the OcCLE filesystem object
     * @return ~array                   The final directory listing (false: failure)
     */
    public function listing($meta_dir, $meta_root_node, &$occle_fs)
    {
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        $listing = array();
        if (is_dir($path)) {
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if ($file[0] != '.') {
                    $listing[] = array(
                        $file,
                        is_dir($path . '/' . $file) ? OCCLEFS_DIR : OCCLEFS_FILE,
                        is_dir($path . '/' . $file) ? null : filesize($path . '/' . $file),
                        filemtime($path . '/' . $file),
                    );
                }
            }
            return $listing;
        }

        return false; // Directory doesn't exist
    }

    /**
     * Standard occle_fs directory creation function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The new directory name
     * @param  object                   A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function make_directory($meta_dir, $meta_root_node, $new_dir_name, &$occle_fs)
    {
        $new_dir_name = filter_naughty($new_dir_name);
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        if ((is_dir($path)) && (!file_exists($path . '/' . $new_dir_name))) {
            $ret = @mkdir($path . '/' . $new_dir_name, 0777) or warn_exit(do_lang_tempcode('WRITE_ERROR', escape_html($path)));
            fix_permissions($path . '/' . $new_dir_name, 0777);
            sync_file($path . '/' . $new_dir_name);
            return $ret;
        } else {
            return false; // Directory exists
        }
    }

    /**
     * Standard occle_fs directory removal function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The directory name
     * @param  object                   A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function remove_directory($meta_dir, $meta_root_node, $dir_name, &$occle_fs)
    {
        $dir_name = filter_naughty($dir_name);
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        if ((is_dir($path)) && (file_exists($path . '/' . $dir_name))) {
            require_code('files');
            deldir_contents($path . '/' . $dir_name);
            $ret = @rmdir($path . '/' . $dir_name) or warn_exit(do_lang_tempcode('WRITE_ERROR', escape_html($path . '/' . $dir_name)));
            sync_file($path . '/' . $dir_name);
            return true;
        } else {
            return false; // Directory doesn't exist
        }
    }

    /**
     * Standard occle_fs file removal function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The file name
     * @param  object                   A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function remove_file($meta_dir, $meta_root_node, $file_name, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        if ((is_dir($path)) && (file_exists($path . '/' . $file_name))) {
            $ret = @unlink($path . '/' . $file_name) or intelligent_write_error($path . '/' . $file_name);
            sync_file($path . '/' . $file_name);
            return $ret;
        } else {
            return false; // File doesn't exist
        }
    }

    /**
     * Standard occle_fs file reading function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The file name
     * @param  object                   A reference to the OcCLE filesystem object
     * @return ~string                  The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        if ((is_dir($path)) && (file_exists($path . '/' . $file_name)) && (is_readable($path . '/' . $file_name))) {
            return file_get_contents($path . '/' . $file_name);
        } else {
            return false; // File doesn't exist
        }
    }

    /**
     * Standard occle_fs file writing function for OcCLE FS hooks.
     *
     * @param  array                    The current meta-directory path
     * @param  string                   The root node of the current meta-directory
     * @param  string                   The file name
     * @param  string                   The new file contents
     * @param  object                   A reference to the OcCLE filesystem object
     * @return boolean                  Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$occle_fs)
    {
        $file_name = filter_naughty($file_name);
        $path = get_custom_file_base() . '/data/modules/admin_occle';
        foreach ($meta_dir as $meta_dir_section) {
            $path .= '/' . filter_naughty($meta_dir_section);
        }

        if ((is_dir($path)) && (((file_exists($path . '/' . $file_name)) && (is_writable_wrap($path . '/' . $file_name))) || ((!file_exists($path . '/' . $file_name)) && (is_writable_wrap($path))))) {
            $fh = @fopen($path . '/' . $file_name, GOOGLE_APPENGINE ? 'wb' : 'wt') or intelligent_write_error($path . '/' . $file_name);
            $output = fwrite($fh, $contents);
            fclose($fh);
            if ($output < strlen($contents)) {
                warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
            }
            fix_permissions($path . '/' . $file_name);
            sync_file($path . '/' . $file_name);
            return $output;
        } else {
            return false; // File doesn't exist
        }
    }
}
