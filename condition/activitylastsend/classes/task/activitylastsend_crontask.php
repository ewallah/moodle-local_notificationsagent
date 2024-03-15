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
 * @package    local_notificationsagent
 * @copyright  2023 Proyecto UNIMOODLE
 * @author     UNIMOODLE Group (Coordinator) <direccion.area.estrategia.digital@uva.es>
 * @author     ISYC <soporte@isyc.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace notificationscondition_activitylastsend\task;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../../lib.php');
require_once(__DIR__ . '/../../lib.php');

use core\task\scheduled_task;
use local_notificationsagent\notificationsagent;
use local_notificationsagent\notificationplugin;

class activitylastsend_crontask extends scheduled_task {

    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string
     */
    public function get_name() {
        return get_string('activitylastsend_crontask', 'notificationscondition_activitylastsend');
    }

    /**
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        custom_mtrace("Activity open start");

        $pluginname = 'activitylastsend';
        $conditions = notificationsagent::get_conditions_by_plugin($pluginname);
        $ruleids = [];
        foreach ($conditions as $condition) {
            $courseid = $condition->courseid;
            $ruleids[] = $condition->ruleid;
            $condtionid = $condition->id;
            $decode = $condition->parameters;
            $param = json_decode($decode, true);
            $cmid = $param[notificationplugin::UI_ACTIVITY];
            $results = notificationsagent_condition_activitylastsend_get_cm_endtime($cmid);
            foreach ($results as $result) {
                if (!notificationsagent::was_launched_indicated_times(
                    $condition->ruleid, $condition->ruletimesfired, $courseid, $result->userid)) {
                        $cache = $result->timemodified + $param[notificationplugin::UI_TIME];
                        notificationsagent::set_timer_cache($result->userid, $courseid, $cache, $pluginname, $condtionid, true);
                        notificationsagent::set_time_trigger($condition->ruleid, $condtionid, $result->userid, $courseid, $cache);
                }
            }

        }

        custom_mtrace("Activity open end ");

    }
}
