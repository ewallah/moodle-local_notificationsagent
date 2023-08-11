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
global $CFG ,$SESSION;
require_once($CFG->dirroot . "/local/notificationsagent/classes/notificationactivityconditionplugin.php");
use local_notificationsagent\notification_activityconditionplugin;
class notificationsagent_condition_activityopen extends notification_activityconditionplugin {

    public function get_description() {
        return array(
            'title' => self::get_title(),
            'elements' => self::get_elements(),
            'name' => self::get_subtype()
        );
    }

    protected function get_mod_name() {
        return get_string('modname', 'notificationscondition_activityopen');
    }

    public function get_title() {
        return get_string('conditiontext', 'notificationscondition_activityopen');
    }

    public function get_elements() {
        return array('[TTTT]', '[AAAA]');
    }

    public function get_subtype() {
        return get_string('subtype', 'notificationscondition_activityopen');
    }

    /** Evaluates this condition using the context variables or the system's state and the complementary flag.
     *
     * @param \EvaluationContext $context |null collection of variables to evaluate the condition.
     *                                    If null the system's state is used.
     *
     * @return bool true if the condition is true, false otherwise.
     */
    public function evaluate(EvaluationContext $context): bool {
        // TODO: Implement evaluate() method.
    }

    /** Estimate next time when this condition will be true. */
    public function estimate_next_time() {
        // TODO: Implement estimate_next_time() method.
    }

    /** Returns the name of the plugin
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'notificationscondition_activityopen');
    }

    public function get_ui($mform, $id, $courseid, $exception) {
        global $SESSION;
        // TODO $id.
        $mform->addElement('hidden', 'pluginname'.$id,  $this->get_subtype());
        $mform->setType('pluginname'.$id, PARAM_RAW );
        $mform->addElement('hidden', 'type'.$id,$this->get_type().$id);
        $mform->setType('type'.$id, PARAM_RAW );
        $timegroup = array();
        if(!empty($SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_days'])){
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_days', '',
                array('class' => 'mr-2', 'size' => '7', 'maxlength' => '3', '
                placeholder' => 'Horas',
                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")', 'value' => $SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_days']));
        }else{
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_days', '',
            array('class' => 'mr-2', 'size' => '7', 'maxlength' => '3', '
            placeholder' => 'Horas',
                'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")')); 
        }
        if(!empty($SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_hours'])){
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_hours', '',
                array('class' => 'mr-2', 'size' => '7', 'maxlength' => '2', 'placeholder' => 'Minutos',
                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")', 'value' => $SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_hours']));
        }else{
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_hours', '',
                array('class' => 'mr-2', 'size' => '7', 'maxlength' => '2', 'placeholder' => 'Minutos',
                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")'));
        }
        if(!empty($SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_minutes'])){
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_minutes', '',
                array('class' => 'mr-2', 'size' => '7', 'maxlength' => '2',
                    'placeholder' => 'Segundos',
                    'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")', 'value' => $SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group_time_condition'.$exception.$id.'_time_minutes']));
        }else{
            $timegroup[] =& $mform->createElement('float', 'condition'.$exception.$id.'_time_minutes', '',
            array('class' => 'mr-2', 'size' => '7', 'maxlength' => '2',
                'placeholder' => 'Segundos',
                'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").replace(/(\..*)\./g, "$1")'));
        }
        // TODO Strings.
        $mform->addGroup($timegroup, $this->get_subtype().'_' .$this->get_type() .$exception.'_time_'.$id,
            get_string('editrule_condition_time', 'notificationscondition_activityopen',
                array('typeelement' => '[TTTT]')));    

        $listactivities = array();
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->get_cms() as $cm) {
            $listactivities[$cm->id] = format_string($cm->name);
        }
        if(empty($listactivities)){
            $listactivities['0'] = 'AAAA';
        }
        asort($listactivities);
        $mform->addElement(
            'select', $this->get_subtype().'_' .$this->get_type() .$exception.'_activity_'.$id,
            get_string(
                'editrule_condition_activity', 'notificationscondition_activityopen',
                array('typeelement' => '[AAAA]')
            ),
            $listactivities
        );
        if(!empty($SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group'])){
            $mform->setDefault('condition' .$exception.$id.'_group', $SESSION->NOTIFICATIONS['FORMDEFAULT']['id_condition'.'_group']);
        }
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function get_parameters($params) {
        $timeUnits = array('days', 'hours', 'minutes', 'seconds');
        $timeValues = array(
            'days' => 0,
            'hours' => 0,
            'minutes' => 0,
            'seconds' => 0
        );
        
        $cmid = 0;
    
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    foreach ($timeUnits as $unit) {
                        if (strpos($subKey, $unit) !== false) {
                            $timeValues[$unit] = $subValue;
                        }
                    }
                }
            } elseif (strpos($key, "activity") !== false) {
                $cmid = $value;
            }
        }
    
        $timeInSeconds = ($timeValues['days'] * 24 * 60 * 60) + ($timeValues['hours'] * 60 * 60) + ($timeValues['minutes'] * 60) + $timeValues['seconds'];
        return json_encode(array('time' => $timeInSeconds, 'cmid' => $cmid));
    }
    
}