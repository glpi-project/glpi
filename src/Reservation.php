<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Glpi\RichText\RichText;

use function Safe\strtotime;

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

    public static function getTypeName($nb = 0)
    {
        return _n('Reservation', 'Reservations', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            !$withtemplate
            && Session::haveRight("reservation", READ)
        ) {
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), 0, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === User::class) {
            self::showForUser($_GET["id"]);
        } elseif ($item instanceof CommonDBTM) {
            self::showForItem($item);
        } else {
            throw new LogicException("Item must be CommonDBTM");
        }
        return true;
    }

    public function pre_deleteItem()
    {
        global $CFG_GLPI;

        if (
            isset($this->fields["users_id"])
            && (($this->fields["users_id"] === Session::getLoginUserID())
              || Session::haveRight("reservation", PURGE))
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

    public function prepareInputForUpdate($input)
    {
        // Save fields
        $oldfields             = $this->fields;
        // Needed for test already planned
        if (isset($input["begin"])) {
            $this->fields["begin"] = $input["begin"];
        }
        if (isset($input["end"])) {
            $this->fields["end"] = $input["end"];
        }

        if (!$this->isReservationInputValid()) {
            return false;
        }

        // Restore fields
        $this->fields = $oldfields;

        return parent::prepareInputForUpdate($input);
    }

    public function post_updateItem($history = true)
    {
        global $CFG_GLPI;

        if (
            count($this->updates)
            && $CFG_GLPI["use_notifications"]
            && !isset($this->input['_disablenotif'])
        ) {
            NotificationEvent::raiseEvent("update", $this);
        }

        parent::post_updateItem($history);
    }

    public function prepareInputForAdd($input)
    {
        // Error on previous added reservation on several add
        if (isset($input['_ok']) && !$input['_ok']) {
            return false;
        }

        // set new date.
        $this->fields["reservationitems_id"] = $input["reservationitems_id"];
        $this->fields["begin"] = $input["begin"];
        $this->fields["end"] = $input["end"];

        if (!$this->isReservationInputValid()) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public static function handleAddForm(array $input): void
    {
        if (empty($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID();
        }

        // Check if user has permission to create reservations
        if (!self::canCreate()) {
            Session::addMessageAfterRedirect(
                __s('You do not have permission to create reservations'),
                false,
                ERROR
            );
            return;
        }

        // Additional check: if creating for another user, ensure user has CREATE right (not just RESERVEANITEM)
        if ($input['users_id'] != Session::getLoginUserID() && !Session::haveRight(self::$rightname, CREATE)) {
            Session::addMessageAfterRedirect(
                __s('You do not have permission to create reservations for other users'),
                false,
                ERROR
            );
            return;
        }

        Toolbox::manageBeginAndEndPlanDates($input['resa']);
        if (!isset($input['resa']["begin"], $input['resa']["end"])) {
            return;
        }

        if (!isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
            Session::addMessageAfterRedirect(
                __s('No selected items'),
                false,
                ERROR
            );
        }

        $dates_to_add = [];
        $dates_to_add[$input['resa']["begin"]] = $input['resa']["end"];
        if (!empty($input['periodicity']['type'])) {
            $dates_to_add += self::computePeriodicities(
                $input['resa']["begin"],
                $input['resa']["end"],
                $input['periodicity']
            );
        }
        ksort($dates_to_add);

        foreach ($input['items'] as $reservationitems_id) {
            $rr = new self();
            $group = (count($dates_to_add) > 1) ? $rr->getUniqueGroupFor($reservationitems_id) : null;

            foreach ($dates_to_add as $begin => $end) {
                $reservation_input = [
                    'begin' => $begin,
                    'end' => $end,
                    'reservationitems_id' => $reservationitems_id,
                    'comment' => $input['comment'],
                    'users_id' => (int) $input['users_id'],
                ];
                if (count($dates_to_add) > 1) {
                    $reservation_input['group'] = $group;
                }

                if ($newID = $rr->add($reservation_input)) {
                    Event::log(
                        $newID,
                        "reservation",
                        4,
                        "inventory",
                        sprintf(
                            __s('%1$s adds the reservation %2$s for item %3$s'),
                            $_SESSION["glpiname"],
                            $newID,
                            $reservationitems_id
                        )
                    );

                    $rri = new ReservationItem();
                    $rri->getFromDB($reservationitems_id);
                    $item = getItemForItemtype($rri->fields["itemtype"]);
                    $item->getFromDB($rri->fields["items_id"]);

                    Session::addMessageAfterRedirect(
                        sprintf(
                            __s('Reservation added for item %s at %s'),
                            $item->getLink(),
                            htmlescape(Html::convDateTime($reservation_input['begin']))
                        )
                    );
                }
            }
        }
    }

    /**
     * Check reservation input.
     *
     * @return bool
     */
    private function isReservationInputValid(): bool
    {
        if (!$this->test_valid_date()) {
            Session::addMessageAfterRedirect(
                __s('Error in entering dates. The starting date is later than the ending date'),
                false,
                ERROR
            );
            return false;
        }

        if ($this->is_reserved()) {
            Session::addMessageAfterRedirect(
                __s('The required item is already reserved for this timeframe'),
                false,
                ERROR
            );
            return false;
        }

        return true;
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
     * Returns an integer that is not already used as a group for the given reservation item.
     * @param $reservationitems_id
     * @return int
     */
    public function getUniqueGroupFor($reservationitems_id): int
    {
        global $DB;

        do {
            $rand = random_int(1, mt_getrandmax());

            $result = $DB->request([
                'COUNT'  => 'cpt',
                'FROM'   => 'glpi_reservations',
                'WHERE'  => [
                    'reservationitems_id'   => $reservationitems_id,
                    'group'                 => $rand,
                ],
            ])->current();
            $count = (int) $result['cpt'];
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
            'FROM'   => static::getTable(),
            'WHERE'  => $where + [
                'reservationitems_id'   => $this->fields['reservationitems_id'],
                'end'                   => ['>', $this->fields['begin']],
                'begin'                 => ['<', $this->fields['end']],
            ],
        ])->current();
        return $result['cpt'] > 0;
    }

    /**
     * Current dates are valid ? begin before end
     *
     * @return boolean
     **/
    public function test_valid_date()
    {
        return (!empty($this->fields["begin"])
              && !empty($this->fields["end"])
              && (strtotime($this->fields["begin"]) < strtotime($this->fields["end"])));
    }

    public static function canView(): bool
    {
        // Users with READ right can see all reservations
        if (Session::haveRight(self::$rightname, READ)) {
            return true;
        }

        // Users with RESERVEANITEM right can see their own reservations (checked in canViewItem)
        if (Session::haveRight(self::$rightname, ReservationItem::RESERVEANITEM)) {
            return true;
        }

        // Delegate to parent to check parent item permissions
        return parent::canView();
    }

    public static function canCreate(): bool
    {
        return (Session::haveRightsOr(self::$rightname, [CREATE, ReservationItem::RESERVEANITEM]));
    }

    public function canCreateItem(): bool
    {
        return self::canCreate();
    }

    public static function canUpdate(): bool
    {
        return (Session::haveRightsOr(self::$rightname, [UPDATE, ReservationItem::RESERVEANITEM]));
    }

    public static function canDelete(): bool
    {
        return (Session::haveRight(self::$rightname, ReservationItem::RESERVEANITEM));
    }

    public static function canPurge(): bool
    {
        return (Session::haveRightsOr(self::$rightname, [PURGE, ReservationItem::RESERVEANITEM]));
    }

    public function canChildItem($methodItem, $methodNotItem)
    {
        // All users can manage their own reservations (read, create, update, purge)
        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            return true;
        }

        // If user only has RESERVEANITEM right (no other reservation rights),
        // they can only manage their own reservations (already handled above)
        $reservation_rights = $_SESSION['glpiactiveprofile'][self::$rightname] ?? 0;
        if ($reservation_rights == ReservationItem::RESERVEANITEM) {
            return false; // Only own reservations allowed with RESERVEANITEM only
        }

        // Check if user has rights on the parent item (asset)
        /** @var ReservationItem $ri */
        $ri = $this->getItem();
        $item = $ri !== false ? $ri->getItem() : false;
        if ($item !== false) {
            // Users with permission to update the specific asset can CRUD all reservations for that asset
            if ($item->canUpdateItem() && Session::haveRight($item::$rightname, UPDATE)) {
                return true;
            }
        }

        // Check if user has global rights for this operation
        if (!parent::canChildItem($methodItem, $methodNotItem)) {
            return false;
        }

        // At minimum, check entity access for the asset
        if ($item !== false) {
            return Session::haveAccessToEntity($item->getEntityID(), $item->isRecursive());
        }

        return false;
    }

    public function canViewItem(): bool
    {
        // Users with READ right can see all reservations they have entity access to
        if (Session::haveRight(self::$rightname, READ)) {
            return $this->canChildItem('canViewItem', 'canView');
        }

        // All users can see their own reservations
        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            return true;
        }

        // If user only has RESERVEANITEM right, they can only see their own reservations
        $reservation_rights = $_SESSION['glpiactiveprofile'][self::$rightname] ?? 0;
        if ($reservation_rights == ReservationItem::RESERVEANITEM) {
            return false; // Only own reservations allowed with RESERVEANITEM only
        }

        // Check if user has rights on the parent item (asset)
        /** @var ReservationItem $ri */
        $ri = $this->getItem();
        if ($ri === false) {
            return false;
        }

        $item = $ri->getItem();
        if ($item === false) {
            return false;
        }

        // Users with permission to update the specific asset can see all reservations for that asset
        if ($item->canUpdateItem() && Session::haveRight($item::$rightname, UPDATE)) {
            return true;
        }

        return false;
    }

    public function canPurgeItem(): bool
    {
        // Follow the same pattern as canUpdateItem and canDeleteItem by delegating to canChildItem
        return $this->canChildItem('canUpdateItem', 'canUpdate');
    }

    public function post_purgeItem()
    {
        global $DB;

        if (isset($this->input['_delete_group']) && $this->input['_delete_group']) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_reservations',
                'WHERE'  => [
                    'reservationitems_id'   => $this->fields['reservationitems_id'],
                    'group'                 => $this->fields['group'],
                ],
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
     * @param integer $ID   ID of the reservation item (if 0 display all)
     **/
    public static function showCalendar(int $ID = 0)
    {
        global $CFG_GLPI;

        if (!Session::haveRightsOr("reservation", [READ, ReservationItem::RESERVEANITEM])) {
            return false;
        }

        $rand = mt_rand();

        if ($ID > 0) {
            $m = new ReservationItem();
            $m->getFromDB($ID);

            if ((!isset($m->fields['is_active'])) || !$m->fields['is_active']) {
                echo "<div class='text-center'>";
                echo __s('Device temporarily unavailable');
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

            $all = "<a class='btn btn-primary ms-2 view-all' href='reservation.php?reservationitems_id=0'>"
               . __s('View all items')
               . "&nbsp;<i class='ti ti-eye'></i>"
            . "</a>";
        } else {
            $type = "";
            $name = __('All reservable devices');
            $all  = "";
        }
        echo "<div class='card'>";
        echo "<div class='text-center card-header'>";
        echo "<img src='" . htmlescape($CFG_GLPI["root_doc"]) . "/pics/reservation.png' alt='' class='reservation-icon'>";
        echo "<h2 class='item-name'>" . htmlescape($name) . "</h2>";
        echo $all;
        echo "</div>"; // .center
        echo "<div id='reservations_planning_$rand' class='card-body reservations-planning'></div>";
        echo "</div>"; // .reservation_panel

        $can_reserve = (
            Session::haveRight("reservation", ReservationItem::RESERVEANITEM)
            && count(self::getReservableItemtypes()) > 0
        );

        $default_date = date('Y-m-d');
        if (isset($_REQUEST['defaultDate'])) {
            $default_date = $_REQUEST['defaultDate'];
        } elseif (isset($_REQUEST['month'], $_REQUEST['year'])) {
            $month = (int) $_REQUEST['month'];
            $year  = (int) $_REQUEST['year'];
            $default_date = sprintf('%04d-%02d-01', $year, $month);
        }

        $js = "
            $(function() {
                var reservation = new Reservations();
                reservation.init({
                    id: $ID,
                    is_all: " . ($ID === 0 ? "true" : "false") . ",
                    rand: $rand,
                    can_reserve: " . ($can_reserve ? "true" : "false") . ",
                    now: '" . jsescape($_SESSION["glpi_currenttime"]) . "',
                    defaultDate: '" . jsescape($default_date) . "',
                });
                reservation.displayPlanning();
          });
        ";
        echo Html::scriptBlock($js);
    }

    public static function getEvents(array $params): array
    {
        global $DB, $CFG_GLPI;

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

        $can_read    = Session::haveRight("reservation", READ);
        $can_edit    = Session::getCurrentInterface() === "central" && Session::haveRight("reservation", UPDATE);
        $can_reserve = Session::haveRight("reservation", ReservationItem::RESERVEANITEM);

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
                        $res_table   => 'reservationitems_id',
                    ],
                ],
            ],
            'WHERE' => [
                'end'   => ['>', $start],
                'begin' => ['<', $end],
            ] + $where,
        ]);

        $events = [];
        if (!count($iterator)) {
            return [];
        }
        foreach ($iterator as $data) {
            $item = getItemForItemtype($data['itemtype']);
            if (!$item->getFromDB($data['items_id'])) {
                continue;
            }
            if (!Session::haveAccessToEntity($item->getEntityID(), $item->isRecursive())) {
                continue;
            }

            $my_item = $data['users_id'] === Session::getLoginUserID();

            $data['comment'] = RichText::getSafeHtml($data['comment']);
            if ($can_read || $my_item) {
                $user->getFromDB($data['users_id']);
                $data['comment'] .= '<br />' . htmlescape(sprintf(__("Reserved by %s"), $user->getFriendlyName()));
            }

            $name = $item->getName([
                'complete' => true,
            ]);

            $editable = $can_edit || ($can_reserve && $my_item);

            $events[] = [
                'id'          => $data['id'],
                'resourceId'  => $data['itemtype'] . "-" . $data['items_id'],
                'start'       => $data['begin'],
                'end'         => $data['end'],
                'comment'     => $can_read || $my_item ? $data['comment'] : '',
                'title'       => $params['reservationitems_id'] ? "" : $name,
                'icon'        => $item->getIcon(),
                'description' => $item->getTypeName(),
                'itemtype'    => $data['itemtype'],
                'items_id'    => $data['items_id'],
                'color'       => Toolbox::getColorForString($name),
                'ajaxurl'     => $CFG_GLPI['root_doc'] . '/ajax/reservations.php?action=add_edit_reservation_fromselect&id=' . $data['id'],
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
                'is_active'  => 1,
            ],
        ]);

        $resources = [];
        if (!count($iterator)) {
            return [];
        }
        foreach ($iterator as $data) {
            $item = getItemForItemtype($data['itemtype']);
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
     * @param array{id: integer, start: string, end: string} $event
     * <ul>
     *     <li>id: integer to identify reservation</li>
     *     <li>start: planning start (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)</li>
     *     <li>end: planning end (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)</li>
     * </ul>
     * @return bool
     */
    public static function updateEvent(array $event): bool
    {
        $reservation = new static();
        if (!$reservation->getFromDB((int) $event['id'])) {
            return false;
        }

        $event = Planning::cleanDates($event);

        return $reservation->update([
            'id'    => (int) $event['id'],
            'begin' => date("Y-m-d H:i:s", strtotime($event['start'])),
            'end'   => date("Y-m-d H:i:s", strtotime($event['end'])),
        ]);
    }

    /**
     * Display for reservation
     *
     * @param integer $ID ID of the reservation (empty for create new)
     * @param array $options possible optional options:
     * <ul>
     *      <li>item: Reservation items ID(s) for creation process. The array keys and values are expected to be symmetrical (ex: [2 => 2, 5 => 5])</li>
     *      <li>begin: planning start (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)</li>
     *      <li>end: planning end (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)</li>
     *  </ul>
     **/
    public function showForm($ID, array $options = [])
    {
        global $CFG_GLPI;

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
                (!isset($options['item']) || (count($options['item']) === 0))
                && ($itemid = $resa->getField('reservationitems_id'))
            ) {
                $options['item'][$itemid] = $itemid;
            }
        } else {
            if (!self::canCreate()) {
                return false;
            }

            $resa->getEmpty();
            $options = Planning::cleanDates($options);
            $resa->fields["begin"] = !empty($options['begin']) ? date("Y-m-d H:i:s", strtotime($options['begin'])) : date('Y-m-d H:00:00', strtotime(Session::getCurrentTime()));
            if (!isset($options['end'])) {
                $resa->fields["end"] = date("Y-m-d H:00:00", strtotime($resa->fields["begin"]) + HOUR_TIMESTAMP);
            } else {
                $resa->fields["end"] = date("Y-m-d H:i:s", strtotime($options['end']));
            }
        }

        $r = new ReservationItem();
        $items = [];
        foreach ($options['item'] as $itemID) {
            // existing item(s)
            if ($r->getFromDB($itemID)) {
                $type = $r->fields["itemtype"];
                $name = NOT_AVAILABLE;
                $item = null;

                if ($item = getItemForItemtype($r->fields["itemtype"])) {
                    $type = $item::getTypeName(1);

                    if ($item->getFromDB($r->fields["items_id"])) {
                        $name = $item->getName();
                    } else {
                        $item = null;
                    }
                }

                $items[] = [
                    'id'        => $itemID,
                    'type_name' => sprintf(__('%1$s - %2$s'), $type, $name),
                    'comment'   => $r->fields['comment'] ?? '',
                ];
            }
        }

        $uid = (empty($ID) ? Session::getLoginUserID() : $resa->fields['users_id']);
        $resa->fields["users_id_friendlyname"] = User::getFriendlyNameById($uid);

        $entities_id  = (isset($item)) ? $item->getEntityID() : Session::getActiveEntity();
        $canedit = Session::haveRight("reservation", UPDATE) && Session::haveAccessToEntity($entities_id);

        $default_delay = floor((strtotime($resa->fields["end"]) - strtotime($resa->fields["begin"]))
                             / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP)
                       * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;

        if ((int) $default_delay === 0) {
            $options['duration'] = 0;
        }

        $options['canedit'] = ($resa->fields["users_id"] === Session::getLoginUserID())
                             || Session::haveRight(static::$rightname, UPDATE);
        $options['candel'] = ($resa->fields["users_id"] === Session::getLoginUserID())
                             || Session::haveRightsOr(static::$rightname, [PURGE, UPDATE]);

        $resa->initForm($ID, $resa->fields);
        TemplateRenderer::getInstance()->display('components/form/reservation.html.twig', [
            'item'              => $resa,
            'items'             => $items,
            'itemtypes'         => self::getReservableItemtypes(),
            'default_delay'     => $default_delay,
            'params'            => $options,
            'canedit'           => $canedit,
        ]);
        return true;
    }

    /**
     * compute periodicities for reservation
     *
     * @since 0.84
     *
     * @param string $begin  Planning start (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)
     * @param string $end    Planning end (should be an ISO_8601 date, but could be anything that can be parsed by strtotime)
     * @param array{type: 'day'|'week'|'month', end: string, subtype?: string, days?: integer} $options Periodicity parameters
     **/
    public static function computePeriodicities($begin, $end, $options)
    {
        $toadd = [];
        if (!isset($options['type'], $options['end'])) {
            return $toadd;
        }

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
                        'end'   => strtotime('+1 week', $end_time),
                    ],
                    ];
                } else {
                    if (is_array($options['days'])) {
                        $begin_hour = $begin_time - strtotime(date('Y-m-d', $begin_time));
                        $end_hour   = $end_time - strtotime(date('Y-m-d', $end_time));
                        foreach ($options['days'] as $day => $val) {
                            $end_day = $day;
                            // Check that the start and end times are different else set the end day at the next day
                            if ($begin_hour == $end_hour) {
                                $end_day = date('l', strtotime($day . ' +1 day'));
                            }
                            $dates[] = ['begin' => strtotime("next $day", $begin_time) + $begin_hour,
                                'end'   => strtotime("next $end_day", $end_time) + $end_hour,
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
        return $toadd;
    }

    /**
     * Display reservations for an item
     *
     * @param CommonDBTM $item Object for which the reservation tab need to be displayed
     * @param integer $withtemplate
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        if (!Session::haveRight("reservation", READ)) {
            return;
        }

        echo "<div class='mb-3'>";
        ReservationItem::showActivationFormForItem($item);

        $ri = new ReservationItem();
        if (!$ri->getFromDBbyItem($item->getType(), $item->getID())) {
            return;
        }

        // js vars
        $rand   = mt_rand();
        $ID     = $ri->getID();

        echo "<br>";
        echo "<h1>" . __s('Reservations for this item') . "</h1>";
        echo "<div id='reservations_planning_$rand' class='reservations-planning tabbed'></div>";

        $default_date = date('Y-m-d');
        if (isset($_REQUEST['defaultDate'])) {
            $default_date = $_REQUEST['defaultDate'];
        } elseif (isset($_REQUEST['month'], $_REQUEST['year'])) {
            $month = (int) $_REQUEST['month'];
            $year  = (int) $_REQUEST['year'];
            $default_date = sprintf('%04d-%02d-01', $year, $month);
        }
        $default_date = jsescape($default_date);
        $now = date("Y-m-d H:i:s");
        $js = <<<JAVASCRIPT
            $(() => {
                const reservation = new Reservations();
                reservation.init({
                    id: $ID,
                    is_all: false,
                    is_tab: true,
                    rand: $rand,
                    currentv: 'listFull',
                    defaultDate: '$default_date',
                    now: '$now',
                });
                reservation.displayPlanning();
            });
JAVASCRIPT;
        echo Html::scriptBlock($js);
        echo "</div>";
    }

    /**
     * Get reservation data for a user
     * @param int $users_id ID of the user
     * @return array
     */
    public static function getForUser(int $users_id): array
    {
        global $DB;

        $now = $_SESSION["glpi_currenttime"];

        $common_criteria = [
            'SELECT'    => [
                'begin',
                'end',
                'items_id',
                'glpi_reservationitems.entities_id',
                'users_id',
                'glpi_reservations.comment',
                'reservationitems_id',
                'completename',
            ],
            'FROM'      => 'glpi_reservations',
            'LEFT JOIN' => [
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id',
                    ],
                ],
                'glpi_entities' => [
                    'ON' => [
                        'glpi_reservationitems' => 'entities_id',
                        'glpi_entities'         => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'users_id'  => $users_id,
            ],
        ];

        // Print reservation in progress
        $in_progress_criteria = $common_criteria;
        $in_progress_criteria['WHERE']['end'] = ['>', $now];
        $in_progress_criteria['ORDERBY'] = 'begin';
        $iterator = $DB->request($in_progress_criteria);

        $ri = new ReservationItem();

        $fn_get_entry = static function (array $data) use ($ri) {
            $entry = [
                'id' => $data['reservationitems_id'],
                'start_date' => $data['begin'],
                'end_date' => $data['end'],
                'item' => null,
                'entity' => null,
                'by' => $data["users_id"],
                'comments' => $data["comment"],
            ];

            if ($ri->getFromDB($data["reservationitems_id"])) {
                $entry['item']['itemtype'] = $ri->fields['itemtype'];
                $entry['item']['id'] = $ri->fields['items_id'];
                $entry['entity'] = $data['entities_id'];
            }
            return $entry;
        };

        $progress_entries = [];
        foreach ($iterator as $data) {
            $progress_entries[] = $fn_get_entry($data);
        }

        // Print old reservations
        $old_criteria = $common_criteria;
        $old_criteria['WHERE']['end'] = ['<=', $now];
        $old_criteria['ORDERBY'] = 'begin DESC';
        $iterator = $DB->request($old_criteria);

        $old_entries = [];
        foreach ($iterator as $data) {
            $old_entries[] = $fn_get_entry($data);
        }

        return [
            'in_progress' => $progress_entries,
            'old' => $old_entries,
        ];
    }

    public static function showReservationsAsList(array $reservations, string $title): void
    {
        $entity_cache = [];
        $fn_format_entry = static function (array $data, bool $is_old) use (&$entity_cache) {
            global $CFG_GLPI;
            $entry = [
                'itemtype' => ReservationItem::class,
                'id' => $data['id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'item' => '',
                'entity' => '',
                'by' => getUserName($data["by"]),
                'comments' => RichText::getSafeHtml($data["comments"]),
            ];

            $item = null;
            if ($data['item'] !== null) {
                if (($item = getItemForItemtype($data['item']['itemtype'])) && $item->getFromDB($data['item']['id'])) {
                    $entry['item'] = $item->getLink();
                }
            }
            if ($data['entity'] !== null) {
                if (!isset($entity_cache[$data['entity']])) {
                    $entity_cache[$data['entity']] = Dropdown::getDropdownName('glpi_entities', $data['entity']);
                }
                $entry['entity'] = $entity_cache[$data['entity']];
            }

            if (!$is_old) {
                [$annee, $mois] = explode("-", $data["start_date"]);
                $href = htmlescape($CFG_GLPI["root_doc"]) . "/front/reservation.php?reservationitems_id={$data['id']}&month=$mois&year=$annee";
                $entry['planning'] = "<a href='$href' title='" . __s('See planning') . "'>";
                $entry['planning'] .= "<i class='" . htmlescape(Planning::getIcon()) . "'></i>";
                $entry['planning'] .= "<span class='sr-only'>" . __s('See planning') . "</span>";
                $entry['planning'] .= "</a>";
            } elseif ($item instanceof CommonDBTM) {
                $href = htmlescape($item::getFormURLWithID($item->getID()) . "&forcetab=Reservation$1&tab_params[defaultDate]={$data['start_date']}");
                $entry['planning'] = "<a href='$href' title=\"" . __s('See planning') . "\">";
                $entry['planning'] .= "<i class='" . htmlescape(Planning::getIcon()) . "'></i>";
                $entry['planning'] .= "<span class='sr-only'>" . __s('See planning') . "</span>";
            }
            return $entry;
        };

        $entries = [];
        foreach ($reservations as $data) {
            $entries[] = $fn_format_entry($data, false);
        }

        $columns = [
            'start_date' => __('Start date'),
            'end_date'   => __('End date'),
            'item'       => _n('Item', 'Items', 1),
            'entity'     => Entity::getTypeName(1),
            'by'         => __('By'),
            'comments'   => _n('Comment', 'Comments', Session::getPluralNumber()),
            'planning'  => '',
        ];
        $formatters = [
            'start_date' => 'datetime',
            'end_date'   => 'datetime',
            'item' => 'raw_html',
            'planning' => 'raw_html',
            'comments' => 'raw_html',
        ];
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'table_class_style' => 'table-hover mb-3',
            'super_header' => $title,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    /**
     * Display reservations for a user
     *
     * @param integer $ID ID of the user
     * @return void
     **/
    public static function showForUser($ID)
    {
        if (!Session::haveRight("reservation", READ)) {
            return;
        }

        $reservations = self::getForUser($ID);
        self::showReservationsAsList($reservations['in_progress'], __('Current and future reservations'));
        self::showReservationsAsList($reservations['old'], __('Past reservations'));
    }

    /**
     * Get reservable itemtypes from GLPI config, filtering out itemtype with no
     * reservable items
     *
     * @return array
     */
    public static function getReservableItemtypes(): array
    {
        global $CFG_GLPI;

        return array_filter(
            $CFG_GLPI['reservation_types'],
            static fn($type) => ReservationItem::countAvailableItems($type) > 0
        );
    }

    public static function getIcon()
    {
        return "ti ti-calendar-event";
    }

    public static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = false, ?CommonDBTM $checkitem = null)
    {
        global $CFG_GLPI;

        $action_prefix = 'Reservation' . MassiveAction::CLASS_ACTION_SEPARATOR;
        if (in_array($itemtype, $CFG_GLPI["reservation_types"], true)) {
            $show_all = $checkitem === null || $checkitem->isNewItem();
            $reservable = false;
            $available = false;
            if (!$show_all) {
                if ($checkitem->isTemplate()) {
                    return;
                }
                $ri = new ReservationItem();
                $reservable = $ri->getFromDBbyItem($checkitem::class, $checkitem->getID());
                if ($reservable) {
                    $available = (bool) $ri->fields['is_active'];
                }
            }
            if ($show_all || !$reservable) {
                $actions[$action_prefix . 'enable'] = "<i class='" . htmlescape(self::getIcon()) . "'></i>" . __s('Authorize reservations');
            }
            if ($show_all || $reservable) {
                $actions[$action_prefix . 'disable'] = "<i class='ti ti-calendar-off'></i>" . __s('Prohibit reservations');
            }
            if ($show_all || ($reservable && !$available)) {
                $actions[$action_prefix . 'available'] = "<i class='" . htmlescape(self::getIcon()) . "'></i>" . __s('Make available for reservations');
            }
            if ($show_all || $available) {
                $actions[$action_prefix . 'unavailable'] = "<i class='ti ti-calendar-off'></i>" . __s('Make unavailable for reservations');
            }
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'enable':
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='"
                    . __s('Authorize reservations') . "'>";
                return true;
            case 'disable':
                echo '<div class="alert alert-warning">';
                echo __s('Are you sure you want to return this non-reservable item?');
                echo '<br>';
                echo "<span class='fw-bold'>" . __s('That will remove all the reservations in progress.') . "</span>";
                echo '</div>';
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='"
                    . __s('Prohibit reservations') . "'>";
                return true;
            case 'available':
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='"
                    . __s('Make available for reservations') . "'>";
                return true;
            case 'unavailable':
                echo "<br><br><input type='submit' name='massiveaction' class='btn btn-primary' value='"
                    . __s('Make unavailable for reservations') . "'>";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        if (!ReservationItem::canUpdate()) {
            return;
        }
        $reservation_item = new ReservationItem();

        switch ($ma->getAction()) {
            case 'enable':
                foreach ($ids as $id) {
                    if ($reservation_item->getFromDBbyItem($item::getType(), $id)) {
                        // Treat as OK
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $result = $reservation_item->add([
                            'itemtype' => $item->getType(),
                            'items_id' => $id,
                            'is_active' => 1,
                        ]);
                        $ma->itemDone($item->getType(), $id, $result ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO);
                    }
                }
                break;
            case 'disable':
                foreach ($ids as $id) {
                    if ($reservation_item->getFromDBbyItem($item::getType(), $id)) {
                        $result = $reservation_item->delete(['id' => $reservation_item->getID()]);
                        $ma->itemDone($item->getType(), $id, $result ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }
                break;
            case 'available':
                foreach ($ids as $id) {
                    if ($reservation_item->getFromDBbyItem($item::getType(), $id)) {
                        $result = $reservation_item->update([
                            'id' => $reservation_item->getID(),
                            'is_active' => 1,
                        ]);
                        $ma->itemDone($item->getType(), $id, $result ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }
                break;
            case 'unavailable':
                foreach ($ids as $id) {
                    if ($reservation_item->getFromDBbyItem($item::getType(), $id)) {
                        $result = $reservation_item->update([
                            'id' => $reservation_item->getID(),
                            'is_active' => 0,
                        ]);
                        $ma->itemDone($item->getType(), $id, $result ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    }
                }
                break;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }
}
