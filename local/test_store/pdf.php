<?php

/**
 * Form for editing HTML block instances.
 *
 * @package   local_test_store
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('../../../dompdf/autoload.inc.php');
// reference the Dompdf namespace
use Dompdf\Dompdf;

//lay id tu tham so cua URL
$quizid = $_GET['quizid'];
$sql_info = "SELECT DISTINCT qa.id qaid, qa.quiz, qa.state, qa.timestart, qa.timefinish, qa.sumgrades sumqa, q.sumgrades sumq,
            q.grade, q.course, u.firstname, u.lastname, u.username, u.id, g.rawgrade, q.name
            FROM {role} r
            JOIN {role_assignments} ra ON r.id = ra.roleid
            JOIN {user} u ON ra.userid = u.id
            JOIN {quiz_attempts} qa ON u.id = qa.userid
            JOIN {quiz} q ON qa.quiz = q.id
            JOIN {grade_items} gi ON q.course = gi.courseid
            JOIN {grade_grades} g ON gi.id =  g.itemid
            WHERE r.id = 5 AND g.rawgrade is not null AND qa.id = $quizid";
$records = $DB->get_records_sql($sql_info);
foreach($records as $record) {
    $q = $record->qaid;
    $name = $record->name;
    $fname = $record->firstname;
    $lname = $record->lastname;
    $start = $record->timestart;
    $tstart = date("d-m-Y, H:i:s", $start);
    $finish = $record->timefinish;
    $tfinish = date("d-m-Y, H:i:s", $finish);
    $state_info = $record->state;
    $timesum = format_time($finish - $start);
    $sumgradesqa = format_float($record->sumqa, 2);
    $sumgradesq = format_float($record->sumq, 2);
    $rawgrade = format_float(($record->sumqa * $record->grade) / $record->sumq,2);
    $grade = format_float($record->grade, 2);
    $percent = format_float(($rawgrade / $record->grade) * 100, 0);
}
// ----------
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
                WHERE r.id = 5 AND qa.id = $quizid
                GROUP BY qa.id, qa.quiz, qa.state, u.firstname, u.lastname";
$contents = $DB->get_records_sql($sql_content);
foreach($contents as $content) {
    $question_arr = explode("||", $content->questions);
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
        $aa = array();
        if ($qtype == 'truefalse') {
            $answers_array[] = 'Đúng';
            $answers_array[] = 'Sai';
            foreach($answers_array as $answer) {
                if (trim($rs) == $answer) {
                    $checked = 'checked';
                    if ($state == 'gradedright') {
                        $icon_check = '&check;';
                        $appearance = '';
                        $solid_circle = '';
                    } else {
                        $icon_check = '&cross;';
                        $appearance = '';
                        $solid_circle = '';
                    }
                }else {
                    $checked = '';
                    $icon_check = '';
                    $appearance = '';
                    $solid_circle = '';
                }
                if (trim($ra) == $answer && trim($rs) != $answer) {
                    $appearance = 'display: none; width: 12px; height: 12px; border-radius: 50%; background-color: black;';
                    $solid_circle = '<span style="margin-left: 20px; font-size: 18px;">&#x25CF;</span>';
                }
                $aa[] = [
                    'a' => $answer,
                    'checked' => $checked,
                    'icon' => $icon,
                    'icon_color' => $icon_color,
                    'appearance' => $appearance,
                    'icon_check' => $icon_check,
                    'solid_circle' => $solid_circle
                ];
            }
        }else {
            foreach($answers_array as $answer) {
                if (trim($rs) == $answer) {
                    $checked = 'checked';
                    if ($state == 'gradedright') {
                        $icon_check = '&check;';
                        $solid_circle = '';
                        $appearance = '';
                    } else {
                        $icon_check = '&cross;';
                        $solid_circle = '';
                        $appearance = '';
                    }
                } else {
                    $checked = '';
                    $icon_check = '';
                    $appearance = '';
                    $solid_circle = '';
                }
                if (trim($ra) == $answer && trim($rs) != $answer) {
                    $appearance = 'display: none; width: 15px; height: 15px; border-radius: 50%; background-color: black;';
                    $solid_circle = '<span style="margin-left: 20px; font-size: 18px;">&#x25CF;</span>';
                }
                $aa[] = [
                    'a' => $answer,
                    'checked' => $checked,
                    'appearance' => $appearance,
                    'icon_check' => $icon_check,
                    'solid_circle' => $solid_circle
                ];
            }
        }

        $q_info[] = [
            'qtext' => $questiontext,
            'aa' => $aa
        ];
    }
}
// --------------
// instantiate and use the dompdf class
$pdf = new Dompdf();

ob_start();
require_once('detail_test.php');
$html = ob_get_clean();

$pdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$pdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$pdf->render();

// Lặp qua từng trang để thêm số trang
// Tính toán vị trí để đặt số trang ở dưới, chính giữa
$x = $pdf->getCanvas()->get_width() / 2;
$y = $pdf->getCanvas()->get_height() - 30; // Khoảng cách từ dưới lên

$pdf->getCanvas()->page_text($x, $y, "{PAGE_NUM}", null, 8, array(0,0,0));

$filename = "Bai_" . $quizid . ".pdf";
// Output the generated PDF to Browser
$pdf->stream($filename, Array('Attachment'=>0));
?>