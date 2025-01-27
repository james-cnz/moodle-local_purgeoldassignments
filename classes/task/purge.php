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

namespace local_purgeoldassignments\task;

use core\task\adhoc_task;

/**
 * Class purge
 *
 * @package    local_purgeoldassignments
 * @copyright  2025 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class purge extends adhoc_task{
    /**
     * Execute the ad hoc task.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot .'/local/purgeoldassignments/lib.php');

        $contextid = $this->get_custom_data()->contextid;
        $component = $this->get_custom_data()->component;
        $purge = $this->get_custom_data()->purge;
        local_purgeoldassignments_purge($contextid, $component, $purge);
    }
}
