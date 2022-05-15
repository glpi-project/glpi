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

/**
 * Reservation Class
 **/
class Reservation extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype          = 'ReservationItem';
    public static $items_id          = 'reservationitems_id';

    public static $rightname                = 'reservation';
    public static $checkParentRights = self::HAVE_VIEW_RIGHT_ON_ITEM;


    /**
     * @param $nb  integer  for singular or plural
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Reservation', 'Reservations', $nb);
    }


    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            !$withtemplate
            && Session::haveRight("reservation", READ)
        ) {
            return self::getTypeName(Session::getPluralNumber());
        }
        return '';
    }


    /**
     * @param $item         CommonGLPI object
     * @param $tabnum       (default1)
     * @param $withtemplate (default0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'User') {
            self::showForUser($_GET["id"]);
        } else {
            self::showForItem($item);
        }
        return true;
    }


    public function pre_deleteItem()
    {
        global $CFG_GLPI;

        if (
            isset($this->fields["users_id"])
            && (($this->fields["users_id"] === Session::getLoginUserID())
              || Session::haveRight("reservation", DELETE))
        ) {
           // Processing Email
            if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
               // Only notify for non-completed reservations
                if (strtotime($this->fields['end']) > time()) {
                    NotificationEvent::raiseEvent("delete", $this);
                }
            }
        }
        return true;
    }


    /**
     * @see CommonDBChild::prepareInputForUpdate()
     **/
    public function prepareInputForUpdate($input)
    {

        $item = 0;
        if (isset($input['_item'])) {
            $item = $_POST['_item'];
        }

       // Save fields
        $oldfields             = $this->fields;
       // Needed for test already planned
        if (isset($input["begin"])) {
            $this->fields["begin"] = $input["begin"];
        }
        if (isset($input["end"])) {
            $this->fields["end"] = $input["end"];
        }

        if (!$this->test_valid_date()) {
            $this->displayError("date", $item);
            return false;
        }

        if ($this->is_reserved()) {
            $this->displayError("is_res", $item);
            return false;
        }

       // Restore fields
        $this->fields = $oldfields;

        return parent::prepareInputForUpdate($input);
    }


    /**
     * @see CommonDBTM::post_updateItem()
     **/
    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        if (
            count($this->updates)
            && $CFG_GLPI["use_notifications"]
            && !isset($this->input['_disablenotif'])
        ) {
            NotificationEvent::raiseEvent("update", $this);
           //$mail = new MailingResa($this,"update");
           //$mail->send();
        }

        parent::post_updateItem($history);
    }


    /**
     * @see CommonDBChild::prepareInputForAdd()
     **/
    public function prepareInputForAdd($input)
    {

       // Error on previous added reservation on several add
        if (isset($input['_ok']) && !$input['_ok']) {
            return false;
        }

       // set new date.
        $this->fields["reservationitems_id"] = $input["reservationitems_id"];
        $this->fields["begin"]               = $input["begin"];
        $this->fields["end"]                 = $input["end"];

        if (!$this->test_valid_date()) {
            $this->displayError("date", $input["reservationitems_id"]);
            return false;
        }

        if ($this->is_reserved()) {
            $this->displayError("is_res", $input["reservationitems_id"]);
            return false;
        }

        return parent::prepareInputForAdd($input);
    }


    public function post_addItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            NotificationEvent::raiseEvent("new", $this);
        }

        parent::post_addItem();
    }


   // SPECIFIC FUNCTIONS

    /**
     * @param $reservationitems_id
     **/
    public function getUniqueGroupFor($reservationitems_id)
    {
        global $DB;

        do {
            $rand = mt_rand(1, mt_getrandmax());

            $result = $DB->request([
                'COUNT'  => 'cpt',
                'FROM'   => 'glpi_reservations',
                'WHERE'  => [
                    'reservationitems_id'   => $reservationitems_id,
                    'group'                 => $rand
                ]
            ])->current();
            $count = (int)$result['cpt'];
        } while ($count > 0);

        return $rand;
    }


    /**
     * Is the item already reserved ?
     *
     *@return boolean
     **/
    public function is_reserved()
    {
        global $DB;

        if (
            !isset($this->fields["reservationitems_id"])
            || empty($this->fields["reservationitems_id"])
        ) {
            return true;
        }

       // When modify a reservation do not itself take into account
        $where = [];
        if (isset($this->fields["id"])) {
            $where['id'] = ['<>', $this->fields['id']];
        }

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => $this->getTable(),
            'WHERE'  => $where + [
                'reservationitems_id'   => $this->fields['reservationitems_id'],
                'end'                   => ['>', $this->fields['begin']],
                'begin'                 => ['<', $this->fields['end']]
            ]
        ])->current();
        return $result['cpt'] > 0;
    }


    /**
     * Current dates are valid ? begin before end
     *
     *@return boolean
     **/
    public function test_valid_date()
    {

        return (!empty($this->fields["begin"])
              && !empty($this->fields["end"])
              && (strtotime($this->fields["begin"]) < strtotime($this->fields["end"])));
    }


    /**
     * display error message
     *
     * @param $type   error type : date / is_res / other
     * @param $ID     ID of the item
     *
     * @return void
     **/
    public function displayError($type, $ID)
    {

        echo "<br><div class='center'>";
        switch ($type) {
            case "date":
                echo __('Error in entering dates. The starting date is later than the ending date');
                break;

            case "is_res":
                echo __('The required item is already reserved for this timeframe');
                break;

            default:
                echo __("Unknown error");
        }

        echo "<br><a href='reservation.php?reservationitems_id=$ID'>" . __('Back to planning') . "</a>";
        echo "</div>";
    }


    /**
     * @since 0.84
     **/
    public static function canCreate()
    {
        return (Session::haveRight(self::$rightname, ReservationItem::RESERVEANITEM));
    }


    /**
     * @since 0.84
     **/
    public static function canUpdate()
    {
        return (Session::haveRight(self::$rightname, ReservationItem::RESERVEANITEM));
    }


    /**
     * @since 0.84
     **/
    public static function canDelete()
    {
        return (Session::haveRight(self::$rightname, ReservationItem::RESERVEANITEM));
    }


    /**
     * Overload canChildItem to make specific checks
     * @since 0.84
     **/
    public function canChildItem($methodItem, $methodNotItem)
    {

       // Original user always have right
        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            return true;
        }

        if (!parent::canChildItem($methodItem, $methodNotItem)) {
            return false;
        }

        $ri = $this->getItem();
        if ($ri === false) {
            return false;
        }

        $item = $ri->getItem();
        if ($item === false) {
            return false;
        }

        return Session::haveAccessToEntity($item->getEntityID());
    }


    public function post_purgeItem()
    {
        global $DB;

        if (isset($this->input['_delete_group']) && $this->input['_delete_group']) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_reservations',
                'WHERE'  => [
                    'reservationitems_id'   => $this->fields['reservationitems_id'],
                    'group'                 => $this->fields['group']
                ]
            ]);
            $rr = clone $this;
            foreach ($iterator as $data) {
                 $rr->delete(['id' => $data['id']]);
            }
        }
    }


    /**
     * Show reservation calendar
     *
     * @param $ID   ID of the reservation item (if 0 display all) (default '')
     **/
    public static function showCalendar(int $ID = 0)
    {
        global $CFG_GLPI;

        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        $rand = mt_rand();

       // scheduler feature key
       // schedular part of fullcalendar is distributed with opensource licence (GLPv3)
       // but this licence is incompatible with GLPI (GPLv2)
       // see https://fullcalendar.io/license
        $scheduler_key = Plugin::doHookFunction('planning_scheduler_key');

        $is_all = $ID === 0 ? "true" : "false";
        if ($ID > 0) {
            $m = new ReservationItem();
            $m->getFromDB($ID);

            if ((!isset($m->fields['is_active'])) || !$m->fields['is_active']) {
                echo "<div class='center'>";
                echo __('Device temporarily unavailable');
                Html::displayBackLink();
                echo "</div>";
                return false;
            }
            $type = $m->fields["itemtype"];
            $name = NOT_AVAILABLE;
            if ($item = getItemForItemtype($m->fields["itemtype"])) {
                $type = $item->getTypeName();

                if ($item->getFromDB($m->fields["items_id"])) {
                    $name = $item->getName();
                }
                $name = sprintf(__('%1$s - %2$s'), $type, $name);
            }

            $all = "<a class='btn btn-primary ms-2 view-all' href='reservation.php?reservationitems_id=0'>" .
               __('View all items') .
               "&nbsp;<i class='fas fa-eye'></i>" .
            "</a>";
        } else {
            $type = "";
            $name = __('All reservable devices');
            $all  = "";
        }
        echo "<div class='card'>";
        echo "<div class='text-center card-header'>";
        echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/reservation.png' alt='' class='reservation-icon'>";
        echo "<h2 class='item-name'>" . $name . "</h2>";
        echo "$all";
        echo "</div>"; // .center
        echo "<div id='reservations_planning_$rand' class='card-body reservations-planning'></div>";
        echo "</div>"; // .reservation_panel

        $js = <<<JAVASCRIPT
      $(function() {
         var reservation = new Reservations();
         reservation.init({
            id: $ID,
            is_all: $is_all,
            rand: $rand,
            license_key: '$scheduler_key',
         });
         reservation.displayPlanning();
      });
JAVASCRIPT;
        echo Html::scriptBlock($js);
    }


    public static function getEvents(array $params): array
    {
        global $DB;

        $defaults = [
            'start'               => '',
            'end'                 => '',
            'reservationitems_id' => 0,
        ];
        $params = array_merge($defaults, $params);

        $start = date("Y-m-d H:i:s", strtotime($params['start']));
        $end   = date("Y-m-d H:i:s", strtotime($params['end']));

        $res_table   = static::getTable();
        $res_i_table = ReservationItem::getTable();

        $canedit_admin = Session::getCurrentInterface() == "central"
                       && Session::haveRight("reservation", READ);
        $can_reserve   = Session::haveRight("reservation", ReservationItem::RESERVEANITEM);

        $user = new User();

        $where = [];
        if ($params['reservationitems_id'] > 0) {
            $where = [
                "$res_table.reservationitems_id" => $params['reservationitems_id'],
            ];
        }

        $iterator = $DB->request([
            'SELECT'     => [
                "$res_table.id",
                "$res_table.begin",
                "$res_table.end",
                "$res_table.comment",
                "$res_table.users_id",
                "$res_i_table.items_id",
                "$res_i_table.itemtype",
            ],
            'FROM'       => $res_table,
            'INNER JOIN' => [
                $res_i_table => [
                    'ON' => [
                        $res_i_table => 'id',
                        $res_table   => 'reservationitems_id'
                    ]
                ]
            ],
            'WHERE' => [
                'end'   => ['>', $start],
                'begin' => ['<', $end],
            ] + $where
        ]);

        $events = [];
        if (!count($iterator)) {
            return [];
        }
        foreach ($iterator as $data) {
            $item = new $data['itemtype']();
            if (!$item->getFromDB($data['items_id'])) {
                continue;
            }

            $my_item = $data['users_id'] === Session::getLoginUserID();

            if ($canedit_admin || $my_item) {
                $user->getFromDB($data['users_id']);
                $username = $user->getFriendlyName();
            }

            $name = $item->getName([
                'complete' => true,
            ]);

            $editable = $canedit_admin || ($can_reserve && $my_item);

            $events[] = [
                'id'          => $data['id'],
                'resourceId'  => $data['itemtype'] . "-" . $data['items_id'],
                'start'       => $data['begin'],
                'end'         => $data['end'],
                'comment'     => $data['comment'] .
                             $canedit_admin || $my_item
                              ? "\n" . sprintf(__("Reserved by %s"), $username)
                              : "",
                'title'       => $params['reservationitems_id'] ? "" : $name,
                'icon'        => $item->getIcon(),
                'description' => $item->getTypeName(),
                'itemtype'    => $data['itemtype'],
                'items_id'    => $data['items_id'],
                'color'       => Toolbox::getColorForString($name),
                'ajaxurl'     => Reservation::getFormURLWithID($data['id']),
                'editable'    => $editable, // "editable" is used by fullcalendar, but is not accessible
                '_editable'   => $editable, // "_editable" will be used by custom event handlers
            ];
        }

        return $events;
    }


    public static function getResources()
    {
        global $DB;

        $res_i_table = ReservationItem::getTable();

        $iterator = $DB->request([
            'SELECT' => [
                "$res_i_table.items_id",
                "$res_i_table.itemtype",
            ],
            'FROM'   => $res_i_table,
            'WHERE'  => [
                'is_active'  => 1
            ]
        ]);

        $resources = [];
        if (!count($iterator)) {
            return [];
        }
        foreach ($iterator as $data) {
            $item = new $data['itemtype']();
            if (!$item->getFromDB($data['items_id'])) {
                continue;
            }

            $resources[] = [
                'id' => $data['itemtype'] . "-" . $data['items_id'],
                'title' => sprintf(__("%s - %s"), $data['itemtype']::getTypeName(), $item->getName()),
            ];
        }

        return $resources;
    }


    /**
     * Change dates of a selected reservation.
     * Called from a drag&drop in planning
     *
     * @param array $options: must contains this keys :
     *  - id : integer to identify reservation
     *  - begin : planning start .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     *  - end : planning end .
     *       (should be an ISO_8601 date, but could be anything wo can be parsed by strtotime)
     * @return bool
     */
    public static function updateEvent(array $event = []): bool
    {

        $reservation = new static();
        if (!$reservation->getFromDB((int) $event['id'])) {
            return false;
        }

        return $reservation->update([
            'id'    => (int) $event['id'],
            'begin' => date("Y-m-d H:i:s", strtotime($event['start'])),
            'end'   => date("Y-m-d H:i:s", strtotime($event['end'])),
        ]);
    }


    /**
     * Display for reservation
     *
     * @param $ID              ID of the reservation (empty for create new)
     * @param $options   array of possibles options:
     *     - item  reservation items ID for creation process
     *     - date date for creation process
     **/
    public function showForm($ID, array $options = [])
    {
        global $CFG_GLPI;

        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        $resa = new self();

        if (!empty($ID) && $ID > 0) {
            if (!$resa->getFromDB($ID)) {
                return false;
            }

            if (!$resa->can($ID, UPDATE)) {
                return false;
            }
           // Set item if not set
            if (
                (!isset($options['item']) || (count($options['item']) == 0))
                && ($itemid = $resa->getField('reservationitems_id'))
            ) {
                $options['item'][$itemid] = $itemid;
            }
        } else {
            $resa->getEmpty();
            $resa->fields["begin"] = date("Y-m-d H:i:s", strtotime($options['begin']));
            if (!isset($options['end'])) {
                $resa->fields["end"] = date("Y-m-d H:00:00", strtotime($resa->fields["begin"]) + HOUR_TIMESTAMP);
            } else {
                $resa->fields["end"] = date("Y-m-d H:i:s", strtotime($options['end']));
            }
        }

        echo "<div class='center'><form method='post' name=form action='" . Reservation::getFormURL() . "'>";

        if (!empty($ID)) {
            echo "<input type='hidden' name='id' value='$ID'>";
        }

        echo "<table class='tab_cadre' width='100%'>";
        echo "<tr><th colspan='2'>" . __('Reserve an item') . "</th></tr>\n";

       // Add Hardware name
        $r = new ReservationItem();

        echo "<tr class='tab_bg_1'><td>" . _n('Item', 'Items', 1) . "</td>";
        echo "<td>";

        $temp_item  = $options['item'];
        $first_item = array_pop($temp_item);
        if (count($options['item']) == 1 && $first_item == 0) {
           // only one id = 0, display an item dropdown
            Dropdown::showSelectItemFromItemtypes([
                'items_id_name'   => 'items[]',
                'itemtypes'       => $CFG_GLPI['reservation_types'],
                'entity_restrict' => Session::getActiveEntity(),
                'checkright'      => false,
                'ajax_page'       => $CFG_GLPI['root_doc'] . '/ajax/reservable_items.php'
            ]);
            echo "<span id='item_dropdown'>";
        } else {
           // existing item(s)
            foreach ($options['item'] as $itemID) {
                $r->getFromDB($itemID);
                $type = $r->fields["itemtype"];
                $name = NOT_AVAILABLE;
                $item = null;

                if ($item = getItemForItemtype($r->fields["itemtype"])) {
                    $type = $item->getTypeName();

                    if ($item->getFromDB($r->fields["items_id"])) {
                        $name = $item->getName();
                    } else {
                        $item = null;
                    }
                }

                echo "<span class='b'>" . sprintf(__('%1$s - %2$s'), $type, $name) . "</span><br>";
                echo "<input type='hidden' name='items[$itemID]' value='$itemID'>";
            }
        }

        echo "</td></tr>";

        $uid = (empty($ID) ? Session::getLoginUserID() : $resa->fields['users_id']);
        echo "<tr class='tab_bg_2'><td>" . __('By') . "</td>";
        echo "<td>";

        $entities_id   = Session::getActiveEntity();
        $is_recursive  = Session::getIsActiveEntityRecursive();
        if (isset($item)) {
            $entities_id  = $item->getEntityID();
            $is_recursive = $item->isRecursive();
        }
        if (
            !Session::haveRight("reservation", UPDATE)
            || !Session::haveAccessToEntity($entities_id)
        ) {
            echo "<input type='hidden' name='users_id' value='" . $uid . "'>";
            echo Dropdown::getDropdownName(
                User::getTable(),
                $uid
            );
        } else {
            User::dropdown([
                'value'        => $uid,
                'entity'       => $entities_id,
                'entity_sons'  => $is_recursive,
                'right'        => 'all'
            ]);
        }
        echo "</td></tr>\n";
        echo "<tr class='tab_bg_2'><td>" . __('Start date') . "</td><td>";
        Html::showDateTimeField("resa[begin]", [
            'value'      => $resa->fields["begin"],
            'maybeempty' => false
        ]);
        echo "</td></tr>";
        $default_delay = floor((strtotime($resa->fields["end"]) - strtotime($resa->fields["begin"]))
                             / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP)
                       * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
        echo "<tr class='tab_bg_2'><td>" . __('Duration') . "</td><td>";
        $rand = Dropdown::showTimeStamp("resa[_duration]", [
            'min'        => 0,
            'max'        => 24 * HOUR_TIMESTAMP,
            'value'      => $default_delay,
            'emptylabel' => __('Specify an end date')
        ]);
        echo "<br><div id='date_end$rand'></div>";
        $params = [
            'duration'     => '__VALUE__',
            'end'          => $resa->fields["end"],
            'name'         => "resa[end]"
        ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_resa[_duration]$rand",
            "date_end$rand",
            $CFG_GLPI["root_doc"] . "/ajax/planningend.php",
            $params
        );

        if ($default_delay == 0) {
            $params['duration'] = 0;
            Ajax::updateItem("date_end$rand", $CFG_GLPI["root_doc"] . "/ajax/planningend.php", $params);
        }
        Alert::displayLastAlert('Reservation', $ID);
        echo "</td></tr>";

        if (empty($ID)) {
            echo "<tr class='tab_bg_2'><td>" . __('Repetition') . "</td>";
            echo "<td>";
            $rand = Dropdown::showFromArray('periodicity[type]', [
                ''      => _x('periodicity', 'None'),
                'day'   => _x('periodicity', 'Daily'),
                'week'  => _x('periodicity', 'Weekly'),
                'month' => _x('periodicity', 'Monthly')
            ]);
            $field_id = Html::cleanId("dropdown_periodicity[type]$rand");

            Ajax::updateItemOnSelectEvent(
                $field_id,
                "resaperiodcontent$rand",
                $CFG_GLPI["root_doc"] . "/ajax/resaperiod.php",
                [
                    'type'     => '__VALUE__',
                    'end'      => $resa->fields["end"]
                ]
            );
            echo "<br><div id='resaperiodcontent$rand'></div>";

            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_2'><td>" . __('Comments') . "</td>";
        echo "<td><textarea name='comment' rows='8' class='form-control'>" . $resa->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        if (empty($ID)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='top center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
        } else {
            if (
                ($resa->fields["users_id"] == Session::getLoginUserID())
                || Session::haveRightsOr(static::$rightname, [PURGE, UPDATE])
            ) {
                echo "<tr class='tab_bg_2'>";
                if (
                    ($resa->fields["users_id"] == Session::getLoginUserID())
                    || Session::haveRight(static::$rightname, PURGE)
                ) {
                    echo "<td class='top center'>";
                    echo "<input type='submit' name='purge' value=\"" . _sx('button', 'Delete permanently') . "\"
                      class='btn btn-primary'>";
                    if ($resa->fields["group"] > 0) {
                        echo "<br><input type='checkbox' name='_delete_group'>&nbsp;" .
                             __s('Delete all repetition');
                    }
                    echo "</td>";
                }
                if (
                    ($resa->fields["users_id"] == Session::getLoginUserID())
                    || Session::haveRight(static::$rightname, UPDATE)
                ) {
                    echo "<td class='top center'>";
                    echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\"
                     class='btn btn-primary'>";
                    echo "</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table>";
        Html::closeForm();
        echo "</div>";

        return true;
    }


    /**
     * compute periodicities for reservation
     *
     * @since 0.84
     *
     * @param $begin             begin of the initial reservation
     * @param $end               begin of the initial reservation
     * @param $options   array   periodicity parameters : must contain : type (day/week/month), end
     **/
    public static function computePeriodicities($begin, $end, $options = [])
    {
        $toadd = [];

        if (isset($options['type']) && isset($options['end'])) {
            $begin_time = strtotime($begin);
            $end_time   = strtotime($end);
            $repeat_end = strtotime($options['end'] . ' 23:59:59');

            switch ($options['type']) {
                case 'day':
                    $begin_time = strtotime("+1 day", $begin_time);
                    $end_time   = strtotime("+1 day", $end_time);
                    while ($begin_time < $repeat_end) {
                        $toadd[date('Y-m-d H:i:s', $begin_time)] = date('Y-m-d H:i:s', $end_time);
                        $begin_time = strtotime("+1 day", $begin_time);
                        $end_time   = strtotime("+1 day", $end_time);
                    }
                    break;

                case 'week':
                    $dates = [];

                   // No days set add 1 week
                    if (!isset($options['days'])) {
                        $dates = [['begin' => strtotime('+1 week', $begin_time),
                            'end'   => strtotime('+1 week', $end_time)
                        ]
                        ];
                    } else {
                        if (is_array($options['days'])) {
                            $begin_hour = $begin_time - strtotime(date('Y-m-d', $begin_time));
                            $end_hour   = $end_time - strtotime(date('Y-m-d', $end_time));
                            foreach ($options['days'] as $day => $val) {
                                $dates[] = ['begin' => strtotime("next $day", $begin_time) + $begin_hour,
                                    'end'   => strtotime("next $day", $end_time) + $end_hour
                                ];
                            }
                        }
                    }

                    foreach ($dates as $key => $val) {
                        $begin_time = $val['begin'];
                        $end_time   = $val['end'];

                        while ($begin_time < $repeat_end) {
                            $toadd[date('Y-m-d H:i:s', $begin_time)] = date('Y-m-d H:i:s', $end_time);
                            $begin_time = strtotime('+1 week', $begin_time);
                            $end_time   = strtotime('+1 week', $end_time);
                        }
                    }
                    break;

                case 'month':
                    if (isset($options['subtype'])) {
                        switch ($options['subtype']) {
                            case 'date':
                                $i = 1;
                                $calc_begin_time = strtotime("+$i month", $begin_time);
                                $calc_end_time   = strtotime("+$i month", $end_time);
                                while ($calc_begin_time < $repeat_end) {
                                    $toadd[date('Y-m-d H:i:s', $calc_begin_time)] = date(
                                        'Y-m-d H:i:s',
                                        $calc_end_time
                                    );
                                    $i++;
                                    $calc_begin_time = strtotime("+$i month", $begin_time);
                                    $calc_end_time   = strtotime("+$i month", $end_time);
                                }
                                break;

                            case 'day':
                                $dayofweek = date('l', $begin_time);

                                $i               = 1;
                                $calc_begin_time = strtotime("+$i month", $begin_time);
                                $calc_end_time   = strtotime("+$i month", $end_time);
                                $begin_hour      = $begin_time - strtotime(date('Y-m-d', $begin_time));
                                $end_hour        = $end_time - strtotime(date('Y-m-d', $end_time));

                                $calc_begin_time = strtotime("next $dayofweek", $calc_begin_time)
                                        + $begin_hour;
                                $calc_end_time   = strtotime("next $dayofweek", $calc_end_time) + $end_hour;

                                while ($calc_begin_time < $repeat_end) {
                                     $toadd[date('Y-m-d H:i:s', $calc_begin_time)] = date(
                                         'Y-m-d H:i:s',
                                         $calc_end_time
                                     );
                                       $i++;
                                       $calc_begin_time = strtotime("+$i month", $begin_time);
                                       $calc_end_time   = strtotime("+$i month", $end_time);
                                       $calc_begin_time = strtotime("next $dayofweek", $calc_begin_time)
                                              + $begin_hour;
                                       $calc_end_time   = strtotime("next $dayofweek", $calc_end_time)
                                              + $end_hour;
                                }
                                break;
                        }
                    }

                    break;
            }
        }

        return $toadd;
    }


    /**
     * Display reservations for an item
     *
     * @param $item            CommonDBTM object for which the reservation tab need to be displayed
     * @param $withtemplate    withtemplate param (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        if (!Session::haveRight("reservation", READ)) {
            return false;
        }

       // scheduler feature key
       // schedular part of fullcalendar is distributed with opensource licence (GLPv3)
       // but this licence is incompatible with GLPI (GPLv2)
       // see https://fullcalendar.io/license
        $scheduler_key = Plugin::doHookFunction('planning_scheduler_key');

        echo "<div class='firstbloc'>";
        ReservationItem::showActivationFormForItem($item);

        $ri = new ReservationItem();
        if (!$ri->getFromDBbyItem($item->getType(), $item->getID())) {
            return;
        }

       // js vars
        $rand   = mt_rand();
        $ID     = $ri->fields['id'];

        echo "<br>";
        echo "<h1>" . __('Reservations for this item') . "</h1>";
        echo "<div id='reservations_planning_$rand' class='reservations-planning tabbed'></div>";

        $defaultDate = date('Y-m-d');
        if (isset($_REQUEST['defaultDate'])) {
            $defaultDate = $_REQUEST['defaultDate'];
        }
        $js = <<<JAVASCRIPT
      $(function() {
         var reservation = new Reservations();
         reservation.init({
            id: $ID,
            is_all: false,
            is_tab: true,
            rand: $rand,
            currentv: 'listFull',
            defaultDate: '$defaultDate',
            license_key: '$scheduler_key',
         });
         reservation.displayPlanning();
      });
JAVASCRIPT;
        echo Html::scriptBlock($js);
        echo "</div>"; // .firstbloc
    }


    /**
     * Display reservations for a user
     *
     * @param $ID ID a the user
     **/
    public static function showForUser($ID)
    {
        global $DB, $CFG_GLPI;

        $resaID = 0;

        if (!Session::haveRight("reservation", READ)) {
            return false;
        }

        echo "<div class='firstbloc'>";
        $now = $_SESSION["glpi_currenttime"];

       // Print reservation in progress
        $iterator = $DB->request([
            'SELECT'    => [
                'begin',
                'end',
                'items_id',
                'glpi_reservationitems.entities_id',
                'users_id',
                'glpi_reservations.comment',
                'reservationitems_id',
                'completename'
            ],
            'FROM'      => 'glpi_reservations',
            'LEFT JOIN' => [
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id'
                    ]
                ],
                'glpi_entities' => [
                    'ON' => [
                        'glpi_reservationitems' => 'entities_id',
                        'glpi_entities'         => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'end'       => ['>', $now],
                'users_id'  => $ID
            ],
            'ORDERBY'   => 'begin'
        ]);

        $ri = new ReservationItem();
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='6'>" . __('Current and future reservations') . "</th></tr>\n";

        if (count($iterator) == 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='6'>" . __('No reservation') . "</td></tr\n>";
        } else {
            echo "<tr><th>" . __('Start date') . "</th>";
            echo "<th>" . __('End date') . "</th>";
            echo "<th>" . _n('Item', 'Items', 1) . "</th>";
            echo "<th>" . Entity::getTypeName(1) . "</th>";
            echo "<th>" . __('By') . "</th>";
            echo "<th>" . __('Comments') . "</th><th>&nbsp;</th></tr>\n";

            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_2'>";
                echo "<td class='center'>" . Html::convDateTime($data["begin"]) . "</td>";
                echo "<td class='center'>" . Html::convDateTime($data["end"]) . "</td>";

                if ($ri->getFromDB($data["reservationitems_id"])) {
                    $link = "&nbsp;";

                    if ($item = getItemForItemtype($ri->fields['itemtype'])) {
                        if ($item->getFromDB($ri->fields['items_id'])) {
                             $link = $item->getLink();
                        }
                    }
                    echo "<td class='center'>$link</td>";
                    echo "<td class='center'>" . $data['completename'] . "</td>";
                } else {
                    echo "<td class='center'>&nbsp;</td>";
                }

                echo "<td class='center'>" . getUserName($data["users_id"]) . "</td>";
                echo "<td class='center'>" . nl2br($data["comment"]) . "</td>";
                echo "<td class='center'>";
                list($annee, $mois, $jour) = explode("-", $data["begin"]);
                echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/reservation.php?reservationitems_id=" .
                  $data["reservationitems_id"] . "&amp;mois_courant=$mois&amp;" .
                  "annee_courante=$annee' title=\"" . __s('See planning') . "\">";
                echo "<i class='far fa-calendar-alt'></i>";
                echo "<span class='sr-only'>" . __('See planning') . "</span>";
                echo "</a></td></tr>\n";
            }
        }
        echo "</table></div>\n";

       // Print old reservations
        $iterator = $DB->request([
            'SELECT'    => [
                'begin',
                'end',
                'items_id',
                'glpi_reservationitems.entities_id',
                'users_id',
                'glpi_reservations.comment',
                'reservationitems_id',
                'completename'
            ],
            'FROM'      => 'glpi_reservations',
            'LEFT JOIN' => [
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id'
                    ]
                ],
                'glpi_entities'         => [
                    'ON' => [
                        'glpi_reservationitems' => 'entities_id',
                        'glpi_entities'         => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'end'       => ['<=', $now],
                'users_id'  => $ID
            ],
            'ORDERBY'   => 'begin DESC'
        ]);

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='6'>" . __('Past reservations') . "</th></tr>\n";

        if (count($iterator) == 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='6'>" . __('No reservation') . "</td></tr>\n";
        } else {
            echo "<tr><th>" . __('Start date') . "</th>";
            echo "<th>" . __('End date') . "</th>";
            echo "<th>" . _n('Item', 'Items', 1) . "</th>";
            echo "<th>" . Entity::getTypeName(1) . "</th>";
            echo "<th>" . __('By') . "</th>";
            echo "<th>" . __('Comments') . "</th><th>&nbsp;</th></tr>\n";

            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_2'>";
                echo "<td>" . Html::convDateTime($data["begin"]) . "</td>";
                echo "<td>" . Html::convDateTime($data["end"]) . "</td>";

                if ($ri->getFromDB($data["reservationitems_id"])) {
                    $link = "&nbsp;";

                    if ($item = getItemForItemtype($ri->fields['itemtype'])) {
                        if ($item->getFromDB($ri->fields['items_id'])) {
                             $link = $item->getLink();
                        }
                    }
                    echo "<td>$link</td>";
                    echo "<td>" . $data['completename'] . "</td>";
                } else {
                    echo "<td>&nbsp;</td>";
                }

                echo "<td>" . getUserName($data["users_id"]) . "</td>";
                echo "<td>" . nl2br($data["comment"]) . "</td>";
                echo "<td>";
                list($annee, $mois, $jour) = explode("-", $data["begin"]);
                echo "<a href='" . $item::getFormURLWithID($ri->fields['items_id']) .
                 "&forcetab=Reservation$1&tab_params[defaultDate]={$data["begin"]}' " .
                  "title=\"" . __s('See planning') . "\">";
                echo "<i class='far fa-calendar-alt'></i>";
                echo "<span class='sr-only'>" . __('See planning') . "</span>";
                echo "</td></tr>\n";
            }
        }
        echo "</table></div>\n";
    }


    public static function getIcon()
    {
        return "ti ti-calendar-event";
    }
}
