<?php

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
 * External Web Service Template
 *
 * @package    localwstemplate
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/report/elearning/locallib.php');

class local_wstemplate_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello world, ') {
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_RAW, 'prometheus data');
    }

    public static function prometheus_endpoint_parameters(){
        return new external_function_parameters(
            array('categoryid' => new external_value(PARAM_TEXT, 'The category by default it is 0', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    public static function prometheus_endpoint($categoryid = 0){
        global $USER;
        //Parameter validation
        //REQUIRED
        /*$params = self::validate_parameters(self::prometheus_endpoint_parameters(),
            array('welcomemessage' => $categoryid));
        */
        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $config = new stdClass();
        $config -> category = "";
        $config -> context = null;
        $config -> visibility = get_string('hiddenandshownplural', 'report_elearning'); //needed?

        $b = get_data(false, false, $config);

        //prometheus data endpoint format

        //split data in courses and categories
        //format for summary: categorys_total{category="<category>"} <value>
        //format for single datapoint: course_<course>{plugin="<block/mod>"} value

        array_shift($b[0]);
        array_shift($b[0]);
        array_shift($b[1]);
        array_shift($b[1]);
        $categorys = $b[0];
        $courses = $b[1];
        $plugins = getheaders();
        //don't need ID and cat/course
        array_shift($plugins);
        array_shift($plugins);

        $return = "";

        //Categorys first
        //O(n * d) !! d= 72 atm so technically O(n) just watch out cause d is the number of blocks and mods
        for($i = 0; $i < sizeof($categorys); $i++){
            $category = $categorys[$i];
            $name = explode(">", $category[1])[1];
            $name = explode("<", $name)[0];
            //no need for id or name anymore
            array_shift($category);
            array_shift($category);
            // multibyte broke for some reason
            $name = str_replace(" ", "_" , $name);

            for($j=0; $j < sizeof($plugins); $j++){
                $return .= $name . "{plugin=\"{$plugins[$j]}\"} {$category[$j]}" . "\n";
            }

        }

        //now for courses
        for($i = 0; $i < sizeof($courses); $i++){
            $course = $courses[$i];
            $name = explode(">", $course[1])[1];
            $name = explode("<", $name)[0];
            //no need for id or name anymore
            array_shift($course);
            array_shift($course);
            // multibyte broke for some reason
            $name = str_replace(" ", "_" , $name);

            for($j=0; $j < sizeof($plugins); $j++){
                $return .= $name . "{plugin=\"{$plugins[$j]}\"} {$course[$j]}" . "\n";
            }

        }

        return $return;


    }

    public static function prometheus_endpoint_returns(){
        return new external_value(PARAM_TEXT, 'The e-learning report in Prometheus compatible formatting.');
    }


}

