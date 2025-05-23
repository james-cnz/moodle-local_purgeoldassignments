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
 * Delete context component files.
 *
 * @package    local_purgeoldassignments
 * @copyright  2025 James Calder and Otago Polytechnic
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/clilib.php");
require_once('../lib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'system' => false,
        'userid' => null,
        'coursecatid' => null,
        'courseid' => null,
        'cmid' => null,
        'blockid' => null,
        'contextid' => null,
        'component' => null,
        'minageyears' => null,
    ], [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

// Check boolean arguments.
foreach (['system'] as $optionname) {
    if (!is_bool($options[$optionname])) {
        cli_error(get_string(
            'cliincorrectvalueerror', 'admin',
            (object)['value' => $options[$optionname], 'option' => $optionname]
        ));
    }
}

// Check non-negative integer arguments.
foreach (['userid', 'coursecatid', 'courseid', 'cmid', 'blockid', 'contextid'] as $optionname) {
    if (isset($options[$optionname]) && (!is_string($options[$optionname]) || !preg_match('/^\d+$/', $options[$optionname]))) {
        cli_error(get_string(
            'cliincorrectvalueerror', 'admin',
            (object)['value' => $options[$optionname], 'option' => $optionname]
        ));
    }
}

// Check string arguments.
foreach (['component'] as $optionname) {
    if (isset($options[$optionname]) && !is_string($options[$optionname])) {
        cli_error(get_string(
            'cliincorrectvalueerror', 'admin',
            (object)['value' => $options[$optionname], 'option' => $optionname]
        ));
    }
}

// Check non-negative number arguments.
foreach (['minageyears'] as $optionname) {
    if (isset($options[$optionname]) && (!is_numeric($options[$optionname]) || ($options[$optionname] < 0))) {
        cli_error(get_string(
            'cliincorrectvalueerror', 'admin',
            (object)['value' => $options[$optionname], 'option' => $optionname]
        ));
    }
}

// Fetch arguments.
$system = $options['system'];
$userid = isset($options['userid']) ? intval($options['userid']) : null;
$coursecatid = isset($options['coursecatid']) ? intval($options['coursecatid']) : null;
$courseid = isset($options['courseid']) ? intval($options['courseid']) : null;
$cmid = isset($options['cmid']) ? intval($options['cmid']) : null;
$blockid = isset($options['blockid']) ? intval($options['blockid']) : null;
$contextid = isset($options['contextid']) ? intval($options['contextid']) : null;
$componentname = $options['component'] ?? null;
$minageyears = isset($options['minageyears']) ? floatval($options['minageyears']) : null;

$help = "Delete context component files.

Options:
 -h, --help             Print out this help
     --system           System
     --userid           User ID
     --coursecatid      Course category ID
     --courseid         Course ID
     --cmid             Course module ID
     --blockid          Block ID
     --contextid        Context ID
     --component        Name of component
     --minageyears      Minimum age in years

";

if ($options['help'] !== false) {
    echo $help;
    exit(0);
}

$context = null;

if ($system) {
    try {
        $newcontext = context_system::instance(0);
    } catch (moodle_exception $e) {
        cli_error("System not found.");
    }
    $context = $newcontext;
}

if (isset($userid)) {
    try {
        $newcontext = context_user::instance($userid);
    } catch (moodle_exception $e) {
        cli_error("User not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, false)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (isset($coursecatid)) {
    try {
        $newcontext = context_coursecat::instance($coursecatid);
    } catch (moodle_exception $e) {
        cli_error("Course category not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, false)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (isset($courseid)) {
    try {
        $newcontext = context_course::instance($courseid);
    } catch (moodle_exception $e) {
        cli_error("Course not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, false)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (isset($cmid)) {
    try {
        $newcontext = context_module::instance($cmid);
    } catch (moodle_exception $e) {
        cli_error("Course module not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, false)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (isset($blockid)) {
    try {
        $newcontext = context_block::instance($blockid);
    } catch (moodle_exception $e) {
        cli_error("Block not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, false)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (isset($contextid)) {
    try {
        $newcontext = context::instance_by_id($contextid);
    } catch (moodle_exception $e) {
        cli_error("Context not found.");
    }
    if (isset($context) && !$context->is_parent_of($newcontext, true)) {
        cli_error("Incompatible contexts specified.");
    }
    $context = $newcontext;
}

if (!isset($context)) {
    cli_error("Context not specified.");
}

if (!isset($componentname)) {
    cli_error("Component not specified.");
}

if (!isset($minageyears)) {
    cli_error("Minium age not specified.");
}

$count = local_purgeoldassignments_purge($context->id, $componentname, $minageyears);
echo "Deleted files count: {$count}\n";
exit(0);
