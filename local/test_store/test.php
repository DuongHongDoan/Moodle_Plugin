<?php

/**
 * Form for editing HTML block instances.
 *
 * @package   local_test_store
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$PAGE->set_url(new moodle_url('/local/test_store/test.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Bài thi');
$PAGE->set_heading('Bài thi');

echo $OUTPUT->header();

// --body
$sql_info = "SELECT DISTINCT qa.id qaid, qa.quiz, qa.state, qa.timestart, qa.timefinish, qa.sumgrades sumqa, q.sumgrades sumq,
            q.grade, q.course, u.firstname, u.lastname, u.username, u.id, g.rawgrade
            FROM {role} r
            JOIN {role_assignments} ra ON r.id = ra.roleid
            JOIN {user} u ON ra.userid = u.id
            JOIN {quiz_attempts} qa ON u.id = qa.userid
            JOIN {quiz} q ON qa.quiz = q.id
            JOIN {grade_items} gi ON q.course = gi.courseid
            JOIN {grade_grades} g ON gi.id =  g.itemid
            WHERE r.id = 5 AND g.rawgrade is not null";

$sql_content = "SELECT  qa.id AS qaid,
                        qa.quiz AS quiz_id,
                        qa.state,
                        qa.uniqueid,
                        u.firstname,
                        u.lastname,
                        COUNT(DISTINCT qt.id) AS total_questions,
                        GROUP_CONCAT(DISTINCT qt.questiontext SEPARATOR '||') AS questions,
                        GROUP_CONCAT(qas.answer SEPARATOR '||') AS answers
                FROM {role} r
                JOIN {role_assignments} ra ON r.id = ra.roleid
                JOIN {user} u ON ra.userid = u.id
                JOIN {quiz_attempts} qa ON u.id = qa.userid
                JOIN {quiz} q ON qa.quiz = q.id 
                JOIN {question_attempts} qats ON qats.questionusageid = qa.uniqueid
                JOIN {question} qt ON qats.questionid = qt.id
                JOIN {question_answers} qas ON qt.id = qas.question
                WHERE r.id = 5
                GROUP BY qa.id, qa.quiz, qa.state, u.firstname, u.lastname";

// truy van cau lenh sql
$records = $DB->get_records_sql($sql_info);
$contents = $DB->get_records_sql($sql_content);

// lay quizid tu URL va hien thi bai thi tuong ung voi quizid 
$quizid = optional_param('quizid', 0, PARAM_INT);
foreach ($records as $record) {
    $qaid = $record->qaid;
    if ($quizid == $qaid) {
        $time = format_time($record->timefinish - $record->timestart);
        $rawgrade = ($record->sumqa * $record->grade) / $record->sumq;
        $percent = ($rawgrade / $record->grade) * 100;
        $table_info = [
            'id' => $record->id,
            'username' => $record->username,
            'firstname' => $record->firstname,
            'lastname' => $record->lastname,
            'course' => $record->course,
            'state' => $record->state,
            'timestart' => $record->timestart,
            'timefinish' => $record->timefinish,
            'time' => $time,
            'sumgradesqa' => format_float($record->sumqa, 2),
            'sumgradesq' => format_float($record->sumq, 2),
            'grade' => format_float($record->grade, 2),
            'rawgrade' => format_float($rawgrade, 2),
            'percent' => format_float($percent, 0),
            'questiontext' => strip_tags($record->questiontext),
            'href' => $href
        ];
    }
}

//lay noi dung cau hoi va cau tra loi
foreach ($contents as $content) {
    $qaid = $content->qaid;
    if ($quizid == $qaid) {
        $question_arr = explode("||", $content->questions);
        $cnt_question = 1;
        foreach ($question_arr as $question) {
            $sql_type = "SELECT qats.rightanswer, qats.responsesummary, qtype, qas.state, qats.questionsummary,
                        qt.questiontext 
                        FROM {role} r
                        JOIN {role_assignments} ra ON r.id = ra.roleid
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {quiz_attempts} qa ON u.id = qa.userid
                        JOIN {quiz} q ON qa.quiz = q.id 
                        JOIN {question_attempts} qats ON qats.questionusageid = qa.uniqueid
                        JOIN {question} qt ON qats.questionid = qt.id
                        JOIN {question_attempt_steps} qas ON qats.id = qas.questionattemptid
                        WHERE questiontext = ? AND qa.id = ?
                        AND (qas.state = 'gradedwrong' OR qas.state = 'gradedright')";
            $questiontype = $DB->get_records_sql($sql_type, array($question, $quizid));
            //kiem tra mang co rong, neu khong thi chuyen mang ve ban ghi (record)
            if (!empty($questiontype)) {
                // Lấy phần tử đầu tiên của mảng (chỉ có một bản ghi)
                $first_record = reset($questiontype);

                // Lấy giá trị từ bản ghi đầu tiên
                $qtype = $first_record->qtype;
                $qtext = $first_record->questiontext;
                $ra = $first_record->rightanswer;
                $rs = $first_record->responsesummary;
                $state = $first_record->state;
                $qsummary = $first_record->questionsummary;
            }
            //kiem tra cau tra loi 
            if ($state == 'gradedright') {
                $state = 'correct';
            } else {
                $state = 'incorrect';
            }
            $answers_array = explode("\n", $qsummary);

            // Loại bỏ phần tử đầu tiên (tiêu đề) của mảng
            // unset($answers_array[0]);
            $answers_array = array_map(function ($answer) {
                return preg_replace('/[;:]/', '', trim($answer));
            }, $answers_array);
            // Loại bỏ khoảng trắng và dấu ; ở đầu mỗi dòng
            $answers_array = array_map('trim', $answers_array);
            $answers_array = array_filter($answers_array);
            // Lấy phần tử đầu tiên của mảng $answers_array gán cho tên câu hỏi
            $questiontext = array_shift($answers_array);
            $answer_array = array();
            if ($qtype == 'truefalse') {
                $answers_array[] = 'Đúng';
                $answers_array[] = 'Sai';
                $dem = 'true';
                foreach ($answers_array as $answer) {
                    if (trim($rs) == $answer) {
                        $checked = 'checked';
                        if ($state == 'correct') {
                            $icon = 'check';
                            $icon_color = 'text-success';
                            $appearance = 'width: 12px; height: 12px; border-radius: 50%; background-color: black;';
                        } else {
                            $icon = 'remove';
                            $icon_color = 'text-danger';
                            $appearance = 'width: 12px; height: 12px; border-radius: 50%; background-color: black;';
                        }
                    } else {
                        $checked = '';
                        $icon = '';
                        $icon_color = '';
                        $appearance = '';
                    }
                    if (trim($ra) == $answer && trim($rs) != $answer) {
                        $appearance = 'appearance: none; width: 12px; height: 12px; border-radius: 50%; background-color: black;';
                    }
                    // Gán giá trị của $answer vào mảng $answer_array
                    $answer_array[] = [
                        'a' => $answer,
                        'checked' => $checked,
                        'icon' => $icon,
                        'icon_color' => $icon_color,
                        'appearance' => $appearance,
                        'dem' => $dem
                    ];
                    $dem = 'false';
                }
            } else {
                $dem = 1;
                // Lặp qua các phần tử còn lại của $answers_array
                foreach ($answers_array as $answer) {
                    if (trim($rs) == $answer) {
                        $checked = 'checked';
                        if ($state == 'correct') {
                            $icon = 'check';
                            $icon_color = 'text-success';
                            $appearance = '';
                        } else {
                            $icon = 'remove';
                            $icon_color = 'text-danger';
                            $appearance = '';
                        }
                    } else {
                        $checked = '';
                        $icon = '';
                        $icon_color = '';
                        $appearance = '';
                    }
                    if (trim($ra) == $answer && trim($rs) != $answer) {
                        $appearance = 'appearance: none; width: 12px; height: 12px; border-radius: 50%; background-color: black; margin-left: 9px;';
                    }
                    // Gán giá trị của $answer vào mảng $answer_array
                    $answer_array[] = [
                        'a' => $answer,
                        'checked' => $checked,
                        'icon' => $icon,
                        'icon_color' => $icon_color,
                        'appearance' => $appearance,
                        'dem' => $dem
                    ];
                    $dem++;
                }
            }

            $content_info[] = [
                'questiontext' => $questiontext,
                'ans' => $answer_array,
                'questionid' => $content->uniqueid,
                'cnt_question' => $cnt_question,
                'qtype' => $qtype,
                'state' => $state
            ];
            $cnt_question++;
        }
    }
}

$url = "pdf.php?quizid=$quizid";
$data = [
    'table_info' => $table_info,
    'content_info' => $content_info,
    'increment' => $helper_increment,
    'url' => $url
];
$html = $OUTPUT->render_from_template('local_test_store/test', $data);
echo $html;
// --/body
echo $OUTPUT->footer();
