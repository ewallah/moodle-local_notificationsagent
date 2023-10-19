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
 * local_notificationsagent webservice definitions.
 *
 * @package    local_notificationsagent
 * @category   external
 * @copyright  2023 ISYC <soporte@isyc.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notificationsagent\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/local/notificationsagent/classes/rule.php");
require_once($CFG->dirroot . "/local/notificationsagent/notificationsagent.php");
use notificationsagent\notificationsagent;
use local_notificationsagent\Rule;

/**
 * Rule external API for unlinking a rule from the course.
 *
 * @package    local_notificationsagent
 * @copyright  2023 ISYC <soporte@isyc.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unlink_rule extends \external_api {
    /**
     * Define parameters for external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters(): \external_function_parameters {
        return new \external_function_parameters([
            'ruleid' => new \external_value(PARAM_INT, 'The rule ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Return a list of the required fields
     *
     * @param int $ruleid The rule ID
     * @return array
     */
    public static function execute(int $ruleid) {
        global $DB;

        [
            'ruleid' => $ruleid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'ruleid' => $ruleid,
        ]);

        $result = ['warnings' => []];

        $instance = Rule::create_instance($ruleid);
        if (empty($instance)) {
            throw new \moodle_exception('nosuchinstance', '', '', get_capability_string('local/notificationsagent:nosuchinstance'));
        }
        $context = \context_course::instance($instance->get_courseid());

        try {
            if (has_capability('local/notificationsagent:unlinkrule', $context)) {
                $request = new \stdClass();
                $request->id = $instance->get_id();
                $request->courseid = SITEID;

                $DB->update_record('notificationsagent_rule', $request);

                notificationsagent::delete_triggers_by_ruleid($instance->get_id());
                notificationsagent::delete_cache_by_ruleid($instance->get_id());
            } else {
                throw new \moodle_exception('nopermissions', '', '', get_capability_string('local/notificationsagent:unlinkrule'));
            }
        } catch (\moodle_exception $e) {
            $result['warnings'][] = [
                'item' => 'local_notificationsagent',
                'itemid' => $instance->get_id(),
                'warningcode' => $e->errorcode,
                'message' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): \external_single_structure {
        return new \external_single_structure(
            [
                'warnings' => new \external_warnings(),
            ]
        );
    }
}

