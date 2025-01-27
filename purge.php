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

/**
 * TODO describe file purge
 *
 * @package    local_purgeoldassignments
 * @copyright  2025 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$purge = optional_param('purge', null, PARAM_INT);
$component = optional_param('component', '', PARAM_COMPONENT);
$confirm = optional_param('confirm', 0, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('local/purgeoldassignments:purgeassignments', $context);

$url = new moodle_url('/local/purgeoldassignments/purge.php', ['id' => $id, 'purge' => $purge, 'component' => $component]);
$PAGE->set_url($url);

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

if (!empty($purge)) {
    $purgeoptions = [1, 2, 3];
    if (!in_array($purge, $purgeoptions)) {
        echo "invalid purge option";
        die;
    }
    if ($confirm && confirm_sesskey()) {
        // Schedule deletion task.
        $task = new \local_purgeoldassignments\task\purge();
        // Add custom data.
        $task->set_custom_data([
            'contextid' => $context->id,
            'component' => $component,
            'purge' => $purge
        ]);
        // Queue it.
        \core\task\manager::queue_adhoc_task($task);

        echo $OUTPUT->notification(get_string("purgetriggered", 'local_purgeoldassignments'));
    } else {
        $cancelurl = new moodle_url('/local/purgeoldassignments/purge.php', ['id' => $id]);
        $url->param('confirm', 1);
        echo $OUTPUT->confirm(get_string('areyousure', 'local_purgeoldassignments', $component), $url, $cancelurl);
    }
} else {
    // Get pending ad-hoc tasks.
    $sql = "SELECT *
              FROM {task_adhoc}
              WHERE (component = 'local_purgeoldassignments' or classname = '\\local_purgeoldassignments\\task\\purge')
              AND customdata like '%contextid\":{$context->id},%'
              AND faildelay = 0";
    $adhoctasks = $DB->get_records_sql($sql);
    $tasksrunning = [];
    if (!empty($adhoctasks)) {
        foreach ($adhoctasks as $task) {
            $customdata = json_decode($task->customdata);
            if ($customdata->contextid == $context->id) {
                $time = !empty($task->timecreated) ? $task->timecreated : $task->nextruntime;
                $tasksrunning[$customdata->component] = $time;
            }
        }
    }

    // Get Total size of current areas.
    $filesizes = local_purgeoldassignments_get_stats($context->id);
    foreach ($filesizes as $component => $filesize) {
        if (!empty($filesize->total)) {
            echo $OUTPUT->heading($component);
            echo "<p>" . get_string('componentcurrentsize', 'local_purgeoldassignments', display_size($filesize->total)) ."</p>";
            if (!empty($filesize->olderthan1)) {
                echo "<p>" .get_string('componentolderthan1', 'local_purgeoldassignments',
                                        display_size($filesize->olderthan1)) ."</p>";
            }
            if (!empty($filesize->olderthan2)) {
                echo "<p>" .get_string('componentolderthan2', 'local_purgeoldassignments',
                                       display_size($filesize->olderthan2)) ."</p>";
            }
            if (!empty($filesize->olderthan3)) {
                echo "<p>" .get_string('componentolderthan3', 'local_purgeoldassignments',
                                       display_size($filesize->olderthan3)) ."</p>";
            }

            if (!empty($tasksrunning[$component])) {
                echo "<div>".get_string("taskpending", "local_purgeoldassignments",
                                         userdate($tasksrunning[$component]))."</div>";
            } else {
                $select = [
                    1 => '1 year',
                    2 => '2 years',
                    3 => '3 years'
                ];
                echo "<div>" . get_string("purgefilesolderthan", "local_purgeoldassignments");
                $url->param('component', $component);
                echo $OUTPUT->single_select(new moodle_url($url), 'purge', $select);
                echo "</div>";
            }
        }
    }

}

echo $OUTPUT->footer();
