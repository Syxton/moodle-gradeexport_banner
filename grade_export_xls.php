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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/export/lib.php');

/**
 * Export for xls files.
 *
 * @package   gradeexport_banner
 * @copyright  2023 Matt Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_export_xls extends grade_export {
    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     * @param stdClass $formdata The validated data from the grade export form.
     */
    public function __construct($course, $groupid, $formdata) {
        parent::__construct($course, $groupid, $formdata);

        // Overrides.
        $this->usercustomfields = true;
    }

    /**
     * To be implemented by child classes
     * @param int $btype Midterm or Final Grade type export.
     */
    public function print_grades(int $btype = 1) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        $exporttracking = $this->track_exports();

        $strgrades = get_string('grades');

        // If this file was requested from a form, then mark download as complete (before sending headers).
        \core_form\util::form_download_complete();

        // Calculate file name.
        $shortname = format_string($this->course->shortname, true, ['context' => context_course::instance($this->course->id)]);
        $downloadfilename = clean_filename("$shortname $strgrades.xls");

        // Get term and CRN.
        $coursecodes = explode("-", $this->course->idnumber);
        $term = $coursecodes[0];
        $crn = $coursecodes[1];

        // Creating a workbook.
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($downloadfilename);
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet($strgrades);

        // Print names of all the fields.
        $firstprofilefields = [(object)["fullname" => "Term Code", "customid" => true, "default" => $term],
                               (object)["fullname" => "CRN", "customid" => true, "default" => $crn],
                               (object)["fullname" => "Student ID", "shortname" => "idnumber"],
        ];

        foreach ($firstprofilefields as $id => $field) {
            $myxls->write_string(0, $id, $field->fullname);
        }

        $pos = count($firstprofilefields);
        $gradetype = $btype == 1 ? "Midterm Grade" : "Final Grade";
        $myxls->write_string(0, $pos++, $gradetype);

        // Print custom field titles on Final Grade export types.
        if ($btype !== 1) {
            $myxls->write_string(0, $pos++, "Last Attended Date");
            $myxls->write_string(0, $pos++, "Incomplete Final Grade");
            $myxls->write_string(0, $pos++, "Extension Date");
        }

        // Print custom field titles on all export types.
        $myxls->write_string(0, $pos++, "Narrative Grade Comment");

        // Print all the lines of data.
        $i = 0;
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields($this->usercustomfields);
        $gui->init();
        while ($userdata = $gui->next_user()) {
            $i++;
            $user = $userdata->user;

            foreach ($firstprofilefields as $id => $field) {
                $fieldvalue = grade_helper::get_user_field_value($user, $field);
                $myxls->write_string($i, $id, $fieldvalue);
            }
            $j = count($firstprofilefields);
            if (!$this->onlyactive) {
                $issuspended = ($user->suspendedenrolment) ? get_string('yes') : '';
                $myxls->write_string($i, $j++, $issuspended);
            }
            foreach ($userdata->grades as $itemid => $grade) {
                if ($exporttracking) {
                    $status = $geub->track($grade);
                }
                foreach ($this->displaytype as $gradedisplayconst) {
                    $gradestr = $this->format_grade($grade, $gradedisplayconst);
                    if (is_numeric($gradestr)) {
                        $myxls->write_number($i, $j++, $gradestr);
                    } else {
                        $gradestr = $gradestr == "-" ? null : $gradestr;
                        $myxls->write_string($i, $j++, $gradestr);
                    }
                }
            }

            // Only on Final Grade export types.
            if ($btype !== 1) {
                // Last attended blank.
                $myxls->write_string($i, $j++, null);
                // Incomplete Final Grade blank.
                $myxls->write_string($i, $j++, null);
                // Extension Date.
                $myxls->write_string($i, $j++, null);
            }

            // Narrative Grade Comment.
            $myxls->write_string($i, $j++, null);
        }

        $gui->close();
        $geub->close();

        // Close the workbook.
        $workbook->close();

        exit;
    }
}
