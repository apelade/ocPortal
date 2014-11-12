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
 * @package    core_cleanup_tools
 */

/**
 * Hook class.
 */
class Hook_task_find_orphaned_uploads
{
    /**
     * Run the task hook.
     *
     * @return ?array                   A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
     */
    public function run()
    {
        require_lang('cleanup');

        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        // Find known paths
        $known_urls = array();
        $urlpaths = $GLOBALS['SITE_DB']->query_select('db_meta', array('m_name', 'm_table'), array('m_type' => 'URLPATH'));
        $base_url = get_base_url();
        foreach ($urlpaths as $urlpath) {
            $ofs = $GLOBALS['SITE_DB']->query_select($urlpath['m_table'], array($urlpath['m_name']));
            foreach ($ofs as $of) {
                $url = $of[$urlpath['m_name']];
                if (url_is_local($url)) {
                    $known_urls[rawurldecode($url)] = 1;
                } else {
                    if (substr($url, 0, strlen($base_url)) == $base_url) {
                        $known_urls[substr($url, strlen($base_url) + 1)] = 1;
                    }
                }
            }
        }

        $all_files = $this->do_dir('uploads');
        $orphaned = array();
        foreach ($all_files as $file) {
            if (!array_key_exists($file, $known_urls)) {
                $orphaned[] = array('URL' => get_base_url() . '/' . str_replace('%2F', '/', rawurlencode($file)));
            }
        }

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;

        $ret = do_template('CLEANUP_ORPHANED_UPLOADS', array(
            '_GUID' => '21049d738f67554cff0891d343c02ad3',
            'FOUND' => $orphaned,
        ));
        return array('text/html', $ret);
    }

    /**
     * Search a directory recursively for files.
     *
     * @param  PATH                     Path to search
     * @return array                    List of files
     */
    public function do_dir($dir)
    {
        $out = array();
        $_dir = ($dir == '') ? '.' : $dir;
        $dh = @opendir($_dir);
        if ($dh !== false) {
            while (($file = readdir($dh)) !== false) {
                if (in_array($file, array('filedump', 'auto_thumbs', 'website_specific', 'index.html', '.htaccess'))) {
                    continue;
                }

                if ($file[0] != '.') {
                    if (is_file($_dir . '/' . $file)) {
                        $out[] = $_dir . '/' . $file;
                    } elseif (is_dir($_dir . '/' . $file)) {
                        $out = array_merge($out, $this->do_dir($dir . (($dir != '') ? '/' : '') . $file));
                    }
                }
            }
            closedir($dh);
        }
        return $out;
    }
}
