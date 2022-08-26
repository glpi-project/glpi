<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\ErrorHandler;
use Glpi\RichText\RichText;
use RRule\RRule;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\ICalendar\Recur;
use Sabre\VObject\Reader;

/**
 * Planning Class
 **/
class Planning extends CommonGLPI
{
    public static $rightname = 'planning';

    public static $palette_bg = ['#FFEEC4', '#D4EDFB', '#E1D0E1', '#CDD7A9', '#F8C8D2',
        '#D6CACA', '#D3D6ED', '#C8E5E3', '#FBD5BF', '#E9EBA2',
        '#E8E5E5', '#DBECDF', '#FCE7F2', '#E9D3D3', '#D2DBDC'
    ];

    public static $palette_fg = ['#57544D', '#59707E', '#5B3B5B', '#3A431A', '#58242F',
        '#3B2727', '#272D59', '#2E4645', '#6F4831', '#46481B',
        '#4E4E4E', '#274C30', '#6A535F', '#473232', '#454545',
    ];

    public static $palette_ev = ['#E94A31', '#5174F2', '#51C9F2', '#FFCC29', '#20C646',
        '#364959', '#8C5344', '#FF8100', '#F600C4', '#0017FF',
        '#000000', '#FFFFFF', '#005800', '#925EFF'
    ];

    public static $directgroup_itemtype = ['PlanningExternalEvent', 'ProjectTask', 'TicketTask', 'ProblemTask', 'ChangeTask'];

    const READMY    =    1;
    const READGROUP = 1024;
    const READALL   = 2048;

    const INFO = 0;
    const TODO = 1;
    const DONE = 2;

    /**
     * @since 0.85
     *
     * @param $nb
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Planning');
    }


    public static function getMenuContent()
    {
        $menu = [];

        if (Planning::canView()) {
            $menu = [
                'title'    => static::getMenuName(),
                'shortcut' => static::getMenuShorcut(),
                'page'     => static::getSearchURL(false),
                'icon'     => static::getIcon(),
            ];

            if ($data = static::getAdditionalMenuLinks()) {
                $menu['links'] = $data;
            }

            if ($options = static::getAdditionalMenuOptions()) {
                $menu['options'] = $options;
            }
        }

        return $menu;
    }


    public static function getAdditionalMenuLinks()
    {
        global $CFG_GLPI;

        $links = [];

        if (Planning::canView()) {
            $title     = Planning::getTypeName(Session::getPluralNumber());
            $planning  = "<i class='fa far fa-calendar-alt pointer' title='$title'>
                        <span class='sr-only'>$title</span>
                       </i>";

            $links[$planning] = Planning::getSearchURL(false);
        }

        if (PlanningExternalEvent::canView()) {
            $ext_title = PlanningExternalEvent::getTypeName(Session::getPluralNumber());
            $external  = "<i class='fa fas fa-calendar-week pointer' title='$ext_title'>
                        <span class='sr-only'>$ext_title</span>
                       </i>";

            $links[$external] = PlanningExternalEvent::getSearchURL(false);
        }

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $caldav_title = __('CalDAV browser interface');
            $caldav  = "<i class='fa fas fa-sync pointer' title='$caldav_title'>
                        <span class='sr-only'>$caldav_title</span>
                       </i>";

            $links[$caldav] = '/caldav.php';
        }

        return $links;
    }


    public static function getAdditionalMenuOptions()
    {
        if (PlanningExternalEvent::canView()) {
            return [
                'external' => [
                    'title' => PlanningExternalEvent::getTypeName(Session::getPluralNumber()),
                    'page'  => PlanningExternalEvent::getSearchURL(false),
                    'links' => [
                        'add'    => '/front/planningexternalevent.form.php',
                        'search' => '/front/planningexternalevent.php',
                    ] + static::getAdditionalMenuLinks()
                ]
            ];
        }
        return false;
    }


    /**
     * @see CommonGLPI::getMenuShorcut()
     *
     * @since 0.85
     **/
    public static function getMenuShorcut()
    {
        return 'p';
    }


    /**
     * @since 0.85
     **/
    public static function canView()
    {

        return Session::haveRightsOr(self::$rightname, [self::READMY, self::READGROUP,
            self::READALL
        ]);
    }


    public function defineTabs($options = [])
    {

        $ong               = [];
        $ong['no_all_tab'] = true;

        $this->addStandardTab(__CLASS__, $ong, $options);

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            $tabs[1] = self::getTypeName();

            return $tabs;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1: // all
                    Planning::showPlanning($_SESSION['glpiID']);
                    break;
            }
        }
        return true;
    }


    /**
     * Get planning state name
     *
     * @param $value status ID
     **/
    public static function getState($value)
    {

        switch ($value) {
            case static::INFO:
                return _n('Information', 'Information', 1);

            case static::TODO:
                return __('To do');

            case static::DONE:
                return __('Done');
        }
    }


    /**
     * Dropdown of planning state
     *
     * @param $name   select name
     * @param $value  default value (default '')
     * @param $display  display of send string ? (true by default)
     * @param $options  options
     **/
    public static function dropdownState($name, $value = '', $display = true, $options = [])
    {

        $values = [static::INFO => _n('Information', 'Information', 1),
            static::TODO => __('To do'),
            static::DONE => __('Done')
        ];

        return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
            'display' => $display
        ], $options));
    }


    /**
     * Check already planned user for a period
     *
     * @param integer $users_id user id
     * @param string  $begin    begin date
     * @param string  $end      end date
     * @param array   $except   items which not be into account ['Reminder' => [1, 2, id_of_items]]
     **/
    public static function checkAlreadyPlanned($users_id, $begin, $end, $except = [])
    {
        global $CFG_GLPI;

        $planned = false;
        $message = '';

        foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            $item = new $itemtype();
            $data = $item->populatePlanning([
                'who'           => $users_id,
                'whogroup'      => 0,
                'begin'         => $begin,
                'end'           => $end,
                'check_planned' => true
            ]);
            if (isPluginItemType($itemtype)) {
                if (isset($data['items'])) {
                    $data = $data['items'];
                } else {
                    $data = [];
                }
            }

            if (
                count($data)
                && method_exists($itemtype, 'getAlreadyPlannedInformation')
            ) {
                foreach ($data as $val) {
                    if (
                        !isset($except[$itemtype])
                        || (is_array($except[$itemtype]) && !in_array($val['id'], $except[$itemtype]))
                    ) {
                         $planned  = true;
                         $message .= '- ' . $item->getAlreadyPlannedInformation($val);
                         $message .= '<br/>';
                    }
                }
            }
        }
        if ($planned) {
            $user = new User();
            $user->getFromDB($users_id);
            Session::addMessageAfterRedirect(
                sprintf(
                    __('The user %1$s is busy at the selected timeframe.'),
                    '<a href="' . $user->getFormURLWithID($users_id) . '">' . $user->getName() . '</a>'
                ) . '<br/>' . $message,
                false,
                WARNING
            );
        }
        return $planned;
    }


    /**
     * Show the availability of a user
     *
     * @since 0.83
     *
     * @param $params   array of params
     *    must contain :
     *          - begin: begin date to check (default '')
     *          - end: end date to check (default '')
     *          - itemtype : User or Object type (Ticket...)
     *          - foreign key field of the itemtype to define which item to used
     *    optional :
     *          - limitto : limit display to a specific user
     *
     * @return void
     **/
    public static function checkAvailability($params = [])
    {
        global $CFG_GLPI;

        if (!isset($params['itemtype'])) {
            return false;
        }
        if (!($item = getItemForItemtype($params['itemtype']))) {
            return false;
        }
        if (
            !isset($params[$item->getForeignKeyField()])
            || !$item->getFromDB($params[$item->getForeignKeyField()])
        ) {
            return false;
        }
       // No limit by default
        if (!isset($params['limitto'])) {
            $params['limitto'] = 0;
        }
        if (isset($params['begin']) && !empty($params['begin'])) {
            $begin = $params['begin'];
        } else {
            $begin = date("Y-m-d");
        }
        if (isset($params['end']) && !empty($params['end'])) {
            $end = $params['end'];
        } else {
            $end = date("Y-m-d");
        }

        if ($end < $begin) {
            $end = $begin;
        }
        $realbegin = $begin . " " . $CFG_GLPI["planning_begin"];
        $realend   = $end . " " . $CFG_GLPI["planning_end"];
        if ($CFG_GLPI["planning_end"] == "24:00") {
            $realend = $end . " 23:59:59";
        }

        $users = [];

        switch ($item->getType()) {
            case 'User':
                $users[$item->getID()] = $item->getName();
                break;

            default:
                if (is_a($item, 'CommonITILObject', true)) {
                    foreach ($item->getUsers(CommonITILActor::ASSIGN) as $data) {
                        $users[$data['users_id']] = getUserName($data['users_id']);
                    }
                    foreach ($item->getGroups(CommonITILActor::ASSIGN) as $data) {
                        foreach (Group_User::getGroupUsers($data['groups_id']) as $data2) {
                            $users[$data2['id']] = formatUserName(
                                $data2["id"],
                                $data2["name"],
                                $data2["realname"],
                                $data2["firstname"]
                            );
                        }
                    }
                }
                if ($itemtype = 'Ticket') {
                    $task = new TicketTask();
                } else if ($itemtype = 'Problem') {
                    $task = new ProblemTask();
                }
                if ($task->getFromDBByCrit(['tickets_id' => $item->fields['id']])) {
                    $users['users_id'] = getUserName($task->fields['users_id_tech']);
                    $group_id = $task->fields['groups_id_tech'];
                    if ($group_id) {
                        foreach (Group_User::getGroupUsers($group_id) as $data2) {
                             $users[$data2['id']] = formatUserName(
                                 $data2["id"],
                                 $data2["name"],
                                 $data2["realname"],
                                 $data2["firstname"]
                             );
                        }
                    }
                }
                break;
        }
        asort($users);
       // Use get method to check availability
        echo "<div class='center'><form method='GET' name='form' action='planning.php'>\n";
        echo "<table class='tab_cadre_fixe'>";
        $colspan = 5;
        if (count($users) > 1) {
            $colspan++;
        }
        echo "<tr class='tab_bg_1'><th colspan='$colspan'>" . __('Availability') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Start') . "</td>\n";
        echo "<td>";
        Html::showDateField("begin", ['value'      => $begin,
            'maybeempty' => false
        ]);
        echo "</td>\n";
        echo "<td>" . __('End') . "</td>\n";
        echo "<td>";
        Html::showDateField("end", ['value'      => $end,
            'maybeempty' => false
        ]);
        echo "</td>\n";
        if (count($users) > 1) {
            echo "<td width='40%'>";
            $data = [0 => __('All')];
            $data += $users;
            Dropdown::showFromArray('limitto', $data, ['width' => '100%',
                'value' => $params['limitto']
            ]);
            echo "</td>";
        }

        echo "<td class='center'>";
        echo "<input type='hidden' name='" . $item->getForeignKeyField() . "' value=\"" . $item->getID() . "\">";
        echo "<input type='hidden' name='itemtype' value=\"" . $item->getType() . "\">";
        echo "<input type='submit' class='btn btn-primary' name='checkavailability' value=\"" .
             _sx('button', 'Search') . "\">";
        echo "</td>\n";

        echo "</tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>\n";

        if (($params['limitto'] > 0) && isset($users[$params['limitto']])) {
            $displayuser[$params['limitto']] = $users[$params['limitto']];
        } else {
            $displayuser = $users;
        }

        if (count($displayuser)) {
            foreach ($displayuser as $who => $whoname) {
                $params = [
                    'who'       => $who,
                    'whogroup'  => 0,
                    'begin'     => $realbegin,
                    'end'       => $realend
                ];

                $interv = [];
                foreach ($CFG_GLPI['planning_types'] as $itemtype) {
                    $interv = array_merge($interv, $itemtype::populatePlanning($params));
                    if (method_exists($itemtype, 'populateNotPlanned')) {
                        $interv = array_merge($interv, $itemtype::populateNotPlanned($params));
                    }
                }

               // Print Headers
                echo "<br><div class='center'><table class='tab_cadre_fixe'>";
                $colnumber  = 1;
                $plan_begin = explode(":", $CFG_GLPI["planning_begin"]);
                $plan_end   = explode(":", $CFG_GLPI["planning_end"]);
                $begin_hour = intval($plan_begin[0]);
                $end_hour   = intval($plan_end[0]);
                if ($plan_end[1] != 0) {
                    $end_hour++;
                }
                $colsize    = floor((100 - 15) / ($end_hour - $begin_hour));
                $timeheader = '';
                for ($i = $begin_hour; $i < $end_hour; $i++) {
                    $from       = ($i < 10 ? '0' : '') . $i;
                    $timeheader .= "<th width='$colsize%' colspan='4'>" . $from . ":00</th>";
                    $colnumber += 4;
                }

               // Print Headers
                echo "<tr class='tab_bg_1'><th colspan='$colnumber'>";
                echo $whoname;
                echo "</th></tr>";
                echo "<tr class='tab_bg_1'><th width='15%'>&nbsp;</th>";
                echo $timeheader;
                echo "</tr>";

                $day_begin = strtotime($realbegin);
                $day_end   = strtotime($realend);

                for ($time = $day_begin; $time < $day_end; $time += DAY_TIMESTAMP) {
                    $current_day   = date('Y-m-d', $time);
                    echo "<tr><th>" . Html::convDate($current_day) . "</th>";
                    $begin_quarter = $begin_hour * 4;
                    $end_quarter   = $end_hour * 4;
                    for ($i = $begin_quarter; $i < $end_quarter; $i++) {
                        $begin_time = date("Y-m-d H:i:s", strtotime($current_day) + ($i) * HOUR_TIMESTAMP / 4);
                        $end_time   = date("Y-m-d H:i:s", strtotime($current_day) + ($i + 1) * HOUR_TIMESTAMP / 4);
                       // Init activity interval
                        $begin_act  = $end_time;
                        $end_act    = $begin_time;

                        reset($interv);
                        while ($data = current($interv)) {
                            if (
                                ($data["begin"] >= $begin_time)
                                && ($data["end"] <= $end_time)
                            ) {
                             // In
                                if ($begin_act > $data["begin"]) {
                                    $begin_act = $data["begin"];
                                }
                                if ($end_act < $data["end"]) {
                                    $end_act = $data["end"];
                                }
                                unset($interv[key($interv)]);
                            } else if (
                                ($data["begin"] < $begin_time)
                                 && ($data["end"] > $end_time)
                            ) {
                            // Through
                                $begin_act = $begin_time;
                                $end_act   = $end_time;
                                next($interv);
                            } else if (
                                ($data["begin"] >= $begin_time)
                                 && ($data["begin"] < $end_time)
                            ) {
                            // Begin
                                if ($begin_act > $data["begin"]) {
                                    $begin_act = $data["begin"];
                                }
                                $end_act = $end_time;
                                next($interv);
                            } else if (
                                ($data["end"] > $begin_time)
                                 && ($data["end"] <= $end_time)
                            ) {
                            //End
                                $begin_act = $begin_time;
                                if ($end_act < $data["end"]) {
                                    $end_act = $data["end"];
                                }
                                unset($interv[key($interv)]);
                            } else { // Defautl case
                                next($interv);
                            }
                        }
                        if ($begin_act < $end_act) {
                            if (
                                ($begin_act <= $begin_time)
                                && ($end_act >= $end_time)
                            ) {
                               // Activity in quarter
                                echo "<td class='notavailable'>&nbsp;</td>";
                            } else {
                             // Not all the quarter
                                if ($begin_act <= $begin_time) {
                                    echo "<td class='partialavailableend'>&nbsp;</td>";
                                } else {
                                    echo "<td class='partialavailablebegin'>&nbsp;</td>";
                                }
                            }
                        } else {
                           // No activity
                            echo "<td class='available'>&nbsp;</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "<tr class='tab_bg_1'><td colspan='$colnumber'>&nbsp;</td></tr>";
                echo "</table></div>";
            }
        }
        echo "<div><table class='tab_cadre'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Caption') . "</th>";
        echo "<td class='available' colspan=8>" . __('Available') . "</td>";
        echo "<td class='notavailable' colspan=8>" . __('Unavailable') . "</td>";
        echo "</tr>";
        echo "</table></div>";
    }


    /**
     * Show the planning
     *
     * Function name change since version 0.84 show() => showPlanning
     * Function prototype changes in 9.1 (no more parameters)
     *
     * @return void
     **/
    public static function showPlanning($fullview = true)
    {
        if (!static::canView()) {
            return false;
        }

        self::initSessionForCurrentUser();

       // scheduler feature key
       // schedular part of fullcalendar is distributed with opensource licence (GLPv3)
       // but this licence is incompatible with GLPI (GPLv2)
       // see https://fullcalendar.io/license
        $scheduler_key = Plugin::doHookFunction('planning_scheduler_key');

        echo "<div" . ($fullview ? " id='planning_container'" : "") . " class='d-flex flex-wrap flex-sm-nowrap'>";

       // define options for current page
        $rand = '';
        if ($fullview) {
           // full planning view (Assistance > Planning)
            Planning::showPlanningFilter();
            $options = [
                'full_view'    => true,
                'default_view' => $_SESSION['glpi_plannings']['lastview'] ?? 'timeGridWeek',
                'license_key'  => $scheduler_key,
                'resources'    => self::getTimelineResources(),
                'now'          => date("Y-m-d H:i:s"),
                'can_create'   => PlanningExternalEvent::canCreate(),
                'can_delete'   => PlanningExternalEvent::canDelete(),
            ];
        } else {
           // short view (on Central page)
            $rand    = rand();
            $options = [
                'full_view'    => false,
                'default_view' => 'listFull',
                'header'       => false,
                'height'       => 'auto',
                'rand'         => $rand,
                'now'          => date("Y-m-d H:i:s"),
            ];
        }

       // display planning (and call js from js/planning.js)
        echo "<div id='planning$rand' class='flex-fill'></div>";
        echo "</div>";

        echo Html::scriptBlock("$(function() {
         GLPIPlanning.display(" . json_encode($options) . ");
         GLPIPlanning.planningFilters();
      });");

        return;
    }

    public static function getTimelineResources()
    {
        $resources = [];
        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning_id => $planning) {
            if ($planning['type'] == 'external') {
                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $planning['name'],
                    'group_id'   => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => null,
                    'items_id'   => null
                ];
                continue; // Ignore external calendars
            }

            $exploded = explode('_', $planning_id);
            if ($planning['type'] == 'group_users') {
                $group_exploded = explode('_', $planning_id);
                $group_id = (int) $group_exploded[1];
                $group = new Group();
                $group->getFromDB($group_id);
                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $group->getName(),
                    'eventAllow' => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => 'Group_User',
                    'items_id'   => $group_id
                ];
                foreach (array_keys($planning['users']) as $planning_id_user) {
                    $child_exploded = explode('_', $planning_id_user);
                    $user = new User();
                    $users_id = (int) $child_exploded[1];
                    $user->getFromDB($users_id);
                    $planning_id_user = "gu_" . $planning_id_user;
                    $resources[] = [
                        'id'         => $planning_id_user,
                        'title'      => $user->getName(),
                        'is_visible' => $planning['display'],
                        'itemtype'   => 'User',
                        'items_id'   => $users_id,
                        'parentId'   => $planning_id,
                    ];
                }
            } else {
                $itemtype   = $exploded[0];
                $object = new $itemtype();
                $users_id = (int) $exploded[1];
                $object->getFromDB($users_id);

                $resources[] = [
                    'id'         => $planning_id,
                    'title'      => $object->getName(),
                    'group_id'   => false,
                    'is_visible' => $planning['display'],
                    'itemtype'   => $itemtype,
                    'items_id'   => $users_id
                ];
            }
        }

        return $resources;
    }

    /**
     * Return a palette array (for example self::$palette_bg)
     * @param  string $palette_name  the short name for palette (bg, fg, ev)
     * @return mixed                 the palette array or false
     *
     * @since  9.1.1
     */
    public static function getPalette($palette_name = 'bg')
    {
        if (in_array($palette_name, ['bg', 'fg', 'ev'])) {
            return self::${"palette_$palette_name"};
        }

        return false;
    }


    /**
     * Return an hexa color from a palette
     * @param  string  $palette_name the short name for palette (bg, fg, ev)
     * @param  integer $color_index  The color index in this palette
     * @return mixed                 the color in hexa (ex: #FFFFFF) or false
     *
     * @since  9.1.1
     */
    public static function getPaletteColor($palette_name = 'bg', $color_index = 0)
    {
        if ($palette = self::getPalette($palette_name)) {
            if ($color_index >= count($palette)) {
                $color_index = $color_index % count($palette);
            }

            return $palette[$color_index];
        }

        return false;
    }

    public static function getPlanningTypes()
    {
        global $CFG_GLPI;

        return array_merge(
            $CFG_GLPI['planning_types'],
            ['NotPlanned', 'OnlyBgEvents']
        );
    }

    /**
     * Init $_SESSION['glpi_plannings'] var with thses keys :
     *  - 'filters' : type of planning available (ChangeTask, Reminder, etc)
     *  - 'plannings' : all plannings definided for current user.
     *
     * If currently logged user, has no plannings or filter, this function wiil init them
     *
     * Also manage color index in $_SESSION['glpi_plannings_color_index']
     *
     * @return void
     */
    public static function initSessionForCurrentUser()
    {
       // new user in planning, init session
        if (!isset($_SESSION['glpi_plannings']['filters'])) {
            $_SESSION['glpi_plannings']['filters']   = [];
            $_SESSION['glpi_plannings']['plannings'] = ['user_' . $_SESSION['glpiID'] => [
                'color'   => self::getPaletteColor('bg', 0),
                'display' => true,
                'type'    => 'user'
            ]
            ];
        }

       // complete missing filters
        $filters = &$_SESSION['glpi_plannings']['filters'];
        $index_color = 0;
        foreach (self::getPlanningTypes() as $planning_type) {
            if (in_array($planning_type, ['NotPlanned', 'OnlyBgEvents']) || $planning_type::canView()) {
                if (!isset($filters[$planning_type])) {
                    $filters[$planning_type] = [
                        'color'   => self::getPaletteColor('ev', $index_color),
                        'display' => !in_array($planning_type, ['NotPlanned', 'OnlyBgEvents']),
                        'type'    => 'event_filter'
                    ];
                }
                $index_color++;
            }
        }

       // compute color index for plannings
        $_SESSION['glpi_plannings_color_index'] = 0;
        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning) {
            if ($planning['type'] == 'group_users') {
                $_SESSION['glpi_plannings_color_index'] += count($planning['users']);
            } else {
                $_SESSION['glpi_plannings_color_index']++;
            }
        }
    }


    /**
     * Display left part of planning who contains filters and planning with delete/toggle buttons
     * and color choosing.
     * Call self::showSingleLinePlanningFilter for each filters and plannings
     *
     * @return void
     */
    public static function showPlanningFilter()
    {
        global $CFG_GLPI;

        $headings = ['filters'    => __("Events type"),
            'plannings'  => __('Plannings')
        ];

        echo "<div id='planning_filter'>";

        echo "<div id='planning_filter_toggle'>";
        echo "<a class='toggle pointer' title='" . __s("Toggle filters") . "'></a>";
        echo "</div>";

        echo "<div id='planning_filter_content'>";
        foreach ($_SESSION['glpi_plannings'] as $filter_heading => $filters) {
            if (!in_array($filter_heading, array_keys($headings))) {
                continue;
            }

            echo "<div>";
            echo "<h3>";
            echo $headings[$filter_heading];
            if ($filter_heading == "plannings") {
                echo "<a class='planning_link planning_add_filter' href='" . $CFG_GLPI['root_doc'] .
                '/ajax/planning.php?action=add_planning_form' . "'>";
                echo "<i class='fas fa-plus-circle'></i>";
                echo "</a>";
            }
            echo "</h3>";
            echo "<ul class='filters'>";
            foreach ($filters as $filter_key => $filter_data) {
                self::showSingleLinePlanningFilter(
                    $filter_key,
                    $filter_data,
                    ['filter_color_index' => 0]
                );
            }
            echo "</ul>";
            echo "</div>";
        }
        echo "</div>"; // #planning_filter_content
        echo "</div>"; // #planning_filter
    }


    /**
     * Display a single line of planning filter.
     * See self::showPlanningFilter function
     *
     * @param $filter_key  : identify curent line of filter
     * @param $filter_data : array of filter date, must contains :
     *   * 'show_delete' (boolean): show delete button
     *   * 'filter_color_index' (integer): index of the color to use in self::$palette_bg
     * @param $options
     *
     * @return void
     */
    public static function showSingleLinePlanningFilter($filter_key, $filter_data, $options = [])
    {
        global $CFG_GLPI;

       // Invalid data, skip
        if (!isset($filter_data['type'])) {
            return;
        }

        $params['show_delete']        = true;
        $params['filter_color_index'] = 0;
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $actor = explode('_', $filter_key);
        $uID = 0;
        $gID = 0;
        $expanded = '';
        if ($filter_data['type'] == 'user') {
            $uID = $actor[1];
            $user = new User();
            $user->getFromDB($actor[1]);
            $title = $user->getName();
        } else if ($filter_data['type'] == 'group_users') {
            $group = new Group();
            $group->getFromDB($actor[1]);
            $title = $group->getName();
            $enabled = $disabled = 0;
            foreach ($filter_data['users'] as $user) {
                if ($user['display']) {
                    $enabled++;
                } else {
                    $disabled++;
                    $filter_data['display'] = false;
                }
            }
            if ($enabled > 0 && $disabled > 0) {
                $expanded = ' expanded';
            }
        } else if ($filter_data['type'] == 'group') {
            $gID = $actor[1];
            $group = new Group();
            $group->getFromDB($actor[1]);
            $title = $group->getName();
        } else if ($filter_data['type'] == 'external') {
            $title = $filter_data['name'];
        } else if ($filter_data['type'] == 'event_filter') {
            if ($filter_key == 'NotPlanned') {
                $title = __('Not planned tasks');
            } else if ($filter_key == 'OnlyBgEvents') {
                $title = __('Only background events');
            } else {
                if (!getItemForItemtype($filter_key)) {
                    return false;
                } else if (!$filter_key::canView()) {
                    return false;
                }
                $title = $filter_key::getTypeName();
            }
        }

        echo "<li event_type='" . $filter_data['type'] . "'
               event_name='$filter_key'
               class='" . $filter_data['type'] . $expanded . "'>";
        Html::showCheckbox([
            'name'          => 'filters[]',
            'value'         => $filter_key,
            'id'            => $filter_key,
            'title'         => $title,
            'checked'       => $filter_data['display']
        ]);

        if ($filter_data['type'] != 'event_filter') {
            $exploded = explode('_', $filter_data['type']);
            $icon = "user";
            if ($exploded[0] === 'group') {
                $icon = "users";
            }
            echo "<i class='actor_icon fa fa-fw fa-$icon'></i>";
        }

        echo "<label for='$filter_key'>";
        echo $title;
        if ($filter_data['type'] == 'external' && !Toolbox::isUrlSafe($filter_data['url'])) {
            $warning = sprintf(__s('URL "%s" is not allowed by your administrator.'), $filter_data['url']);
            echo "<i class='fas fa-exclamation-triangle' title='{$warning}'></i>";
        }
        echo "</label>";

        $color = self::$palette_bg[$params['filter_color_index']];
        if (isset($filter_data['color']) && !empty($filter_data['color'])) {
            $color = $filter_data['color'];
        } else {
            $params['filter_color_index']++;
            $color = self::getPaletteColor('bg', $params['filter_color_index']);
        }

        echo "<span class='ms-auto d-flex align-items-center'>";
       // colors not for groups
        if ($filter_data['type'] != 'group_users' && $filter_key != 'OnlyBgEvents') {
            echo "<span class='color_input'>";
            Html::showColorField(
                $filter_key . "_color",
                ['value' => $color]
            );
            echo "</span>";
        }

        if ($filter_data['type'] == 'group_users') {
            echo "<span class='toggle pointer'></span>";
        }

        if ($filter_data['type'] != 'event_filter') {
            echo "<span class='filter_option dropstart'>";
            echo "<i class='fas fa-ellipsis-v'></i>";
            echo "<ul class='dropdown-menu '>";
            if ($params['show_delete']) {
                echo "<li class='delete_planning dropdown-item' value='$filter_key'>" . __("Delete") . "</li>";
            }
            if ($filter_data['type'] != 'group_users' && $filter_data['type'] != 'external') {
                $url = parse_url($CFG_GLPI["url_base"]);
                $port = 80;
                if (isset($url['port'])) {
                    $port = $url['port'];
                } else if (isset($url['scheme']) && ($url["scheme"] == 'https')) {
                    $port = 443;
                }

                $loginUser = new User();
                $loginUser->getFromDB(Session::getLoginUserID(true));
                $cal_url = "/front/planning.php?genical=1&uID=" . $uID . "&gID=" . $gID .
                       //"&limititemtype=$limititemtype".
                       "&entities_id=" . $_SESSION["glpiactive_entity"] .
                       "&is_recursive=" . $_SESSION["glpiactive_entity_recursive"] .
                       "&token=" . $loginUser->getAuthToken();

                echo "<li class='dropdown-item'><a target='_blank' href='" . $CFG_GLPI["root_doc"] . "$cal_url'>" .
                 _sx("button", "Export") . " - " . __("Ical") . "</a></li>";

                echo "<li class='dropdown-item'><a target='_blank' href='webcal://" . $url['host'] . ":$port" .
                 (isset($url['path']) ? $url['path'] : '') . "$cal_url'>" .
                 _sx("button", "Export") . " - " . __("Webcal") . "</a></li>";

                echo "<li class='dropdown-item'><a target='_blank' href='" . $CFG_GLPI['root_doc'] .
                 "/front/planningcsv.php?uID=" . $uID . "&gID=" . $gID . "'>" .
                 _sx("button", "Export") . " - " . __("CSV") . "</a></li>";

                $caldav_url = $CFG_GLPI['url_base']
                . '/caldav.php/'
                . self::getCaldavBaseCalendarUrl($filter_data['type'] == 'user' ? $user : $group);
                $copy_js = 'copyTextToClipboard("' . $caldav_url . '");'
                . ' alert("' . __s('CalDAV URL has been copied to clipboard') . '");'
                . ' return false;';
                echo "<li class='dropdown-item'><a target='_blank' href='#'
                 onclick='$copy_js'>" .
                 __s("Copy CalDAV URL to clipboard") . "</a></li>";
            }
            echo "</ul>";
            echo "</span>";
        }
        echo "</span>";

        if ($filter_data['type'] == 'group_users') {
            echo "<ul class='group_listofusers filters'>";
            foreach ($filter_data['users'] as $user_key => $userdata) {
                self::showSingleLinePlanningFilter(
                    $user_key,
                    $userdata,
                    ['show_delete'        => false,
                        'filter_color_index' => $params['filter_color_index']
                    ]
                );
            }
            echo "</ul>";
        }

        echo "</li>";
    }


    /**
     * Display ajax form to add actor on planning
     *
     * @return void
     */
    public static function showAddPlanningForm()
    {
        global $CFG_GLPI;

        $rand = mt_rand();
        echo "<form action='" . self::getFormURL() . "'>";
        echo __("Actor") . ": <br>";

        $planning_types = ['user' => User::getTypeName(1)];

        if (Session::haveRightsOr('planning', [self::READGROUP, self::READALL])) {
            $planning_types['group_users'] = __('All users of a group');
            $planning_types['group']       = Group::getTypeName(1);
        }

        $planning_types['external'] = __('External calendar');

        Dropdown::showFromArray(
            'planning_type',
            $planning_types,
            ['display_emptychoice' => true,
                'rand'                =>  $rand
            ]
        );
        echo Html::scriptBlock("
      $(function() {
         $('#dropdown_planning_type$rand').on( 'change', function( e ) {
            var planning_type = $(this).val();
            $('#add_planning_subform$rand').load('" . $CFG_GLPI['root_doc'] . "/ajax/planning.php',
                                                 {action: 'add_'+planning_type+'_form'});
         });
      });");
        echo "<br><br>";
        echo "<div id='add_planning_subform$rand'></div>";
        Html::closeForm();
    }


    /**
     * Display 'User' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @return void
     */
    public static function showAddUserForm()
    {
        $used = [];
        foreach (array_keys($_SESSION['glpi_plannings']) as $actor) {
            $actor = explode("_", $actor);
            if ($actor[0] == "user") {
                $used[] = $actor[1];
            }
        }
        echo User::getTypeName(1) . " :<br>";

       // show only users with right to add planning events
        $rights = ['change', 'problem', 'reminder', 'task', 'projecttask'];
       // Can we see only personnal planning ?
        if (!Session::haveRightsOr('planning', [self::READALL, self::READGROUP])) {
            $rights = 'id';
        }
       // Can we see user of my groups ?
        if (
            Session::haveRight('planning', self::READGROUP)
            && !Session::haveRight('planning', self::READALL)
        ) {
            $rights = 'groups';
        }

        User::dropdown(['entity'      => $_SESSION['glpiactive_entity'],
            'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
            'right'       => $rights,
            'used'        => $used
        ]);
        echo "<br /><br />";
        echo Html::hidden('action', ['value' => 'send_add_user_form']);
        echo Html::submit(_sx('button', 'Add'));
    }


    /**
     * Recieve 'User' data from self::showAddPlanningForm and save them to session and DB
     *
     * @param $params (array) : must contais form data (typically $_REQUEST)
     */
    public static function sendAddUserForm($params = [])
    {
        $_SESSION['glpi_plannings']['plannings']["user_" . $params['users_id']]
         = ['color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
             'display' => true,
             'type'    => 'user'
         ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }


    /**
     * Display 'All users of a group' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @return void
     */
    public static function showAddGroupUsersForm()
    {
        echo Group::getTypeName(1) . " : <br>";

        $condition = ['is_task' => 1];
       // filter groups
        if (!Session::haveRight('planning', self::READALL)) {
            $condition['id'] = $_SESSION['glpigroups'];
        }

        Group::dropdown([
            'entity'      => $_SESSION['glpiactive_entity'],
            'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
            'condition'   => $condition
        ]);
        echo "<br /><br />";
        echo Html::hidden('action', ['value' => 'send_add_group_users_form']);
        echo Html::submit(_sx('button', 'Add'));
    }


    /**
     * Recieve 'All users of a group' data from self::showAddGroupUsersForm and save them to session and DB
     *
     * @since 9.1
     *
     * @param $params (array) : must contais form data (typically $_REQUEST)
     */
    public static function sendAddGroupUsersForm($params = [])
    {
        $current_group = &$_SESSION['glpi_plannings']['plannings']["group_" . $params['groups_id'] . "_users"];
        $current_group = ['display' => true,
            'type'    => 'group_users',
            'users'   => []
        ];
        $users = Group_User::getGroupUsers($params['groups_id'], [
            'glpi_users.is_active'  => 1,
            'glpi_users.is_deleted' => 0,
            [
                'OR' => [
                    ['glpi_users.begin_date' => null],
                    ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_users.end_date' => null],
                    ['glpi_users.end_date' => ['>', new QueryExpression('NOW()')]],
                ]
            ]
        ]);

        foreach ($users as $user_data) {
            $current_group['users']['user_' . $user_data['id']] = [
                'color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
                'display' => true,
                'type'    => 'user'
            ];
            $_SESSION['glpi_plannings_color_index']++;
        }
        self::savePlanningsInDB();
    }


    public static function editEventForm($params = [])
    {
        if (!$params['itemtype'] instanceof CommonDBTM) {
            echo "<div class='center'>";
            echo "<a href='" . $params['url'] . "' class='btn btn-outline-secondary'>" .
                "<i class='ti ti-eye'></i>" .
                "<span>" . __("View this item in his context") . "</span>" .
            "</a>";
            echo "</div>";
            echo "<hr>";
            $rand = mt_rand();
            $options = [
                'from_planning_edit_ajax' => true,
                'formoptions'             => "id='edit_event_form$rand'",
                'start'                   => date("Y-m-d", strtotime($params['start']))
            ];
            if (isset($params['parentitemtype'])) {
                $options['parent'] = getItemForItemtype($params['parentitemtype']);
                $options['parent']->getFromDB($params['parentid']);
            }
            $item = getItemForItemtype($params['itemtype']);
            $item->getFromDB((int) $params['id']);
            $item->showForm((int)$params['id'], $options);
            $callback = "glpi_close_all_dialogs();
                      GLPIPlanning.refresh();
                      displayAjaxMessageAfterRedirect();";
            Html::ajaxForm("#edit_event_form$rand", $callback);
        }
    }


    /**
     * Display 'Group' part of self::showAddPlanningForm spcified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @since 9.1
     *
     * @return void
     */
    public static function showAddGroupForm()
    {

        $condition = ['is_task' => 1];
       // filter groups
        if (!Session::haveRight('planning', self::READALL)) {
            $condition['id'] = $_SESSION['glpigroups'];
        }

        echo Group::getTypeName(1) . " : <br>";
        Group::dropdown([
            'entity'      => $_SESSION['glpiactive_entity'],
            'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
            'condition'   => $condition
        ]);
        echo "<br /><br />";
        echo Html::hidden('action', ['value' => 'send_add_group_form']);
        echo Html::submit(_sx('button', 'Add'));
    }


    /**
     * Recieve 'Group' data from self::showAddGroupForm and save them to session and DB
     *
     * @since 9.1
     *
     * @param $params (array) : must contais form data (typically $_REQUEST)
     */
    public static function sendAddGroupForm($params = [])
    {
        $_SESSION['glpi_plannings']['plannings']["group_" . $params['groups_id']]
         = ['color'   => self::getPaletteColor(
             'bg',
             $_SESSION['glpi_plannings_color_index']
         ),
             'display' => true,
             'type'    => 'group'
         ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }


    /**
     * Display 'External' part of self::showAddPlanningForm specified by planning type dropdown.
     * Actually called by ajax/planning.php
     *
     * @since 9.5
     *
     * @return void
     */
    public static function showAddExternalForm()
    {

        $rand = mt_rand();

        echo '<label for ="name' . $rand . '">' . __("Calendar name") . ' : </label> ';
        echo '<br />';
        echo Html::input(
            'name',
            [
                'value' => '',
                'id'    => 'name' . $rand,
            ]
        );
        echo '<br />';
        echo '<br />';

        echo '<label for ="url' . $rand . '">' . __("Calendar URL") . ' : </label> ';
        echo '<br />';
        echo '<input type="url" name="url" id="url' . $rand . '" required>';
        echo '<br /><br />';

        echo Html::hidden('action', ['value' => 'send_add_external_form']);
        echo Html::submit(_sx('button', 'Add'));
    }


    /**
     * Receive 'External' data from self::showAddExternalForm and save them to session and DB
     *
     * @since 9.5
     *
     * @param array $params Form data
     *
     * @return void
     */
    public static function sendAddExternalForm($params = [])
    {
        if (!Toolbox::isUrlSafe($params['url'])) {
            Session::addMessageAfterRedirect(
                sprintf(__('URL "%s" is not allowed by your administrator.'), $params['url']),
                false,
                ERROR
            );
            return;
        }

        $_SESSION['glpi_plannings']['plannings']['external_' . md5($params['url'])] = [
            'color'   => self::getPaletteColor('bg', $_SESSION['glpi_plannings_color_index']),
            'display' => true,
            'type'    => 'external',
            'name'    => $params['name'],
            'url'     => $params['url'],
        ];
        self::savePlanningsInDB();
        $_SESSION['glpi_plannings_color_index']++;
    }


    public static function showAddEventForm($params = [])
    {
        global $CFG_GLPI;

        if (count($CFG_GLPI['planning_add_types']) == 1) {
            $params['itemtype'] = $CFG_GLPI['planning_add_types'][0];
            self::showAddEventSubForm($params);
        } else {
            $rand = mt_rand();
            $select_options = [];
            foreach ($CFG_GLPI['planning_add_types'] as $add_types) {
                $select_options[$add_types] = $add_types::getTypeName(1);
            }
            echo __("Event type") . " : <br>";
            Dropdown::showFromArray(
                'itemtype',
                $select_options,
                ['display_emptychoice' => true,
                    'rand'                => $rand
                ]
            );

            echo Html::scriptBlock("
         $(function() {
            $('#dropdown_itemtype$rand').on('change', function() {
               var current_itemtype = $(this).val();
               $('#add_planning_subform$rand').load('" . $CFG_GLPI['root_doc'] . "/ajax/planning.php',
                                                    {action:   'add_event_sub_form',
                                                     itemtype: current_itemtype,
                                                     begin:    '" . $params['begin'] . "',
                                                     end:      '" . $params['end'] . "'});
            });
         });");
            echo "<br><br>";
            echo "<div id='add_planning_subform$rand'></div>";
        }
    }


    /**
     * Display form after selecting date range in planning
     *
     * @since 9.1
     *
     * @param $params (array): must contains this keys :
     *  - begin : start of selection range.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end : end of selection range.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *
     * @return void
     */
    public static function showAddEventSubForm($params = [])
    {

        $rand   = mt_rand();
        $params = self::cleanDates($params);

        $params['res_itemtype'] = $params['res_itemtype'] ?? '';
        $params['res_items_id'] = $params['res_items_id'] ?? 0;
        if ($item = getItemForItemtype($params['itemtype'])) {
            $item->showForm('', [
                'from_planning_ajax' => true,
                'begin'              => $params['begin'],
                'end'                => $params['end'],
                'res_itemtype'       => $params['res_itemtype'],
                'res_items_id'       => $params['res_items_id'],
                'formoptions'        => "id='ajax_reminder$rand'"
            ]);
            $callback = "glpi_close_all_dialogs();
                      GLPIPlanning.refresh();
                      displayAjaxMessageAfterRedirect();";
            Html::ajaxForm("#ajax_reminder$rand", $callback);
        }
    }


    /**
     * Former front/planning.php before 9.1.
     * Display a classic form to plan an event (with begin fiel and duration)
     *
     * @since 9.1
     *
     * @param $params (array): array of parameters whou should contain :
     *   - id (integer): id of item who receive the planification
     *   - itemtype (string): itemtype of item who receive the planification
     *   - begin (string) : start date of event
     *   - _display_dates (bool) : display dates fields (default true)
     *   - end (optionnal) (string) : end date of event. Ifg missing, it will computerd from begin+1hour
     *   - rand_user (integer) : users_id to check planning avaibility
     *   - rand : specific rand if needed (default is generated one)
     */
    public static function showAddEventClassicForm($params = [])
    {
        global $CFG_GLPI;

        if (isset($params["id"]) && ($params["id"] > 0)) {
            echo "<input type='hidden' name='plan[id]' value='" . $params["id"] . "'>";
        }

        $rand = mt_rand();
        if (isset($params['rand'])) {
            $rand = $params['rand'];
        }

        $display_dates = $params['_display_dates'] ?? true;

        $mintime = $CFG_GLPI["planning_begin"];
        if (isset($params["begin"]) && !empty($params["begin"])) {
            $begin = $params["begin"];
            $begintime = date("H:i:s", strtotime($begin));
            if ($begintime < $mintime) {
                $mintime = $begintime;
            }
        } else {
            $ts = $CFG_GLPI['time_step'] * 60; // passage en minutes
            $time = time() + $ts - 60;
            $time = floor($time / $ts) * $ts;
            $begin = date("Y-m-d H:i", $time);
        }

        if (isset($params["end"]) && !empty($params["end"])) {
            $end = $params["end"];
        } else {
            $end = date("Y-m-d H:i:s", strtotime($begin) + HOUR_TIMESTAMP);
        }

        echo "<table class='planning_classic_card'>";

        if ($display_dates) {
            echo "<tr class='tab_bg_2'><td>" . __('Start date') . "</td><td>";
            Html::showDateTimeField("plan[begin]", [
                'value'      => $begin,
                'maybeempty' => false,
                'canedit'    => true,
                'mindate'    => '',
                'maxdate'    => '',
                'mintime'    => $mintime,
                'maxtime'    => $CFG_GLPI["planning_end"],
                'rand'       => $rand,
            ]);
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_2'><td>" . __('Period') . "&nbsp;";

        if (isset($params["rand_user"])) {
            $_POST['parent_itemtype'] = $params["parent_itemtype"] ?? '';
            $_POST['parent_items_id'] = $params["parent_items_id"] ?? '';
            $_POST['parent_fk_field'] = $params["parent_fk_field"] ?? '';
            echo "<span id='user_available" . $params["rand_user"] . "'>";
            include_once(GLPI_ROOT . '/ajax/planningcheck.php');
            echo "</span>";
        }

        echo "</td><td>";

        $empty_label   = Dropdown::EMPTY_VALUE;
        $default_delay = $params['duration'] ?? 0;
        if ($display_dates) {
            $empty_label   = __('Specify an end date');
            $default_delay = floor((strtotime($end) - strtotime($begin)) / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP) * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
        }

        Dropdown::showTimeStamp("plan[_duration]", [
            'min'        => 0,
            'max'        => 50 * HOUR_TIMESTAMP,
            'value'      => $default_delay,
            'emptylabel' => $empty_label,
            'rand'       => $rand,
        ]);
        echo "<br><div id='date_end$rand'></div>";

        $event_options = [
            'duration'     => '__VALUE__',
            'end'          => $end,
            'name'         => "plan[end]",
            'global_begin' => $CFG_GLPI["planning_begin"],
            'global_end'   => $CFG_GLPI["planning_end"]
        ];

        if ($display_dates) {
            Ajax::updateItemOnSelectEvent(
                "dropdown_plan[_duration]$rand",
                "date_end$rand",
                $CFG_GLPI["root_doc"] . "/ajax/planningend.php",
                $event_options
            );

            if ($default_delay == 0) {
                $params['duration'] = 0;
                Ajax::updateItem("date_end$rand", $CFG_GLPI["root_doc"] . "/ajax/planningend.php", $params);
            }
        }

        echo "</td></tr>\n";

        if (
            (!isset($params["id"]) || ($params["id"] == 0))
            && isset($params['itemtype'])
            && PlanningRecall::isAvailable()
        ) {
            echo "<tr class='tab_bg_2'><td>" . _x('Planning', 'Reminder') . "</td><td>";
            PlanningRecall::dropdown([
                'itemtype' => $params['itemtype'],
                'items_id' => $params['items_id'],
                'rand'     => $rand,
            ]);
            echo "</td></tr>";
        }
        echo "</table>\n";
    }


    /**
     * Clone an event
     *
     * @since 9.5
     *
     * @param array $event the event to clone
     *
     * @return mixed the id (integer) or false if it failed
     */
    public static function cloneEvent(array $event = [])
    {
        $item = new $event['old_itemtype']();
        $item->getFromDB((int) $event['old_items_id']);

        $input = array_merge($item->fields, [
            'plan' => [
                'begin' => date("Y-m-d H:i:s", strtotime($event['start'])),
                'end'   => date("Y-m-d H:i:s", strtotime($event['end'])),
            ],
        ]);
        unset($input['id'], $input['uuid']);

        if (isset($item->fields['name'])) {
            $input['name'] = sprintf(__('Copy of %s'), $item->fields['name']);
        }

       // manage change of assigment for CommonITILTask
        if (
            $item instanceof CommonITILTask
            && isset($event['actor']['itemtype'])
            && isset($event['actor']['items_id'])
        ) {
            switch ($event['actor']['itemtype']) {
                case "group":
                    $key = "groups_id_tech";
                    break;
                case "user":
                    $key = isset($item->fields['users_id_tech']) ? "users_id_tech" : "users_id";
                    break;
            }

            unset(
                $input['users_id_tech'],
                $input['users_id'],
                $input['groups_id_tech'],
                $input['groups_id']
            );

            $input[$key] = $event['actor']['items_id'];
        }

        $new_items_id = $item->add(Toolbox::addslashes_deep($input));

       // manage all assigments for ProjectTask
        if (
            $item instanceof ProjectTask
            && isset($event['actor']['itemtype'])
            && isset($event['actor']['items_id'])
        ) {
            $team = new ProjectTaskTeam();
            $team->add([
                'projecttasks_id' => $new_items_id,
                'itemtype'        => ucfirst($event['actor']['itemtype']),
                'items_id'        => $event['actor']['items_id']
            ]);
        }

        return $new_items_id;
    }

    /**
     * Delete an event
     *
     * @since 9.5
     *
     * @param array $event the event to clone (with itemtype and items_id keys)
     *
     * @return bool
     */
    public static function deleteEvent(array $event = []): bool
    {
        $item = new $event['itemtype']();

        if (
            isset($event['day'])
            && isset($event['instance'])
            && $event['instance']
            && method_exists($item, "deleteInstance")
        ) {
            return $item->deleteInstance((int) $event['items_id'], $event['day']);
        } else {
            return $item->delete([
                'id' => (int) $event['items_id']
            ]);
        }
    }


    /**
     * toggle display for selected line of $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contains :
     *  - type : event type, can be event_filter, user, group or group_users
     *  - parent : in case of type=users_group, must contains the id of the group
     *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
     *  - display : boolean value to set to his line
     * @return void
     */
    public static function toggleFilter($options = [])
    {

        $key = 'filters';
        if (in_array($options['type'], ['user', 'group', 'group_users', 'external'])) {
            $key = 'plannings';
        }
        if (
            !isset($options['parent'])
            || empty($options['parent'])
        ) {
            $_SESSION['glpi_plannings'][$key][$options['name']]['display']
            = ($options['display'] === 'true');
        } else {
            $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['display']
            = ($options['display'] === 'true');
        }
        self::savePlanningsInDB();
    }


    /**
     * change color for selected line of $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contains :
     *  - type : event type, can be event_filter, user, group or group_users
     *  - parent : in case of type=users_group, must contains the id of the group
     *  - name : contains a string with type and id concatened with a '_' char (ex user_41).
     *  - color : rgb color (preceded by '#'' char)
     * @return void
     */
    public static function colorFilter($options = [])
    {
        $key = 'filters';
        if (in_array($options['type'], ['user', 'group', 'group_users', 'external'])) {
            $key = 'plannings';
        }
        if (
            !isset($options['parent'])
            || empty($options['parent'])
        ) {
            $_SESSION['glpi_plannings'][$key][$options['name']]['color'] = $options['color'];
        } else {
            $_SESSION['glpi_plannings']['plannings'][$options['parent']]['users']
            [$options['name']]['color'] = $options['color'];
        }
        self::savePlanningsInDB();
    }


    /**
     * delete selected line in $_SESSION['glpi_plannings']
     *
     * @since 9.1
     *
     * @param  array $options: should contains :
     *  - type : event type, can be event_filter, user, group or group_users
     *  - filter : contains a string with type and id concatened with a '_' char (ex user_41).
     * @return void
     */
    public static function deleteFilter($options = [])
    {

        $current = $_SESSION['glpi_plannings']['plannings'][$options['filter']];
        if ($current['type'] == 'group_users') {
            $_SESSION['glpi_plannings_color_index'] -= count($current['users']);
        } else {
            $_SESSION['glpi_plannings_color_index']--;
        }

        unset($_SESSION['glpi_plannings']['plannings'][$options['filter']]);
        self::savePlanningsInDB();
    }


    public static function savePlanningsInDB()
    {

        $user = new User();
        $user->update(['id' => $_SESSION['glpiID'],
            'plannings' => exportArrayToDB($_SESSION['glpi_plannings'])
        ]);
    }


    /**
     * Prepare a set of events for jquery fullcalendar.
     * Call populatePlanning functions for all $CFG_GLPI['planning_types'] types
     *
     * @since 9.1
     *
     * @param array $options with this keys:
     *  - begin: mandatory, planning start.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end: mandatory, planning end.
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - display_done_events: default true, show also events tagged as done
     *  - force_all_events: even if the range is big, don't reduce the returned set
     * @return array $events : array with events in fullcalendar.io format
     */
    public static function constructEventsArray($options = [])
    {
        global $CFG_GLPI;

        $param['start']               = '';
        $param['end']                 = '';
        $param['view_name']           = '';
        $param['display_done_events'] = true;
        $param['force_all_events']    = false;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $param[$key] = $val;
            }
        }

        $timezone = new DateTimeZone(date_default_timezone_get());
        $time_begin = strtotime($param['start']) - $timezone->getOffset(new DateTime($param['start']));
        $time_end   = strtotime($param['end']) - $timezone->getOffset(new DateTime($param['end']));

       // if the dates range is greater than a certain amount, and we're not on a list view
       // we certainly are on this view (as our biggest view apart list is month one).
       // we must avoid at all cost to calculate rrules events on a big range
        if (
            !$param['force_all_events']
            && $param['view_name'] != "listFull"
            && ($time_end - $time_begin) > (2 * MONTH_TIMESTAMP)
        ) {
            $param['view_name'] = "listFull";
            return [];
        }

        $param['begin'] = date("Y-m-d H:i:s", $time_begin);
        $param['end']   = date("Y-m-d H:i:s", $time_end);

        $raw_events = [];
        $not_planned = [];
        foreach ($CFG_GLPI['planning_types'] as $planning_type) {
            if (!$planning_type::canView()) {
                continue;
            }
            if ($_SESSION['glpi_plannings']['filters'][$planning_type]['display']) {
                $event_type_color = $_SESSION['glpi_plannings']['filters'][$planning_type]['color'];
                foreach ($_SESSION['glpi_plannings']['plannings'] as $actor => $actor_params) {
                    if ($actor_params['type'] == 'external') {
                        continue; // Ignore external calendars
                    }
                    $actor_params['event_type_color'] = $event_type_color;
                    $actor_params['planning_type'] = $planning_type;
                    self::constructEventsArraySingleLine(
                        $actor,
                        array_merge($param, $actor_params),
                        $raw_events,
                        $not_planned
                    );
                }
            }
        }

       //handle not planned events
        $raw_events = array_merge($raw_events, $not_planned);

       // get external calendars events (ical)
       // and on list view, only get future events
        $begin_ical = $param['begin'];
        if ($param['view_name'] == "listFull") {
            $begin_ical = date('Y-m-d 00:00:00');
        }
        $raw_events = array_merge(
            $raw_events,
            self::getExternalCalendarRawEvents($begin_ical, $param['end'])
        );

       // construct events (in fullcalendar format)
        $events = [];
        foreach ($raw_events as $event) {
            if (
                $_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']
                && (!isset($event['background']) || !$event['background'])
            ) {
                continue;
            }

            $users_id = (isset($event['users_id_tech']) && !empty($event['users_id_tech']) ?
                        $event['users_id_tech'] :
                        $event['users_id']);
            $content = Planning::displayPlanningItem($event, $users_id, 'in', false) ?: ($event['content'] ?? "");
            $tooltip = Planning::displayPlanningItem($event, $users_id, 'in', true) ?: ($event['tooltip'] ?? "");

           // dates should be set with the user timezone
            $begin = $event['begin'];
            $end   = $event['end'];

           // retreive all day events
            if (
                strpos($event['begin'], "00:00:00") != false
                && (strtotime($event['end']) - strtotime($event['begin'])) % DAY_TIMESTAMP == 0
            ) {
                 $begin = date('Y-m-d', strtotime($event['begin']));
                 $end = date('Y-m-d', strtotime($event['end']));
            }

           // get duration in milliseconds
            $ms_duration = (strtotime($end) - strtotime($begin)) * 1000;

            $index_color = array_search("user_$users_id", array_keys($_SESSION['glpi_plannings']));
            $new_event = [
                'title'       => $event['name'],
                'content'     => $content,
                'tooltip'     => $tooltip,
                'start'       => $begin,
                'end'         => $end,
                'duration'    => $ms_duration,
                '_duration'   => $ms_duration, // sometimes duration is removed from event object in fullcalendar
                '_editable'   => $event['editable'], // same, avoid loss of editable key in fullcalendar
                'rendering'   => isset($event['background'])
                             && $event['background']
                             && !$_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']
                              ? 'background'
                              : '',
                'color'       => (empty($event['color']) ?
                              Planning::$palette_bg[$index_color] :
                              $event['color']),
                'borderColor' => (empty($event['event_type_color']) ?
                              self::getPaletteColor('ev', $event['itemtype']) :
                              $event['event_type_color']),
                'textColor'   => Planning::$palette_fg[$index_color],
                'typeColor'   => (empty($event['event_type_color']) ?
                              self::getPaletteColor('ev', $event['itemtype']) :
                              $event['event_type_color']),
                'url'         => $event['url'] ?? "",
                'ajaxurl'     => $event['ajaxurl'] ?? "",
                'itemtype'    => $event['itemtype'] ?? "",
                'parentitemtype' => $event['parentitemtype'] ?? "",
                'items_id'    => $event['id'] ?? "",
                'resourceId'  => $event['resourceId'] ?? "",
                'priority'    => $event['priority'] ?? "",
                'state'       => $event['state'] ?? "",
            ];

           // if we can't update the event, pass the editable key
            if (!$event['editable']) {
                $new_event['editable'] = false;
            }

           // override color if view is ressource and category color exists
           // maybe we need a better way for displaying categories color
            if (
                $param['view_name'] == "resourceWeek"
                && !empty($event['event_cat_color'])
            ) {
                $new_event['color'] = $event['event_cat_color'];
            }

           // manage reccurent events
            if (isset($event['rrule']) && count($event['rrule'])) {
                $rrule = $event['rrule'];

               // the fullcalencard plugin waits for integer types for number (not strings)
                if (isset($rrule['interval'])) {
                    $rrule['interval'] = (int) $rrule['interval'];
                }
                if (isset($rrule['count'])) {
                    $rrule['count'] = (int) $rrule['count'];
                }

               // clean empty values in rrule
                foreach ($rrule as $key => $value) {
                    if (is_null($value) || $value == '') {
                        unset($rrule[$key]);
                    }
                }

                $rset = PlanningExternalEvent::getRsetFromRRuleField($rrule, $new_event['start']);

               // append icon to distinguish reccurent event in views
               // use UTC datetime to avoid some issues with rlan/phprrule
                $dtstart_datetime  = new \DateTime($new_event['start']);
                unset($rrule['exceptions']); // remove exceptions key (as libraries throw exception for unknow keys)
                $hr_rrule_o = new RRule(
                    array_merge(
                        $rrule,
                        [
                            'dtstart' => $dtstart_datetime->format('Ymd\THis\Z')
                        ]
                    )
                );
                $new_event = array_merge($new_event, [
                    'icon'     => 'fas fa-history',
                    'icon_alt' => $hr_rrule_o->humanReadable(),
                ]);

               // for fullcalendar, we need to pass start in the rrule key
                unset($new_event['start'], $new_event['end']);

               // For list view, only display only the next occurence
               // to avoid issues performances (range in list view can be 10 years long)
                if ($param['view_name'] == "listFull") {
                     $next_date = $rset->getNthOccurrenceAfter(new DateTime(), 1);
                    if ($next_date) {
                        $new_event = array_merge($new_event, [
                            'start'    => $next_date->format('c'),
                            'end'      => $next_date->add(new DateInterval("PT" . ($ms_duration / 1000) . "S"))
                                            ->format('c'),
                        ]);
                    }
                } else {
                    $rrule_string = "";
                    foreach ($rset->getRRules() as $occurence) {
                        $rrule_string .= $occurence->rfcString(false) . "\n";
                    }
                    $ex_dates = [];
                    foreach ($rset->getExDates() as $occurence) {
                       // we forge the ex date with only the date part of the exception
                       // and the hour of the dtstart.
                       // This to presents only date selection to the user
                        $ex_dates[] = "EXDATE:" . $occurence->format('Ymd\THis');
                    }

                    if (count($ex_dates)) {
                        $rrule_string .= implode("\n", $ex_dates) . "\n";
                    }

                    $new_event = array_merge($new_event, [
                        'is_recurrent' => true,
                        'rrule'        => $rrule_string,
                        'duration'     => $ms_duration
                    ]);
                }
            }

            $events[] = $new_event;
        }

        return $events;
    }


    /**
     * construct a single line for self::constructEventsArray()
     * Recursively called to construct $raw_events param.
     *
     * @since 9.1
     *
     * @param string $actor: a type and id concaneted separated by '_' char, ex 'user_41'
     * @param array  $params: must contains this keys :
     *  - display: boolean for pass or not the consstruction of this line (a group of users can be displayed but its users not).
     *  - type: event type, can be event_filter, user, group or group_users
     *  - who: integer for identify user
     *  - whogroup: integer for identify group
     *  - color: string with #rgb color for event's foreground color.
     *  - event_type_color : string with #rgb color for event's foreground color.
     * @param array  $raw_events: (passed by reference) the events array in construction
     * @param array  $not_planned (passed by references) not planned events array in construction
     * @return void
     */
    public static function constructEventsArraySingleLine($actor, $params = [], &$raw_events = [], &$not_planned = [])
    {

        if ($params['display']) {
            $actor_array = explode("_", $actor);
            if ($params['type'] == "group_users") {
                $subparams = $params;
                unset($subparams['users']);
                $subparams['from_group_users'] = true;
                foreach ($params['users'] as $user => $userdata) {
                    $subparams = array_merge($subparams, $userdata);
                    self::constructEventsArraySingleLine($user, $subparams, $raw_events, $not_planned);
                }
            } else {
                $params['who']       = $actor_array[1];
                $params['whogroup']  = 0;
                if (
                    $params['type'] == "group"
                    && in_array($params['planning_type'], self::$directgroup_itemtype)
                ) {
                    $params['who']       = 0;
                    $params['whogroup']  = $actor_array[1];
                }

                $current_events = $params['planning_type']::populatePlanning($params);
                if (count($current_events) > 0) {
                    $raw_events = array_merge($raw_events, $current_events);
                }
                if (
                    $_SESSION['glpi_plannings']['filters']['NotPlanned']['display']
                    && method_exists($params['planning_type'], 'populateNotPlanned')
                ) {
                    $not_planned = array_merge($not_planned, $params['planning_type']::populateNotPlanned($params));
                }
            }
        }

        if (isset($params['from_group_users']) && $params['from_group_users']) {
            $actor = "gu_" . $actor;
        }

       // fill type of planning
        $raw_events = array_map(function ($arr) use ($actor) {
            return $arr + ['resourceId' => $actor];
        }, $raw_events);

        if ($_SESSION['glpi_plannings']['filters']['NotPlanned']['display']) {
            $not_planned = array_map(function ($arr) use ($actor) {
                return $arr + [
                    'not_planned' => true,
                    'resourceId' => $actor,
                    'event_type_color' => $_SESSION['glpi_plannings']['filters']['NotPlanned']['color']
                ];
            }, $not_planned);
        }
    }

    /**
     * Return events fetched from user external calendars.
     *
     * @return array
     */
    private static function getExternalCalendarRawEvents(string $limit_begin, string $limit_end): array
    {
        ErrorHandler::getInstance()->suspendOutput(); // Suspend error output to prevent warnings to corrupr JSON output

        $raw_events = [];

        foreach ($_SESSION['glpi_plannings']['plannings'] as $planning_id => $planning_params) {
            if ('external' !== $planning_params['type'] || !$planning_params['display']) {
                continue; // Ignore non external and inactive calendars
            }
            $calendar_data = Toolbox::getURLContent($planning_params['url']);
            if (empty($calendar_data)) {
                continue;
            }
            try {
                $vcalendar = Reader::read($calendar_data);
            } catch (\Sabre\VObject\ParseException $exception) {
                trigger_error(
                    sprintf('Unable to parse calendar data from URL "%s"', $planning_params['url']),
                    E_USER_WARNING
                );
                continue;
            }
            if (!$vcalendar instanceof VCalendar) {
                trigger_error(
                    sprintf('No VCalendar object found at URL "%s"', $planning_params['url']),
                    E_USER_WARNING
                );
                continue;
            }
            foreach ($vcalendar->getComponents() as $vcomp) {
                if (!($vcomp instanceof VEvent || $vcomp instanceof VTodo)) {
                    continue;
                }

                $end_date_prop = $vcomp instanceof VTodo ? 'DUE' : 'DTEND';
                if (
                    !$vcomp->DTSTART instanceof \Sabre\VObject\Property\ICalendar\DateTime
                    || !$vcomp->$end_date_prop instanceof \Sabre\VObject\Property\ICalendar\DateTime
                ) {
                    continue;
                }
                $user_tz  = new \DateTimeZone(date_default_timezone_get());
                $begin_dt = $vcomp->DTSTART->getDateTime();
                $begin_dt = $begin_dt->setTimeZone($user_tz);
                $end_dt   = $vcomp->$end_date_prop->getDateTime();
                $end_dt   = $end_dt->setTimeZone($user_tz);

                if (
                    !($vcomp->RRULE instanceof Recur)
                    && ($limit_end < $begin_dt->format('Y-m-d H:i:s') || $limit_begin > $end_dt->format('Y-m-d H:i:s'))
                ) {
                    continue; // Ignore events not inside dates range
                }

                $title = $vcomp->SUMMARY instanceof FlatText ? $vcomp->SUMMARY->getValue() : '';
                $description = $vcomp->DESCRIPTION instanceof FlatText ? $vcomp->DESCRIPTION->getValue() : '';

                $raw_events[] = [
                    'users_id'         => Session::getLoginUserID(),
                    'name'             => $title,
                    'tooltip'          => trim($title . "\n" . $description),
                    'content'          => $description,
                    'begin'            => $begin_dt->format('Y-m-d H:i:s'),
                    'end'              => $end_dt->format('Y-m-d H:i:s'),
                    'event_type_color' => $planning_params['color'],
                    'color'            => $planning_params['color'],
                    'rrule'            => $vcomp->RRULE instanceof Recur
                  ? current($vcomp->RRULE->getJsonValue())
                  : null,
                    'editable'         => false,
                    'resourceId'       => $planning_id,
                ];
            }
        }

        ErrorHandler::getInstance()->unsuspendOutput(); // Restore error output state

        return $raw_events;
    }


    /**
     * Change dates of a selected event.
     * Called from a drag&drop in planning
     *
     * @since 9.1
     *
     * @param array $options: must contains this keys :
     *  - items_id : integer to identify items
     *  - itemtype : string to identify items
     *  - begin : planning start .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end : planning end .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     * @return bool
     */
    public static function updateEventTimes($params = [])
    {
        if ($item = getItemForItemtype($params['itemtype'])) {
            $params = self::cleanDates($params);

            if (
                $item->getFromDB($params['items_id'])
                && empty($item->fields['is_deleted'])
            ) {
                // item exists and is not in bin

                $abort = false;

                // we should not edit events from closed parent
                if (!empty($item->fields['tickets_id'])) {
                  // todo: to same checks for changes, problems, projects and maybe reminders and others depending on incoming itemtypes
                    $ticket = new Ticket();

                    if (
                        !$ticket->getFromDB($item->fields['tickets_id'])
                        || $ticket->fields['is_deleted']
                        || $ticket->fields['status'] == CommonITILObject::CLOSED
                    ) {
                         $abort = true;
                    }
                }

                // if event has rrule property, check if we need to create a clone instance
                if (
                    isset($item->fields['rrule'])
                    && strlen($item->fields['rrule'])
                ) {
                    if (
                        isset($params['move_instance'])
                        && filter_var($params['move_instance'], FILTER_VALIDATE_BOOLEAN)
                    ) {
                         $item = $item->createInstanceClone(
                             $item->fields['id'],
                             $params['old_start']
                         );
                            $params['items_id'] = $item->fields['id'];
                    }
                }

                if (!$abort) {
                     $update = [
                         'id'   => $params['items_id'],
                         'plan' => [
                             'begin' => $params['start'],
                             'end'   => $params['end']
                         ]
                     ];

                     if (isset($item->fields['users_id_tech'])) {
                         $update['users_id_tech'] = $item->fields['users_id_tech'];
                     }

                     // manage moving event between resource (actors)
                     if (
                         isset($params['new_actor_itemtype'])
                         && isset($params['new_actor_items_id'])
                         && !empty($params['new_actor_itemtype'])
                         && !empty($params['new_actor_items_id'])
                     ) {
                         $new_actor_itemtype = strtolower($params['new_actor_itemtype']);

                         // reminders don't have group assignement for planning
                         if (
                             !($new_actor_itemtype === 'group'
                             && $item instanceof Reminder)
                         ) {
                             switch ($new_actor_itemtype) {
                                 case "group":
                                        $update['groups_id_tech'] = $params['new_actor_items_id'];
                                     if (strtolower($params['old_actor_itemtype']) === "user") {
                                         $update['users_id_tech']  = 0;
                                     }
                                     break;

                                 case "user":
                                     if (isset($item->fields['users_id_tech'])) {
                                         $update['users_id_tech']  = $params['new_actor_items_id'];
                                         if (strtolower($params['old_actor_itemtype']) === "group") {
                                             $update['groups_id_tech']  = 0;
                                         }
                                     } else {
                                         $update['users_id'] = $params['new_actor_items_id'];
                                     }
                                     break;
                             }
                         }

                       // special case for project tasks
                       // which have a link tables for their relation with groups/users
                         if ($item instanceof ProjectTask) {
                          // get actor for finding relation with item
                             $actor = new $params['old_actor_itemtype']();
                             $actor->getFromDB((int) $params['old_actor_items_id']);

                          // get current relation
                             $team_old = new ProjectTaskTeam();
                             $team_old->getFromDBForItems($item, $actor);

                          // if new relation already exists, delete old relation
                             $actor_new = new $params['new_actor_itemtype']();
                             $actor_new->getFromDB((int) $params['new_actor_items_id']);
                             $team_new  = new ProjectTaskTeam();
                             if ($team_new->getFromDBForItems($item, $actor_new)) {
                                 $team_old->delete([
                                     'id' => $team_old->fields['id']
                                 ]);
                             } else {
                      // else update relation
                                 $team_old->update([
                                     'id'       => $team_old->fields['id'],
                                     'itemtype' => $params['new_actor_itemtype'],
                                     'items_id' => $params['new_actor_items_id'],
                                 ]);
                             }
                         }
                     }

                     if (is_subclass_of($item, "CommonITILTask")) {
                         $parentitemtype = $item->getItilObjectItemType();
                         if (!$update["_job"] = getItemForItemtype($parentitemtype)) {
                             return;
                         }

                         $fkfield = $update["_job"]->getForeignKeyField();
                         $update[$fkfield] = $item->fields[$fkfield];
                     }

                     return $item->update($update);
                }
            }
        }

        return false;
    }

    /**
     * Clean timezone information from dates fields,
     * as fullcalendar doesn't support easily timezones, let's consider it sends raw dates
     * (remove timezone suffix), we will manage timezone directy on database
     * see https://fullcalendar.io/docs/timeZone
     *
     * @since 9.5
     *
     * @param array $params parameters send by fullcalendar
     *
     * @return array cleaned $params
     */
    public static function cleanDates(array $params = []): array
    {
        $dates_fields = [
            'start', 'begin', 'end'
        ];

        foreach ($params as $key => &$value) {
            if (in_array($key, $dates_fields)) {
                $value  = date("Y-m-d H:i:s", strtotime(trim($value, 'Z')));
            }
        }

        return $params;
    }



    /**
     * Display a Planning Item
     *
     * @param $val       Array of the item to display
     * @param $who             ID of the user (0 if all)
     * @param $type            position of the item in the time block (in, through, begin or end)
     *                         (default '')
     * @param $complete        complete display (more details) (default 0)
     *
     * @return string
     **/
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        $html = "";

       // bg event shouldn't have content displayed
        if (!$complete && $_SESSION['glpi_plannings']['filters']['OnlyBgEvents']['display']) {
            return "";
        }

       // Plugins case
        if (
            isset($val['itemtype'])
            && !empty($val['itemtype'])
            && $val['itemtype'] != 'NotPlanned'
            && method_exists($val['itemtype'], "displayPlanningItem")
        ) {
            $html .= $val['itemtype']::displayPlanningItem($val, $who, $type, $complete);
        }

        return $html;
    }

    /**
     * Show the planning for the central page of a user
     *
     * @param $who ID of the user
     *
     * @return void
     **/
    public static function showCentral($who)
    {
        global $CFG_GLPI;

        if (
            !Session::haveRight(self::$rightname, self::READMY)
            || ($who <= 0)
        ) {
            return false;
        }

        echo "<div class='table-responsive card-table'>";
        echo "<table class='table'>";
        echo "<thead>";
        echo "<tr class='noHover'><th>";
        echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/planning.php'>" . __('Your planning') . "</a>";
        echo "</th></tr>";
        echo "</thead>";

        echo "<tr class='noHover'>";
        echo "<td class='planning_on_central'>";
        self::showPlanning(false);
        echo "</td></tr>";
        echo "</table>";
        echo "</div>";
    }



   //*******************************************************************************************************************************
   // *********************************** Implementation ICAL ***************************************************************
   //*******************************************************************************************************************************

    /**
     *  Generate ical file content
     *
     * @param $who             user ID
     * @param $whogroup        group ID
     * @param $limititemtype   itemtype only display this itemtype (default '')
     *
     * @return void Outputs ical contents
     **/
    public static function generateIcal($who, $whogroup, $limititemtype = '')
    {
        global $CFG_GLPI;

        if (
            ($who === 0)
            && ($whogroup === 0)
        ) {
            return;
        }

        if (!empty($CFG_GLPI["version"])) {
            $unique_id = "GLPI-Planning-" . trim($CFG_GLPI["version"]);
        } else {
            $unique_id = "GLPI-Planning-UnknownVersion";
        }

       // create vcalendar
        $vcalendar = new VCalendar();

       // $xprops = array( "X-LIC-LOCATION" => $tz );
       // iCalUtilityFunctions::createTimezone( $v, $tz, $xprops );

        $interv = [];
        $begin  = time() - MONTH_TIMESTAMP * 12;
        $end    = time() + MONTH_TIMESTAMP * 12;
        $begin  = date("Y-m-d H:i:s", $begin);
        $end    = date("Y-m-d H:i:s", $end);
        $params = [
            'genical'   => true,
            'who'       => $who,
            'whogroup'  => $whogroup,
            'begin'     => $begin,
            'end'       => $end
        ];

        if (empty($limititemtype)) {
            foreach ($CFG_GLPI['planning_types'] as $itemtype) {
                $interv = array_merge($interv, $itemtype::populatePlanning($params));
            }
        } else {
            $interv = $limititemtype::populatePlanning($params);
        }

        if (count($interv) > 0) {
            foreach ($interv as $key => $val) {
                if (isset($val['itemtype'])) {
                    if (isset($val[getForeignKeyFieldForItemType($val['itemtype'])])) {
                        $uid = $val['itemtype'] . "#" . $val[getForeignKeyFieldForItemType($val['itemtype'])];
                    } else {
                        $uid = "Other#" . $key;
                    }
                } else {
                    $uid = "Other#" . $key;
                }

                $vevent['UID']     = $uid;

                $dateBegin = new DateTime($val["begin"]);
                $dateBegin->setTimeZone(new DateTimeZone('UTC'));

                $dateEnd = new DateTime($val["end"]);
                $dateEnd->setTimeZone(new DateTimeZone('UTC'));

                $vevent['DTSTART'] = $dateBegin;
                $vevent['DTEND']   = $dateEnd;

                if (isset($val["tickets_id"])) {
                    $summary = sprintf(__('Ticket #%1$s %2$s'), $val["tickets_id"], $val["name"]);
                } else if (isset($val["name"])) {
                    $summary = $val["name"];
                }
                $vevent['SUMMARY'] = $summary;

                if (isset($val["content"])) {
                    $description = $val["content"];
                } else if (isset($val["text"])) {
                    $description = $val["text"];
                } else if (isset($val["name"])) {
                    $description = $val["name"];
                }
                $vevent['DESCRIPTION'] = RichText::getTextFromHtml($description);

                if (isset($val["url"])) {
                    $vevent['URL'] = $val["url"];
                }
                $vcalendar->add('VEVENT', $vevent);
            }
        }

        $output   = $vcalendar->serialize();
        $filename = date('YmdHis') . '.ics';

        @header("Content-Disposition: attachment; filename=\"$filename\"");
       //@header("Content-Length: ".Toolbox::strlen($output));
        @header("Connection: close");
        @header("content-type: text/calendar; charset=utf-8");

        echo $output;
    }

    /**
     * @since 0.85
     **/
    public function getRights($interface = 'central')
    {

        $values[self::READMY]    = __('See personnal planning');
        $values[self::READGROUP] = __('See schedule of people in my groups');
        $values[self::READALL]   = __('See all plannings');

        return $values;
    }

    /**
     * Save the last view used in fullcalendar
     *
     * @since 9.5
     *
     * @param string $view_name
     * @return void
     */
    public static function viewChanged($view_name = "ListView")
    {
        $_SESSION['glpi_plannings']['lastview'] = $view_name;
    }

    /**
     * Returns actor type from 'planning' key (key comes from user 'plannings' field).
     *
     * @param string $key
     *
     * @return string|null
     */
    public static function getActorTypeFromPlanningKey($key)
    {
        if (preg_match('/group_\d+_users/', $key)) {
            return Group_User::getType();
        }
        $itemtype = ucfirst(preg_replace('/^([a-z]+)_\d+$/', '$1', $key));
        return class_exists($itemtype) ? $itemtype : null;
    }

    /**
     * Returns actor id from 'planning' key (key comes from user 'plannings' field).
     *
     * @param string $key
     *
     * @return integer|null
     */
    public static function getActorIdFromPlanningKey($key)
    {
        $items_id = preg_replace('/^[a-z]+_(\d+)(?:_[a-z]+)?$/', '$1', $key);
        return is_numeric($items_id) ? (int)$items_id : null;
    }

    /**
     * Returns planning key for given actor (key is used in user 'plannings' field).
     *
     * @param string  $itemtype
     * @param integer $items_id
     *
     * @return string
     */
    public static function getPlanningKeyForActor($itemtype, $items_id)
    {
        if ('Group_User' === $itemtype) {
            return 'group_' . $items_id . '_users';
        }

        return strtolower($itemtype) . '_' . $items_id;
    }

    /**
     * Get CalDAV base calendar URL for given actor.
     *
     * @param CommonDBTM $item
     *
     * @return string|null
     */
    private static function getCaldavBaseCalendarUrl(\CommonDBTM $item)
    {

        $calendar_uri = null;

        switch (get_class($item)) {
            case \Group::class:
                $calendar_uri = \Glpi\CalDAV\Backend\Calendar::PREFIX_GROUPS
                 . '/' . $item->fields['id']
                 . '/' . \Glpi\CalDAV\Backend\Calendar::BASE_CALENDAR_URI;
                break;
            case \User::class:
                $calendar_uri = \Glpi\CalDAV\Backend\Calendar::PREFIX_USERS
                . '/' . $item->fields['name']
                . '/' . \Glpi\CalDAV\Backend\Calendar::BASE_CALENDAR_URI;
                break;
        }

        return $calendar_uri;
    }

    public static function getIcon()
    {
        return "ti ti-calendar-time";
    }
}
