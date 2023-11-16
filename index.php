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
 * @copyright  2023 Matt Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/grade/export/lib.php');
require_once('grade_export_xls.php');

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/grade/export/xls/index.php', ['id' => $id]);

if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new \moodle_exception('invalidcourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/banner:view', $context);

$actionbar = new \core_grades\output\export_action_bar($context, null, 'banner');
print_grade_page_head($COURSE->id, 'export', 'banner',
    get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_banner'),
    false, false, true, null, null, null, $actionbar);
export_verify_grades($COURSE->id);

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
if ($groupmode == SEPARATEGROUPS && !$currentgroup && !has_capability('moodle/site:accessallgroups', $context)) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

groups_print_course_menu($course, 'index.php?id=' . $id);
echo '<div class="clearer"></div>';
echo '<div style="display: none">';
$mform->display();
echo '</div>';

echo '<div style="text-align:center">
        <button class="bannerbutton btn btn-primary"
                style="display: none;"
                type="submit"
                onclick="$(\'.mform\').first().trigger(\'submit\');">
            Download
        </button>
      </div>
      <script>
        jQuery(window).on("load", function() {
            // Deselect everything.
            $("input:checkbox").prop("checked", false);

            // Select desired settings.
            // Only Letter output.
            $("input:checkbox#id_display_letter").prop("checked", true);

            // Only Active users.
            $("input:checkbox#id_export_onlyactive").prop("checked", true);

            // Only Course total.
            $("#id_gradeitemscontainer input:checkbox:last").prop("checked", true);

            // Enable submit button.
            $(".bannerbutton").show();
        });
      </script>';

echo $OUTPUT->footer();