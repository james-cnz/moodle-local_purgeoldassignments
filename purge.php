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

// File areas we want to allow purging.
$fileareas = ['assignfeedback_editpdf', 
              'assignfeedback_file',
              'assignsubmission_file',
              'local_assignhistory']; // local_assignhistory is a custom client specific area.

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

if (!empty($purge)) {
    $purgeoptions = [1,2,3];
    if (!in_array($purge, $purgeoptions)) {
        echo "invalid purge option";
        die;
    }
    if ($confirm && confirm_sesskey()) {
        local_purgeoldassignments_purge($context->id, $component, $purge);
        echo $OUTPUT->notification(get_string("purgetriggered", 'local_purgeoldassignments'));
       
    } else {
        $cancelurl = new moodle_url('/local/purgeoldassignments/purge.php', ['id' => $id]);
        $url->param('confirm', 1);
        echo $OUTPUT->confirm(get_string('areyousure', 'local_purgeoldassignments', $component), $url, $cancelurl);
    
    }
} else {
    // Get Total size of current areas:
    $filesizes = [];
    foreach ($fileareas as $filearea) {
        $sql = "SELECT sum(filesize) 
                  FROM {files}
                 WHERE component = :component
             and contextid = :context";
        $params = ['component' => $filearea, 'context' => $context->id];
        $filesize = $DB->get_field_sql($sql, $params);
        if (!empty($filesize)) {
            $filesizes[$filearea] = $filesize;
        }
    }
    foreach ($filesizes as $component => $filesize) {
        echo $OUTPUT->heading($component);
        echo get_string('componentcurrentsize', 'local_purgeoldassignments', display_size($filesize));
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

echo $OUTPUT->footer();
