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
 * Callback implementations for Purge old assignments
 *
 * @package    local_purgeoldassignments
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
 * Inject a button for cleaning up old assignments.
 *
 * @return string|void
 */
function local_purgeoldassignments_extend_settings_navigation(navigation_node $navigation, $context) {
    global $PAGE;
    $cm = $PAGE->cm;

    if (!$cm) {
        return false;
    }
    if ($cm->modname != 'assign') {
       return false;
    }
    if (!$assignsettingsnode = $navigation->find('modulesettings', navigation_node::TYPE_SETTING)) {
        return;
    }
    if (!has_capability('local/purgeoldassignments:purgeassignments', $context)) {
        return;
    }
    $node = navigation_node::create(get_string('pluginname', 'local_purgeoldassignments'),
                new moodle_url('/local/purgeoldassignments/purge.php', ['id' => $cm->id]),
                navigation_node::TYPE_SETTING);
    $assignsettingsnode->add_node($node);

}

/**
 * Purge selected files.
 *
 * @param int $contextid
 * @param string $component
 * @param int $purge
 * @return int count of deletions.
 */
function local_purgeoldassignments_purge(int $contextid, $component, int $purge) {
    global $DB;

    if (empty($purge) || empty($component) || empty($contextid)) {
        // Safety check - don't allow all files to be deleted.
        return;
    }
    $olderthan = time() - (YEARSECS * $purge);
    
    $sql = "SELECT * 
            FROM {files}
            WHERE timemodified < :olderthan AND component = :component AND contextid = :contextid";
    $params = ['olderthan' => $olderthan, 'component' => $component, 'contextid' => $contextid];
    $records = $DB->get_recordset_sql($sql, $params);
    $fs = get_file_storage();
    $count = 0;
    foreach ($records as $record) {
        $file = $fs->get_file_instance($record);
        if (!$file->is_directory()) {
            $file->delete();
            $count++;
        }
    }
    $records->close();

    return $count;
}
