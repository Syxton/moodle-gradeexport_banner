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
 * Dump for export.
 *
 * @copyright  2023 Matt Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/export/banner/grade_export_xls.php');

$id                 = required_param('id', PARAM_INT);
$groupid            = optional_param('groupid', 0, PARAM_INT);
$itemids            = required_param('itemids', PARAM_RAW);
$exportfeedback     = false;
$displaytype        = 3;
$decimalpoints      = 2;
$onlyactive         = true;

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

require_user_key_login('grade/export', $id);

if (empty($CFG->gradepublishing)) {
    throw new \moodle_exception('gradepubdisable');
}

$context = context_course::instance($id);
require_capability('moodle/grade:export', $context);
require_capability('gradeexport/xls:view', $context);
require_capability('gradeexport/xls:publish', $context);

if (!groups_group_visible($groupid, $COURSE)) {
    throw new \moodle_exception('cannotaccessgroup', 'grades');
}

// Get all url parameters and create an object to simulate a form submission.
$formdata = grade_export::export_bulk_export_data($id, $itemids, $exportfeedback, $onlyactive, $displaytype,
        $decimalpoints);

$export = new grade_export_xls($course, $groupid, $formdata);
$export->print_grades();


