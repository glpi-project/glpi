<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * ReservationItem Class
 **/
class ReservationItem extends CommonDBChild
{
   /// From CommonDBChild
    public static $itemtype          = 'itemtype';
    public static $items_id          = 'items_id';

    public static $checkParentRights = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static $rightname                = 'reservation';

    const RESERVEANITEM              = 1024;

    public $get_item_to_display_tab  = false;
    public $showdebug                = false;

    public $taborientation           = 'horizontal';


    /**
     * @since 0.85
     **/
    public static function canView()
    {
        return Session::haveRightsOr(self::$rightname, [READ, self::RESERVEANITEM]);
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Reservable item', 'Reservable items', $nb);
    }


    /**
     * @see CommonGLPI::getMenuName()
     *
     * @since 0.85
     **/
    public static function getMenuName()
    {
        return Reservation::getTypeName(Session::getPluralNumber());
    }


    /**
     * @see CommonGLPI::getForbiddenActionsForMenu()
     *
     * @since 0.85
     **/
    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }


    /**
     * @see CommonGLPI::getAdditionalMenuLinks()
     *
     * @since 0.85
     **/
    public static function getAdditionalMenuLinks()
    {

        if (static::canView()) {
            return ['showall' => Reservation::getSearchURL(false)];
        }
        return false;
    }


    public static function getMenuContent()
    {
        $menu = parent::getMenuContent();
        if (isset($menu['links']['lists'])) {
            unset($menu['links']['lists']);
        }

        return $menu;
    }


   // From CommonDBTM
    /**
     * Retrieve an item from the database for a specific item
     *
     * @param $itemtype   type of the item
     * @param $ID         ID of the item
     *
     * @return true if succeed else false
     **/
    public function getFromDBbyItem($itemtype, $ID)
    {

        return $this->getFromDBByCrit([
            $this->getTable() . '.itemtype'  => $itemtype,
            $this->getTable() . '.items_id'  => $ID
        ]);
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Reservation::class,
            ]
        );

       // Alert does not extends CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete($this->getType(), $this->fields['id']);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => 'reservation_types',
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'addobjectparams'    => [
                'forcetab'           => 'Reservation$1'
            ]
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'reservation_types',
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => '_virtual',
            'name'               => __('Planning'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
            'additionalfields'   => ['is_active']
        ];

        $loc = Location::rawSearchOptionsToAdd();
       // Force massive actions to false
        foreach ($loc as &$val) {
            $val['massiveaction'] = false;
        }
        $tab = array_merge($tab, $loc);

        $tab[] = [
            'id'                 => '6',
            'table'              => 'reservation_types',
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => 'reservation_types',
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => 'reservation_types',
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge of the hardware'),
            'datatype'           => 'dropdown',
            'right'              => 'interface',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    /**
     * @param $item   CommonDBTM object
     **/
    public static function showActivationFormForItem(CommonDBTM $item)
    {

        if (!self::canUpdate()) {
            return false;
        }
        if ($item->getID()) {
           // Recursive type case => need entity right
            if ($item->isRecursive()) {
                if (!Session::haveAccessToEntity($item->fields["entities_id"])) {
                    return false;
                }
            }
        } else {
            return false;
        }

        $ri = new self();

        echo "<div>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>" . __('Reserve an item') . "</th></tr>";
        echo "<tr class='tab_bg_1'>";
        if ($ri->getFromDBbyItem($item->getType(), $item->getID())) {
            echo "<td class='center'>";
           //Switch reservation state

            if ($ri->fields["is_active"]) {
                Html::showSimpleForm(
                    static::getFormURL(),
                    'update',
                    "<i class='fas fa-toggle-on'></i>&nbsp;" . __('Make unavailable'),
                    [
                        'id'        => $ri->fields['id'],
                        'is_active' => 0
                    ]
                );
            } else {
                Html::showSimpleForm(
                    static::getFormURL(),
                    'update',
                    "<i class='fas fa-toggle-off'></i>&nbsp;" . __('Make available'),
                    [
                        'id'        => $ri->fields['id'],
                        'is_active' => 1
                    ]
                );
            }

            echo '</td><td>';
            Html::showSimpleForm(
                static::getFormURL(),
                'purge',
                "<i class='fas fa-ban'></i>&nbsp;" . __('Prohibit reservations'),
                ['id' => $ri->fields['id']],
                '',
                '',
                [__('Are you sure you want to return this non-reservable item?'),
                    __('That will remove all the reservations in progress.')
                ]
            );

            echo "</td>";
        } else {
            echo "<td class='center'>";
            Html::showSimpleForm(
                static::getFormURL(),
                'add',
                "<i class='fas fa-check'></i>&nbsp;" . __('Authorize reservations'),
                ['items_id'     => $item->getID(),
                    'itemtype'     => $item->getType(),
                    'entities_id'  => $item->getEntityID(),
                    'is_recursive' => $item->isRecursive(),
                ]
            );
            echo "</td>";
        }
        echo "</tr></table>";
        echo "</div>";
    }


    public function showForm($ID, array $options = [])
    {

        if (!self::canView()) {
            return false;
        }

        $r = new self();

        if ($r->getFromDB($ID)) {
            $type = $r->fields["itemtype"];
            $name = NOT_AVAILABLE;
            if ($item = getItemForItemtype($r->fields["itemtype"])) {
                $type = $item->getTypeName();
                if ($item->getFromDB($r->fields["items_id"])) {
                    $name = $item->getName();
                }
            }

            echo "<div class='center'><form method='post' name=form action='" . $this->getFormURL() . "'>";
            echo "<input type='hidden' name='id' value='$ID'>";
            echo "<table class='tab_cadre'>";
            echo "<tr><th colspan='2'>" . __s('Modify the comment') . "</th></tr>";

           // Ajouter le nom du materiel
            echo "<tr class='tab_bg_1'><td>" . _n('Item', 'Items', 1) . "</td>";
            echo "<td class='b'>" . sprintf(__('%1$s - %2$s'), $type, $name) . "</td></tr>\n";

            echo "<tr class='tab_bg_1'><td>" . __('Comments') . "</td>";
            echo "<td><textarea name='comment' cols='30' rows='10' >" . $r->fields["comment"];
            echo "</textarea></td></tr>\n";

            echo "<tr class='tab_bg_2'><td colspan='2' class='top center'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</td></tr>\n";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
            return true;
        }
        return false;
    }


    public static function showListSimple()
    {
        global $DB, $CFG_GLPI;

        if (!Session::haveRight(self::$rightname, self::RESERVEANITEM)) {
            return false;
        }

        $ok         = false;
        $showentity = Session::isMultiEntitiesMode();
        $values     = [];

        if (isset($_SESSION['glpi_saved']['ReservationItem'])) {
            $_POST = $_SESSION['glpi_saved']['ReservationItem'];
        }

        if (isset($_POST['reserve'])) {
            echo "<div id='viewresasearch'  class='center'>";
            Toolbox::manageBeginAndEndPlanDates($_POST['reserve']);
            echo "<div id='nosearch' class='center firstbloc'>" .
              "<a href=\"" . $CFG_GLPI['root_doc'] . "/front/reservationitem.php\">";
            echo __('See all reservable items') . "</a></div>\n";
        } else {
            echo "<div id='makesearch' class='center firstbloc'>" .
              "<a class='btn btn-secondary' href='reservation.php?reservationitems_id=0'>
                  <i class='far fa-calendar'></i>&nbsp;
                  " . __("View calendar for all items") . "
               </a>
               <a class='btn btn-secondary mw-100 d-inline-block text-truncate' onClick=\"javascript:showHideDiv('viewresasearch','','','');" .
                "showHideDiv('makesearch','','','')\">
               <i class='fas fa-search'></i>&nbsp;";
            echo __('Find a free item in a specific period') . "</a></div>";

            echo "<div id='viewresasearch' style=\"display:none;\" class='center'>";
            $begin_time                 = time();
            $begin_time                -= ($begin_time % HOUR_TIMESTAMP);
            $_POST['reserve']["begin"]  = date("Y-m-d H:i:s", $begin_time);
            $_POST['reserve']["end"]    = date("Y-m-d H:i:s", $begin_time + HOUR_TIMESTAMP);
            $_POST['reservation_types'] = '';
        }
        echo "<form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
        echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
        echo "<th colspan='3'>" . __('Find a free item in a specific period') . "</th></tr>";

        echo "<tr class='tab_bg_2'><td>" . __('Start date') . "</td><td class='d-flex'>";
        Html::showDateTimeField("reserve[begin]", ['value'      =>  $_POST['reserve']["begin"],
            'maybeempty' => false
        ]);
        echo "</td><td rowspan='3'>";
        echo "<input type='submit' class='btn btn-primary' name='submit' value=\"" . _sx('button', 'Search') . "\">";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td>" . __('Duration') . "</td><td>";
        $default_delay = floor((strtotime($_POST['reserve']["end"]) - strtotime($_POST['reserve']["begin"]))
                             / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP)
                       * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
        $rand = Dropdown::showTimeStamp("reserve[_duration]", [
            'min'        => 0,
            'max'        => 48 * HOUR_TIMESTAMP,
            'value'      => $default_delay,
            'emptylabel' => __('Specify an end date')
        ]);
        echo "<br><div id='date_end$rand'></div>";
        $params = ['duration'     => '__VALUE__',
            'end'          => $_POST['reserve']["end"],
            'name'         => "reserve[end]"
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_reserve[_duration]$rand",
            "date_end$rand",
            $CFG_GLPI["root_doc"] . "/ajax/planningend.php",
            $params
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td>" . __('Item type') . "</td><td>";

        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_reservationitems',
            'WHERE'           => [
                'is_active' => 1
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true)
        ]);

        foreach ($iterator as $data) {
            if (is_a($data['itemtype'], CommonDBTM::class, true) && $data['itemtype']::canView()) {
                $values[$data['itemtype']] = $data['itemtype']::getTypeName();
            }
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_peripheraltypes.name',
                'glpi_peripheraltypes.id'
            ],
            'FROM'      => 'glpi_peripheraltypes',
            'LEFT JOIN' => [
                'glpi_peripherals'      => [
                    'ON' => [
                        'glpi_peripheraltypes'  => 'id',
                        'glpi_peripherals'      => 'peripheraltypes_id'
                    ]
                ],
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'items_id',
                        'glpi_peripherals'      => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'itemtype'           => 'Peripheral',
                'is_active'          => 1,
                'peripheraltypes_id' => ['>', 0]
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true),
            'ORDERBY'   => 'glpi_peripheraltypes.name'
        ]);

        foreach ($iterator as $ptype) {
            $id = $ptype['id'];
            $values["Peripheral#$id"] = $ptype['name'];
        }

        Dropdown::showFromArray("reservation_types", $values, [
            'class'               => "form-select",
            'value'               => $_POST['reservation_types'],
            'display_emptychoice' => true,
        ]);

        echo "</td></tr>";

        // Location dropdown
        $locrand = mt_rand();
        echo "<tr class='tab_bg_1'><td><label for='dropdown_locations_id$locrand'>" . __('Item location') . "</label></td><td>";
        Location::dropdown([
            // Fill with submitted data if any, otherwise use user's location
            'value'  => (int)($_POST['locations_id'] ?? User::getById(Session::getLoginUserID())->fields['locations_id'] ?? 0),
            'rand'   => $locrand,
            'entity' => $_SESSION['glpiactiveentities'],
        ]);

        echo "</td></tr>";
        echo "</table>";
        Html::closeForm();
        echo "</div>";

       // GET method passed to form creation
        echo "<div id='nosearch' class='card'>";
        echo "<form name='form' method='GET' action='" . Reservation::getFormURL() . "'>";
        echo "<div class='table-responsive'>";
        echo "<table class='card-table table table-hover'>";
        echo "<tr>";
        echo "<th style='width: 30px;'>" . Html::getCheckAllAsCheckbox('nosearch') . "</th>";
        echo "<th>" . self::getTypeName(Session::getPluralNumber()) . "</th>";
        echo "<th>" . Location::getTypeName(1) . "</th>";
        echo "<th>" . _n('Comment', 'Comments', 1) . "</th>";
        if ($showentity) {
            echo "<th>" . Entity::getTypeName(1) . "</th>";
        }
        echo "<th style='width: 50px;'>" . __("Booking calendar") . "</th>";
        echo "</tr>";

        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);
            $itemname  = $item->getNameField();

            $otherserial = new \QueryExpression($DB->quote('') . ' AS ' . $DB->quoteName('otherserial'));
            if ($item->isField('otherserial')) {
                $otherserial = "$itemtable.otherserial AS otherserial";
            }
            $criteria = [
                'SELECT' => [
                    'glpi_reservationitems.id',
                    'glpi_reservationitems.comment',
                    "$itemtable.$itemname AS name",
                    "$itemtable.entities_id AS entities_id",
                    $otherserial,
                    'glpi_locations.id AS location',
                    'glpi_reservationitems.items_id AS items_id'
                ],
                'FROM'   => self::getTable(),
                'INNER JOIN'   => [
                    $itemtable  => [
                        'ON'  => [
                            'glpi_reservationitems' => 'items_id',
                            $itemtable              => 'id', [
                                'AND' => [
                                    'glpi_reservationitems.itemtype' => $itemtype
                                ]
                            ]
                        ]
                    ]
                ],
                'LEFT JOIN'    =>  [
                    'glpi_locations'  => [
                        'ON'  => [
                            $itemtable        => 'locations_id',
                            'glpi_locations'  => 'id'
                        ]
                    ]
                ],
                'WHERE'        => [
                    'glpi_reservationitems.is_active'   => 1,
                    "$itemtable.is_deleted"             => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', $_SESSION['glpiactiveentities'], $item->maybeRecursive()),
                'ORDERBY'      => [
                    "$itemtable.entities_id",
                    "$itemtable.$itemname"
                ]
            ];

            $begin = $_POST['reserve']["begin"];
            $end   = $_POST['reserve']["end"];
            if (isset($_POST['submit']) && isset($begin) && isset($end)) {
                $criteria['LEFT JOIN']['glpi_reservations'] = [
                    'ON'  => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id', [
                            'AND' => [
                                'glpi_reservations.end'    => ['>', $begin],
                                'glpi_reservations.begin'  => ['<', $end]
                            ]
                        ]
                    ]
                ];
                $criteria['WHERE'][] = ['glpi_reservations.id' => null];
            }
            if (isset($_POST["reservation_types"]) && !empty($_POST["reservation_types"])) {
                $tmp = explode('#', $_POST["reservation_types"]);
                $criteria['WHERE'][] = ['glpi_reservationitems.itemtype' => $tmp[0]];
                if (
                    isset($tmp[1]) && ($tmp[0] == 'Peripheral')
                    && ($itemtype == 'Peripheral')
                ) {
                    $criteria['LEFT JOIN']['glpi_peripheraltypes'] = [
                        'ON' => [
                            'glpi_peripherals'      => 'peripheraltypes_id',
                            'glpi_peripheraltypes'  => 'id'
                        ]
                    ];
                    $criteria['WHERE'][] = ["$itemtable.peripheraltypes_id" => $tmp[1]];
                }
            }

            // Filter locations if location was provided/submitted
            if ((int)($_POST['locations_id'] ?? 0) > 0) {
                $criteria['WHERE'][] = [
                    'glpi_locations.id' => getSonsOf('glpi_locations', (int) $_POST['locations_id']),
                ];
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $row) {
                echo "<tr><td>";
                echo Html::getCheckbox([
                    'name'  => "item[" . $row["id"] . "]",
                    'value' => $row["id"],
                    'zero_on_empty' => false,
                ]);
                echo "</td>";
                $typename = $item->getTypeName();
                if ($itemtype == 'Peripheral') {
                     $item->getFromDB($row['items_id']);
                    if (
                        isset($item->fields["peripheraltypes_id"])
                         && ($item->fields["peripheraltypes_id"] != 0)
                    ) {
                        $typename = Dropdown::getDropdownName(
                            "glpi_peripheraltypes",
                            $item->fields["peripheraltypes_id"]
                        );
                    }
                }
                $item_link = sprintf(__('%1$s - %2$s'), $typename, $row["name"]);
                if ($itemtype::canView()) {
                    $item_link = "<a href='" . $itemtype::getFormURLWithId($row['items_id']) . "&forcetab=Reservation$1'>" .
                        $item_link .
                        "</a>";
                }
                echo "<td>$item_link</td>";
                echo "<td>" . Dropdown::getDropdownName("glpi_locations", $row["location"]) . "</td>";
                echo "<td>" . nl2br(($row["comment"] ?? "")) . "</td>";
                if ($showentity) {
                    echo "<td>" . Dropdown::getDropdownName("glpi_entities", $row["entities_id"]) .
                     "</td>";
                }
                echo "<td class='center'><a href='reservation.php?reservationitems_id=" . $row['id'] . "'>
                     <i class='far fa-calendar-plus fa-2x pointer' title=\"" . __s("Reserve this item") . "\"></i>
                  </a></td>";
                echo "</tr>";
                $ok = true;
            }
        }
        if ($ok) {
            echo "<tr class='tab_bg_1'>";
            echo "<th><i class='fas fa-level-up-alt fa-flip-horizontal fa-lg mx-2'></i></th>";
            echo "<th colspan='" . ($showentity ? "5" : "4") . "'>";
            if (isset($_POST['reserve'])) {
                echo Html::hidden('begin', ['value' => $_POST['reserve']["begin"]]);
                echo Html::hidden('end', ['value'   => $_POST['reserve']["end"]]);
            }
            echo Html::submit("<i class='fas fa-lg fa-calendar-plus'></i>&nbsp;" . _sx('button', 'Book'));
            echo "</th></tr>";
        }
        echo "</table>";
        echo "</div>";
        echo "<input type='hidden' name='id' value=''>";
        echo "</form>";// No CSRF token needed
        echo "</div>";
    }


    /**
     * @param $name
     *
     * @return array
     **/
    public static function cronInfo($name)
    {
        return ['description' => __('Alerts on reservations')];
    }


    /**
     * Cron action on reservation : alert on end of reservations
     *
     * @param $task to log, if NULL use display (default NULL)
     *
     * @return 0 : nothing to do 1 : done with success
     **/
    public static function cronReservation($task = null)
    {
        global $DB, $CFG_GLPI;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0;
        }

        $message        = [];
        $cron_status    = 0;
        $items_infos    = [];
        $items_messages = [];

        foreach (Entity::getEntitiesToNotify('use_reservations_alert') as $entity => $value) {
            $secs = $value * HOUR_TIMESTAMP;

           // Reservation already begin and reservation ended in $value hours
            $criteria = [
                'SELECT' => [
                    'glpi_reservationitems.*',
                    'glpi_reservations.end AS end',
                    'glpi_reservations.id AS resaid'
                ],
                'FROM'   => 'glpi_reservations',
                'LEFT JOIN' => [
                    'glpi_alerts'  => [
                        'ON'  => [
                            'glpi_reservations'  => 'id',
                            'glpi_alerts'        => 'items_id', [
                                'AND' => [
                                    'glpi_alerts.itemtype'  => 'Reservation',
                                    'glpi_alerts.type'      => Alert::END
                                ]
                            ]
                        ]
                    ],
                    'glpi_reservationitems' => [
                        'ON'  => [
                            'glpi_reservations'     => 'reservationitems_id',
                            'glpi_reservationitems' => 'id'
                        ]
                    ]
                ],
                'WHERE'     => [
                    'glpi_reservationitems.entities_id' => $entity,
                    new QueryExpression('(UNIX_TIMESTAMP(' . $DB->quoteName('glpi_reservations.end') . ') - ' . $secs . ') < UNIX_TIMESTAMP()'),
                    'glpi_reservations.begin'  => ['<', new \QueryExpression('NOW()')],
                    'glpi_alerts.date'         => null
                ]
            ];
            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                if ($item_resa = getItemForItemtype($data['itemtype'])) {
                    if ($item_resa->getFromDB($data["items_id"])) {
                        $data['item_name']                     = $item_resa->getName();
                        $data['entity']                        = $entity;
                        $items_infos[$entity][$data['resaid']] = $data;

                        if (!isset($items_messages[$entity])) {
                            $items_messages[$entity] = __('Device reservations expiring today') . "<br>";
                        }
                        $items_messages[$entity] .= sprintf(
                            __('%1$s - %2$s'),
                            $item_resa->getTypeName(),
                            $item_resa->getName()
                        ) . "<br>";
                    }
                }
            }
        }

        foreach ($items_infos as $entity => $items) {
            $resitem = new self();
            if (
                NotificationEvent::raiseEvent(
                    "alert",
                    new Reservation(),
                    ['entities_id' => $entity,
                        'items'       => $items
                    ]
                )
            ) {
                $message     = $items_messages[$entity];
                $cron_status = 1;
                if ($task) {
                    $task->addVolume(1);
                    $task->log(sprintf(
                        __('%1$s: %2$s') . "\n",
                        Dropdown::getDropdownName("glpi_entities", $entity),
                        $message
                    ));
                } else {
                   //TRANS: %1$s is a name, %2$s is text of message
                    Session::addMessageAfterRedirect(sprintf(
                        __('%1$s: %2$s'),
                        Dropdown::getDropdownName(
                            "glpi_entities",
                            $entity
                        ),
                        $message
                    ));
                }

                $alert             = new Alert();
                $input["itemtype"] = 'Reservation';
                $input["type"]     = Alert::END;
                foreach ($items as $resaid => $item) {
                    $input["items_id"] = $resaid;
                    $alert->add($input);
                    unset($alert->fields['id']);
                }
            } else {
                $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
               //TRANS: %s is entity name
                $msg = sprintf(__('%1$s: %2$s'), $entityname, __('Send reservation alert failed'));
                if ($task) {
                    $task->log($msg);
                } else {
                    Session::addMessageAfterRedirect($msg, false, ERROR);
                }
            }
        }
        return $cron_status;
    }


    /**
     * Display debug information for reservation of current object
     **/
    public function showDebugResa()
    {

        $resa                                = new Reservation();
        $resa->fields['id']                  = '1';
        $resa->fields['reservationitems_id'] = $this->getField('id');
        $resa->fields['begin']               = $_SESSION['glpi_currenttime'];
        $resa->fields['end']                 = $_SESSION['glpi_currenttime'];
        $resa->fields['users_id']            = Session::getLoginUserID();
        $resa->fields['comment']             = '';

        NotificationEvent::debugEvent($resa);
    }


    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {
        if ($interface == 'central') {
            $values = parent::getRights();
        } else {
            $values = [READ => __('Read')];
        }
        $values[self::RESERVEANITEM] = __('Make a reservation');

        return $values;
    }


    /**
     * @see CommonGLPI::defineTabs()
     *
     * @since 0.85
     **/
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $ong['no_all_tab'] = true;
        return $ong;
    }


    /**
     * @see CommonGLPI::getTabNameForItem()
     *
     * @since 0.85
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            $tabs = [];
            if (Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
                $tabs[1] = Reservation::getTypeName(1);
            }
            if (
                (Session::getCurrentInterface() == "central")
                && Session::haveRight("reservation", READ)
            ) {
                $tabs[2] = __('Administration');
            }
            return $tabs;
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

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showListSimple();
                    break;

                case 2:
                    Search::show('ReservationItem');
                    break;
            }
        }
        return true;
    }

    /**
     * @see CommonDBTM::isNewItem()
     *
     * @since 0.85
     **/
    public function isNewItem()
    {
        return false;
    }


    public static function getIcon()
    {
        return Reservation::getIcon();
    }

    /**
     * Display a dropdown with only reservable items
     *
     * @param array $post with these options
     * - idtable: itemtype of items to show
     * - name: input name
     *
     * @return void
     */
    public static function ajaxDropdown(array $post)
    {
        global $DB;

        if ($post['idtable'] && class_exists($post['idtable'])) {
            $itemtype = $post['idtable'];
            $itemtype_obj = new $itemtype();

            $item_table = $itemtype::getTable();
            $resi_table = ReservationItem::getTable();

            $criteria = [
                'SELECT' => [
                    "$resi_table.id",
                    "$item_table.name"
                ],
                'FROM' => $item_table,
                'INNER JOIN' => [
                    $resi_table => [
                        'ON' => [
                            $resi_table => 'items_id',
                            $item_table => 'id',
                            ['AND' => ["$resi_table.itemtype" => $itemtype]],
                        ]
                    ]
                ],
                'WHERE' => [
                    "$resi_table.is_active"   => 1,
                    "$item_table.is_deleted"  => 0,
                ]
            ];

            if ($itemtype_obj->maybeTemplate()) {
                $criteria['WHERE']["$item_table.is_template"] = 0;
            }

            $result = $DB->request($criteria);

            if ($result->count() == 0) {
                 echo __('No reservable item!');
            } else {
                $items = [];
                foreach ($result as $row) {
                    $name = $row['name'];
                    if (empty($name)) {
                        $name = $row['id'];
                    }
                    $items[$row['id']] = $name;
                }
                Dropdown::showFromArray($post['name'], $items);
            }
        }
    }
}
