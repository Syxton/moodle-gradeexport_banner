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
 * Index form.
 *
 * @copyright 2023 Matt Davidson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_xls.php';

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/grade/export/xls/index.php', ['id' => $id]);

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

$totalgrade = $DB->get_record(
    'grade_items',
    ['courseid' => $id, 'itemtype' => 'course']
);

if (!$totalgrade) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/banner:view', $context);

$actionbar = new \core_grades\output\export_action_bar($context, null, 'banner');
$export_to = get_string('exportto', 'grades');
$export_name = get_string('pluginname', 'gradeexport_banner');
print_grade_page_head(
    $id,
    'export',
    'banner',
    $export_to . ' ' . $export_name,
    false,
    false,
    true,
    null,
    null,
    null,
    $actionbar
);
export_verify_grades($id);

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/banner:publish', $context);
}

$actionurl = new moodle_url('/grade/export/banner/export.php');
$formoptions = [
    'publishing' => true,
    'simpleui' => true,
    'multipledisplaytypes' => true,
];

$mform = new grade_export_form($actionurl, $formoptions);

// Groups are being used.
$groupmode    = groups_get_course_groupmode($course);
$currentgroup = groups_get_course_group($course, true);
if ($groupmode == SEPARATEGROUPS
    && !$currentgroup
    && !has_capability('moodle/site:accessallgroups', $context)
) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

groups_print_course_menu($course, 'index.php?id=' . $id);
echo '
    <div class="clearer"></div>
    <div id="fitem_id_bannertype" class="form-group row fitem">
        <div class="col-md-1 col-form-label d-flex pb-0 pr-md-0">
        </div>
        <div class="col-md-2 col-form-label d-flex pb-0 pr-md-0">
            <label
                id="id_bannertype_label"
                class="d-inline word-break"
                for="id_bannertype">
                Export Type
            </label>
        </div>
        <div
            class="col-md-9 form-inline align-items-start felement"
            data-fieldtype="select">
            <select id="id_bannertype" class="custom-select mb-1 mb-md-0 mr-md-2">
                <option value="1" selected>Midterm Grade</option>
                <option value="2">Final Grade</option>
            </select>
        </div>
    </div>
    <div style="display: none">';
$mform->display();
echo '
    </div>
    <div style="text-align:center">
        <button
            class="bannerbutton btn btn-primary"
            style="display: none;"
            type="submit"
            onclick="jQuery(\'#btype\').val(jQuery(\'#id_bannertype\').val());
                jQuery(\'#page-content .mform\').first().trigger(\'submit\');">
            Download
        </button>
    </div>
    <script type="module" src="js/export_banner.js"></script>
    <script type="module">
        window.addEventListener("load", function() {
            grade_export_banner_onload(' . $totalgrade->id . ');
        });
    </script>';

echo $OUTPUT->footer();
