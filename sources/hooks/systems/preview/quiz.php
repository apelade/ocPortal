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
 * @package    quizzes
 */

/**
 * Hook class.
 */
class Hook_preview_quiz
{
    /**
     * Find whether this preview hook applies.
     *
     * @return array                    Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
     */
    public function applies()
    {
        $applies = (get_param('page', '') == 'cms_quiz') && ((get_param('type') == '_ed') || (get_param('type') == 'ad'));
        return array($applies, null, false);
    }

    /**
     * Run function for preview hooks.
     *
     * @return array                    A pair: The preview, the updated post Comcode
     */
    public function run()
    {
        require_code('quiz');
        require_code('quiz2');

        $questions = array();

        $type = post_param('type');

        // Do a basic parse (just enough to render the quiz)

        $text = post_param('text');
        $_qs = explode("\n\n", $text);
        $qs = array();
        foreach ($_qs as $q) {
            $q = trim($q);
            if ($q != '') {
                $qs[] = $q;
            }
        }

        foreach ($qs as $i => $q) {
            $_as = explode("\n", $q);

            $as = array();
            foreach ($_as as $a) {
                if ($a != '') {
                    if (substr($a, 0, 1) == ':') { // Is an explanation
                        if (count($as) != 0) {
                            $as[count($as) - 1][1] = trim($as[count($as) - 1][1] . "\n" . trim(substr($a, 1)));
                        }
                    } else {
                        $as[] = array($a, '');
                    }
                }
            }

            if (count($as) == 0) {
                continue; // Was only an orphaned explanation, so ignore
            }

            $_q = array_shift($as);
            $question = $_q[0];
            $question_extra_text = $_q[1];
            list($question, $type, $required, $marked, $question_extra_text) = parse_quiz_question_line($question, $as, $question_extra_text);

            // Now we add the answers
            $answers = array();
            foreach ($as as $x => $a) {
                $a[0] = str_replace(' [*]', '', $a[0]);

                $answers[] = array(
                    'id' => $x,
                    'q_answer_text' => $a[0],
                    'q_is_correct' => 1,
                );
            }

            $questions[] = array(
                'id' => $i,
                'q_type' => $type,
                'q_question_text' => $question,
                'q_question_extra_text' => $question_extra_text,
                'answers' => $answers,
                'q_required' => $required,
            );
        }

        $preview = render_quiz($questions);

        return array(do_template('FORM', array('_GUID' => '671da928305bee72d7508beb7687d6df', 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => '', 'TEXT' => '', 'URL' => '', 'HIDDEN' => '', 'FIELDS' => $preview)), null);
    }
}
