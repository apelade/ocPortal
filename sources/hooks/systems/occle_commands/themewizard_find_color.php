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
 * @package    themewizard
 */

/**
 * Hook class.
 */
class Hook_occle_command_themewizard_find_color
{
    /**
     * Run function for OcCLE hooks.
     *
     * @param  array                    The options with which the command was called
     * @param  array                    The parameters with which the command was called
     * @param  object                   A reference to the OcCLE filesystem object
     * @return array                    Array of stdcommand, stdhtml, stdout, and stderr responses
     */
    public function run($options, $parameters, &$occle_fs)
    {
        if ((array_key_exists('h', $options)) || (array_key_exists('help', $options))) {
            return array('', do_command_help('themewizard_find_color', array('h'), array(true, true)), '', '');
        } else {
            if (!array_key_exists(0, $parameters)) {
                return array('', '', '', do_lang('MISSING_PARAM', '1', 'themewizard_find_color'));
            }

            $input = $parameters[0];
            if (substr($input, 0, 1) == '#') {
                $input = substr($input, 1);
            }
            if (strlen($input) == 3) {
                $input = $input[0] . $input[0] . $input[1] . $input[1] . $input[2] . $input[2];
            }
            list($ir, $ig, $ib) = array(hexdec(substr($input, 0, 2)), hexdec(substr($input, 2, 2)), hexdec(substr($input, 4, 2)));

            $theme = array_key_exists(1, $parameters) ? $parameters[1] : 'default';

            $results = array();

            require_code('files2');
            $d = get_directory_contents(get_file_base() . '/themes/' . filter_naughty($theme) . '/css');
            foreach ($d as $f) {
                if (substr($f, -4) != '.css') {
                    continue;
                }
                $c = unixify_line_format(file_get_contents(get_file_base() . '/themes/' . filter_naughty($theme) . '/css/' . $f));
                $matches = array();
                $num_matches = preg_match_all('/#([A-Za-f\d]{6}).*\{\$,(.*)\}/', $c, $matches);
                $matches2 = array();
                $num_matches2 = preg_match_all('/\{\$THEME_WIZARD_COLOR,\#([A-Za-f\d]{6}),(.*)\}/', $c, $matches2);
                for ($i = 0; $i < $num_matches2; $i++) {
                    $matches[0][$num_matches] = $matches2[0][$i];
                    $matches[1][$num_matches] = $matches2[1][$i];
                    $matches[2][$num_matches] = $matches2[2][$i];
                    $num_matches++;
                }
                if ($num_matches != 0) {
                    for ($i = 0; $i < $num_matches; $i++) {
                        $color = $matches[1][$i];
                        $equation = $matches[2][$i];
                        list($r, $g, $b) = array(hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
                        $dist = sqrt(pow(floatval($r - $ir), 2.0) + pow(floatval($g - $ig), 2.0) + pow(floatval($b - $ib), 2.0));
                        $results[] = array($color, $dist, $equation, $f, array($r, $g, $b));
                    }
                }
            }

            sort_maps_by($results, 1);
            $results = array_reverse($results);

            $results_printed = '';
            foreach ($results as $i => $result) {
                if ($i < count($results) - 10) {
                    continue;
                }

                $results_printed .= '#' . $result[0];
                $results_printed .= ' (' . $result[2] . ')';
                $results_printed .= ' [';
                if ($result[1] == 0.0) {
                    $results_printed .= '=';
                } else {
                    $results_printed .= '-' . integer_format($result[1]);
                }
                $results_printed .= ']';
                $results_printed .= "\n";
            }

            return array('', '', $results_printed, '');
        }
    }
}
