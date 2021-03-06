<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../lib.php');

class tool_bulkreset_courses_form extends moodleform {
    public $sort = TOOL_BULKRESET_SORT_SORTORDER;

    public function __construct($actionurl = null, $sort = TOOL_BULKRESET_SORT_SORTORDER) {
        $this->sort = $sort;
        parent::__construct($actionurl);
    }

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('html', html_writer::tag('p', get_string('selectcourses', 'tool_bulkreset')));

        $mform->addElement('html', tool_bulkreset_renderselectallallbuttons());

        $categories = tool_bulkreset_getcategories($this->sort);
        foreach ($categories as $category) {
            if (!$category->coursecount) {
                continue;
            }

            $headername = "coursecategory_{$category->id}";
            $mform->addElement('header', $headername, $category->get_nested_name(false));
            $mform->setExpanded($headername, true);

            $courses = get_courses($category->id);
            $mform->addElement('html', tool_bulkreset_renderselectallbuttons(count($courses) > 1));

            foreach ($courses as $course) {
                $mform->addElement('advcheckbox', "courses[{$course->id}]", $course->fullname, '', ['courses' => 1]);
                $mform->setDefault("courses[{$course->id}]", 1);
            }
        }

        $mform->addElement('html', html_writer::start_tag('hr'));

        $mform->addElement('header', 'schedulingheader', get_string('scheduling', 'tool_bulkreset'));
        $mform->setExpanded('schedulingheader', true);

        $mform->addElement('date_time_selector', 'schedule', get_string('schedule', 'tool_bulkreset'));

        if (tool_bulkreset_resetsettingsenabled()) {
            $mform->addElement('header', 'settingstemplateheader', get_string('settingstemplateheader', 'tool_bulkreset'));

            $mform->setExpanded('settingstemplateheader', true);

            $settings = tool_bulkreset_getsettings();
            $mform->addElement('select', 'settingstemplate', get_string('settingstemplate', 'tool_bulkreset'), $settings);
            $mform->setType('settingstemplate', PARAM_TEXT);
            $mform->setDefault('settingstemplate', 'blank');

            $mform->addElement('static', 'gotoresetsettings', '',
                html_writer::link(new moodle_url('/admin/tool/resetsettings'), get_string('gotoresetsettings', 'tool_bulkreset')));
        }

        $this->add_action_buttons(true, get_string('continue'));
    }

    public function getforwarddata() {
        $data = $this->get_submitted_data();
        $courseids = [];
        foreach ($data->courses as $courseid => $value) {
            if ($value) {
                $courseids[] = $courseid;
            }
        }

        $schedule = isset($data->schedule) && $data->schedule ? $data->schedule : time();

        return (object)[
            'courses' => $courseids,
            'schedule' => $schedule,
            'settingstemplate' => $data->settingstemplate
        ];
    }
}
