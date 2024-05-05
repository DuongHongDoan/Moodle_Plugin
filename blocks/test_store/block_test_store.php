<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use core_external\util as external_util;

/**
 * Form for editing HTML block instances.
 *
 * @package   block_test_store
 * @copyright 1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_test_store extends block_base
{
    function init()
    {
        $this->title = get_string('pluginname', 'block_test_store');
    }

    function get_content() {
        global $DB, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $sql = "SELECT DISTINCT qa.quiz, u.firstname, u.lastname, q.id qid, qa.id qaid, c.fullname,
                GROUP_CONCAT(DISTINCT q.name SEPARATOR '||') AS name_concat
                FROM {role} r
                JOIN {role_assignments} ra ON r.id = ra.roleid
                JOIN {user} u ON ra.userid = u.id
                JOIN {quiz_attempts} qa ON u.id = qa.userid
                JOIN {quiz} q ON qa.quiz = q.id
                JOIN {course} c ON q.course = c.id
                WHERE r.id = 5
                GROUP BY c.fullname";

        // Lấy kết quả từ truy vấn SQL
        $cnt = $DB->count_records_sql($sql);
        $records = $DB->get_records_sql($sql);
        foreach ($records as $record) {
            $name_course = $record->fullname;
            $name_test_arr = explode('||', $record->name_concat);

            $name_test_list = array();
            foreach($name_test_arr as $name_test) {
                $sql_list_test = "SELECT DISTINCT qa.quiz, q.id AS qid, c.fullname,
                                GROUP_CONCAT(DISTINCT q.name ORDER BY qa.id SEPARATOR '||') AS name_concat,
                                GROUP_CONCAT(DISTINCT qa.id ORDER BY q.id SEPARATOR '||') AS qaid_concat,
                                (
                                    SELECT GROUP_CONCAT(u.username ORDER BY qa_inner.id SEPARATOR '||')
                                    FROM {quiz_attempts} qa_inner
                                    JOIN {user} u ON qa_inner.userid = u.id
                                    WHERE qa_inner.quiz = qa.quiz AND u.id != 2
                                    GROUP BY qa_inner.quiz
                                ) AS username_concat
                                FROM {role} r
                                JOIN {role_assignments} ra ON r.id = ra.roleid
                                JOIN {user} u ON ra.userid = u.id
                                JOIN {quiz_attempts} qa ON u.id = qa.userid
                                JOIN {quiz} q ON qa.quiz = q.id
                                JOIN {course} c ON q.course = c.id
                                WHERE r.id = 5 AND q.name = ?
                                GROUP BY c.fullname";
                $lists = $DB->get_records_sql($sql_list_test, array($name_test));

                $list_test = array();
                if(!empty($lists)) {
                    $list = reset($lists);

                    $username_arr = explode("||", $list->username_concat);
                    $qaid_arr = explode("||", $list->qaid_concat);

                    foreach ($username_arr as $key => $username) {
                        $qaid = $qaid_arr[$key];
                        $href = "../local/test_store/test.php?quizid=$qaid";
                        $pdf_url = "../local/test_store/pdf.php?quizid=$qaid";
                        // Kiểm tra xem quizattemptid đã tồn tại trong bảng hay không
                        $sql_s = "SELECT * FROM {local_link_pdf}
                        WHERE quizattemptid = $qaid";
                        $r = $DB->get_records_sql($sql_s);
                        if(empty($r)) {
                            $d = new stdClass();
                            $d->quizattemptid = $qaid;
                            $d->linkpdf = $pdf_url;

                            $DB->insert_record('local_link_pdf', $d);
                        }
                        $list_test[] = [
                            'username' => $username,
                            'href' => $href
                        ];
                    }
                }

                $name_test_list[] = array(
                    'name_test' => $name_test,
                    'list_test' => $list_test
                );
            }
            // Thêm dữ liệu cho mỗi bài kiểm tra vào mảng $data
            $data[] = array(
                'name_course' => $name_course,
                'name_test_list' => $name_test_list
            );
        }

        $this->content = new stdClass;
        if($cnt > 0) {
            $template_data = array(
                'data' => $data
            );
            $this->content->text = $OUTPUT->render_from_template('block_test_store/link', $template_data);
        }else {
            $this->content->text = 'Không có bài kiểm tra';
        }
        $this->content->footer = '';
        return $this->content;
    }
}

