<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
// Project implemented by the \"Recovery, Transformation and Resilience Plan.
// Funded by the European Union - Next GenerationEU\".
//
// Produced by the UNIMOODLE University Group: Universities of
// Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca,
// Illes Balears, Valencia, Rey Juan Carlos, La Laguna, Zaragoza, Málaga,
// Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria y Burgos.

/**
 * Version details
 *
 * @package    notificationscondition_calendarstart
 * @copyright  2023 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @author     ISYC <soporte@isyc.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace notificationscondition_calendarstart;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../../calendar/lib.php');

use local_notificationsagent\evaluationcontext;
use local_notificationsagent\form\editrule_form;
use local_notificationsagent\notificationconditionplugin;
use local_notificationsagent\rule;

/**
 * Class representing the calendarstart condition plugin.
 */
class calendarstart extends notificationconditionplugin {

    /**
     * Subplugin name
     */
    public const NAME = 'calendarstart';
    /**
     * Constant for the UI.
     */
    public const UI_RADIO_GROUP = 'radiogroup';
    /**
     * Constant for the UI.
     */
    public const UI_RADIO = 'radio';
    /**
     * Constant for the UI.
     */
    public const UI_RADIO_DEFAULT_VALUE = 1;

    /**
     * Subplugin title
     *
     * @return \lang_string|string
     */
    public function get_title() {
        return get_string('conditiontext', 'notificationscondition_calendarstart');
    }

    /**
     *  Subplugins elements
     *
     * @return string[]
     */
    public function get_elements() {
        return ['[TTTT]', '[CCCC]'];
    }

    /**
     * Get the subtype of the condition.
     *
     * @return string The subtype of the condition.
     */
    public function get_subtype() {
        return get_string('subtype', 'notificationscondition_calendarstart');
    }

    /** Evaluates this condition using the context variables or the system's state and the complementary flag.
     *
     * @param evaluationcontext $context  |null collection of variables to evaluate the condition.
     *                                    If null the system's state is used.
     *
     * @return bool true if the condition is true, false otherwise.
     */
    public function evaluate(evaluationcontext $context): bool {
        global $DB;
        $courseid = $context->get_courseid();
        $userid = $context->get_userid();
        $pluginname = $this->get_subtype();
        $params = json_decode($context->get_params());
        $radio = $params->{self::UI_RADIO};
        $meetcondition = false;
        $conditionid = $this->get_id();

        $timeaccess = $context->get_timeaccess();

        $timestart = $DB->get_field(
            'notificationsagent_cache',
            'timestart',
            ['conditionid' => $conditionid, 'courseid' => $courseid, 'userid' => $userid, 'pluginname' => $pluginname],
        );

        if (empty($timestart)) {
            $event = calendar_get_events_by_id([$params->{self::UI_ACTIVITY}]);
            if ($radio == 1) {
                $timestart = $event[$params->{self::UI_ACTIVITY}]->timestart + $params->{self::UI_TIME};
            } else {
                $timestart = $event[$params->{self::UI_ACTIVITY}]->timestart + $event[$params->{self::UI_ACTIVITY}]->timeduration
                    + $params->{self::UI_TIME};
            }

        }

        ($timeaccess >= $timestart) ? $meetcondition = true : $meetcondition = false;

        return $meetcondition;
    }

    /** Estimate next time when this condition will be true.
     *
     * @param evaluationcontext $context Context for the condition evaluation.
     */
    public function estimate_next_time(evaluationcontext $context) {
        $timereturn = null;
        $params = json_decode($context->get_params(), false);
        $event = calendar_get_events_by_id([$params->{self::UI_ACTIVITY}]);
        $timeevent = $event[$params->{self::UI_ACTIVITY}]->timestart;
        $timeduration = $event[$params->{self::UI_ACTIVITY}]->timeduration;
        $timeaccess = $context->get_timeaccess();

        if (empty($timeevent)) {
            return null;
        }

        // Condition.
        if (!$context->is_complementary()) {
            if ($params->{self::UI_RADIO} == 1) {
                if ($timeaccess >= $timeevent && $timeaccess <= $timeevent + $params->{self::UI_TIME}) {
                    $timereturn = $timeevent + $params->{self::UI_TIME};
                } else if ($timeaccess > $timeevent + $params->{self::UI_TIME}) {
                    $timereturn = time();
                }

            } else {
                if ($timeaccess >= $timeevent + $timeduration
                    && $timeaccess <= $timeevent + $timeduration + $params->{self::UI_TIME}
                ) {
                    $timereturn = $timeevent + $timeduration + $params->{self::UI_TIME};
                } else if ($timeaccess > $timeevent + $timeduration + $params->{self::UI_TIME}) {
                    $timereturn = time();
                }

            }
        }

        //Exception.
        if ($context->is_complementary()) {
            if ($params->{self::UI_RADIO} == 1) {
                if ($timeaccess >= $timeevent && $timeaccess < $timeevent + $params->{self::UI_TIME}) {
                    $timereturn = time();
                }
            } else {
                if ($timeaccess >= $timeevent + $timeduration
                    && $timeaccess < $timeevent + $timeduration + $params->{self::UI_TIME}
                ) {
                    $timereturn = time();
                }
            }
        }

        return $timereturn;
    }

    /**
     * Get the UI of the condition.
     *
     * @param mform $mform
     * @param int   $id
     * @param int   $courseid
     * @param int   $type
     */
    public function get_ui($mform, $id, $courseid, $type) {
        $this->get_ui_title($mform, $id, $type);
        global $DB;

        // Calendar.
        $listevents = $DB->get_records_sql("SELECT * FROM {event} WHERE eventtype IN ('course')");

        $events = [];
        foreach ($listevents as $event) {
            $events[$event->id] = format_text($event->name) . " - " . userdate($event->timestart) .
                " - " . userdate($event->timestart + $event->timeduration);
        }

        // Only is template
        if ($this->rule->get_template() == rule::TEMPLATE_TYPE) {
            $events['0'] = 'CCCC';
        }

        $element = $mform->createElement(
            'select', $this->get_name_ui($id, self::UI_ACTIVITY),
            get_string(
                'editrule_condition_calendar', 'notificationscondition_calendarstart',
                ['typeelement' => '[CCCC]']
            ),
            $events
        );

        // Radio.
        $radioarray = [];
        $radioarray[] = $mform->createElement(
            'radio',
            $this->get_name_ui($id, self::UI_RADIO),
            '', get_string('afterstart', 'notificationscondition_calendarstart'), 1
        );
        $radioarray[] = $mform->createElement(
            'radio',
            $this->get_name_ui($id, self::UI_RADIO),
            '', get_string('afterend', 'notificationscondition_calendarstart'), 2
        );

        $radiogroup = $mform->createElement(
            'group', $this->get_name_ui($id, self::UI_RADIO_GROUP),
            '',
            $radioarray, null, false
        );

        $this->get_ui_select_date($mform, $id, $type);
        $mform->insertElementBefore($element, 'new' . $type . '_group');
        $mform->addRule(
            $this->get_name_ui($id, self::UI_ACTIVITY), get_string('editrule_required_error', 'local_notificationsagent'),
            'required'
        );
        $mform->insertElementBefore($radiogroup, 'new' . $type . '_group');
    }

    /**
     * Check capability.
     *
     * @param \context $context
     */
    public function check_capability($context) {
        return has_capability('local/notificationsagent:calendarstart', $context);
    }

    /**
     * Convert parameters.
     *
     * @param int   $id
     * @param array $params
     */
    protected function convert_parameters($id, $params) {
        $params = (array) $params;
        $calendar = $params[$this->get_name_ui($id, self::UI_ACTIVITY)] ?? 0;
        $radio = $params[$this->get_name_ui($id, self::UI_RADIO)] ?? 0;
        $timevalues = [
            'days' => $params[$this->get_name_ui($id, self::UI_DAYS)] ?? 0,
            'hours' => $params[$this->get_name_ui($id, self::UI_HOURS)] ?? 0,
            'minutes' => $params[$this->get_name_ui($id, self::UI_MINUTES)] ?? 0,
            'seconds' => $params[$this->get_name_ui($id, self::UI_SECONDS)] ?? 0,
        ];
        $timeinseconds = ($timevalues['days'] * 24 * 60 * 60) + ($timevalues['hours'] * 60 * 60)
            + ($timevalues['minutes'] * 60) + $timevalues['seconds'];
        $this->set_parameters(
            json_encode([self::UI_TIME => $timeinseconds, self::UI_ACTIVITY => (int) $calendar, self::UI_RADIO => $radio])
        );
        return $this->get_parameters();
    }

    /**
     * Process and replace markups in the supplied content.
     *
     * This function should handle any markup logic specific to a notification plugin,
     * such as replacing placeholders with dynamic data, formatting content, etc.
     *
     * @param array $content  The content to be processed, passed by reference.
     * @param int   $courseid The ID of the course related to the content.
     * @param mixed $options  Additional options if any, null by default.
     *
     * @return void Processed content with markups handled.
     */
    public function process_markups(&$content, $courseid, $options = null) {
        $jsonparams = json_decode($this->get_parameters());

        $paramstoteplace = [to_human_format($jsonparams->{self::UI_TIME}, true)];

        $humanvalue = str_replace($this->get_elements(), $paramstoteplace, $this->get_title());

        $content[] = $humanvalue;
    }

    /**
     * Check if the plugin is generic
     */
    public function is_generic() {
        return true;
    }

    /**
     * Set the defalut values
     *
     * @param editrule_form $form
     * @param int           $id
     *
     * @return void
     */
    public function set_default($form, $id) {
        $params = $this->set_default_select_date($id);
        $params[$this->get_name_ui($id, self::UI_RADIO)] = self::UI_RADIO_DEFAULT_VALUE;
        $form->set_data($params);
    }

    /**
     * Update any necessary ids and json parameters in the database.
     * It is called near the completion of course restoration.
     *
     * @param string       $restoreid Restore identifier
     * @param integer      $courseid  Course identifier
     * @param \base_logger $logger    Logger if any warnings
     *
     * @return bool|void False if restore is not required
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger) {
        global $DB;

        $oldeventid = json_decode($this->get_parameters())->{self::UI_ACTIVITY};
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'event', $oldeventid);

        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('event', ['id' => $oldeventid, 'courseid' => $courseid])) {
                return false;
            }
            // Otherwise it's a warning.
            $logger->process(
                'Restored item (' . $this->get_pluginname() . ')
                has eventid on action that was not restored',
                \backup::LOG_WARNING
            );
        } else {
            $newparameters = json_decode($this->get_parameters());
            $newparameters->{self::UI_ACTIVITY} = $rec->newitemid;
            $newparameters = json_encode($newparameters);

            $record = new \stdClass();
            $record->id = $this->get_id();
            $record->parameters = $newparameters;
            $record->cmid = $rec->newitemid;

            $DB->update_record('notificationsagent_condition', $record);
        }
    }
}
