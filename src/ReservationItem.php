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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\RichText\RichText;

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

    public const RESERVEANITEM              = 1024;

    public $get_item_to_display_tab  = false;
    public $showdebug                = false;

    public $taborientation           = 'horizontal';

    public static function canView(): bool
    {
        return Session::haveRightsOr(self::$rightname, [READ, self::RESERVEANITEM]);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Reservable item', 'Reservable items', $nb);
    }

    public static function getMenuName()
    {
        return Reservation::getTypeName(Session::getPluralNumber());
    }

    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }

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

    /**
     * Retrieve an item from the database for a specific item
     *
     * @param class-string<CommonDBTM> $itemtype Type of the item
     * @param int $ID ID of the item
     *
     * @return boolean true if succeed else false
     **/
    public function getFromDBbyItem($itemtype, $ID)
    {
        return $this->getFromDBByCrit([
            static::getTable() . '.itemtype'  => $itemtype,
            static::getTable() . '.items_id'  => $ID,
        ]);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Reservation::class,
            ]
        );

        // Alert does not extend CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete(static::class, $this->fields['id']);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => 'reservation_types',
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'addobjectparams'    => [
                'forcetab'           => 'Reservation$1',
            ],
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'reservation_types',
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => '_virtual',
            'name'               => __('Planning'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
            'additionalfields'   => ['is_active'],
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
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => 'reservation_types',
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => 'reservation_types',
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'interface',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        return [
            [
                'id'                 => '81',
                'table'              => static::getTable(),
                'name'               => __('Reservable'),
                'field'              => 'is_active',
                'joinparams'         => [
                    'jointype' => 'itemtype_item',
                ],
                'datatype'           => 'bool',
                'massiveaction'      => false,
            ],
        ];
    }

    /**
     * @param CommonDBTM $item
     * @return false|void
     */
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
        $reservable = $ri->getFromDBbyItem($item::class, $item->getID());
        $twig_params = [
            'reservable' => $reservable,
            'toggle_state_label' => ($ri->fields["is_active"] ?? 0)
                ? __('Make unavailable')
                : __('Make available'),
            'toggle_state' => $ri->fields["is_active"] ?? 0,
            'toggle_reservable_label' => $reservable
                ? __('Prohibit reservations')
                : __('Authorize reservations'),
            'toggle_reservable' => $reservable,
            'item' => $item,
            'id' => $ri->getID(),
            'purge_warning' => __('Are you sure you want to return this non-reservable item?')
                . ' ' . __('That will remove all the reservations in progress.'),
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="firstbloc">
            <form id="reservation_actions" class="d-flex " action="{{ 'ReservationItem'|itemtype_form_path }}" method="post">
                <input type="hidden" name="items_id" value="{{ item.getID() }}">
                <input type="hidden" name="itemtype" value="{{ get_class(item) }}">
                <input type="hidden" name="entities_id" value="{{ item.getEntityID() }}">
                <input type="hidden" name="is_recursive" value="{{ item.isRecursive() ? 1 : 0 }}">
                <input type="hidden" name="is_active" value="{{ reservable ? (toggle_state ? 0 : 1) : 1 }}">
                {% if reservable %}
                    <button name="update" class="btn btn-{{ toggle_state ? 'danger' : 'primary' }} mx-1">
                        <i class="{{ toggle_state ? 'ti ti-toggle-right' : 'ti ti-toggle-left' }} me-2"></i>
                        {{ toggle_state_label }}
                    </button>
                    <input type="hidden" name="id" value="{{ id }}">
                    <script>
                        $(() => $('#reservation_actions button[name="purge"]').on('click', () => confirm('{{ purge_warning }}')));
                    </script>
                {% endif %}
                <button name="{{ toggle_reservable ? 'purge' : 'add' }}" class="btn btn-{{ toggle_reservable ? 'danger' : 'primary' }} mx-1">
                    <i class="{{ toggle_reservable ? 'ti ti-ban' : 'ti ti-check' }} me-2"></i>
                    {{ toggle_reservable_label }}
                </button>
                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
            </form>
            </div>
TWIG, $twig_params);
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
                $type = $item::getTypeName(1);
                if ($item->getFromDB($r->fields["items_id"])) {
                    $name = $item->getName();
                }
            }

            $options['candel'] = false;
            $this->initForm($ID, $options);
            TemplateRenderer::getInstance()->display('components/form/reservationitem_comment.html.twig', [
                'item'      => $r,
                'type_name' => sprintf(__('%1$s - %2$s'), $type, $name),
                'params'    => $options,
            ]);
            return true;
        }
        return false;
    }

    public static function showListSimple()
    {
        global $CFG_GLPI, $DB;

        if (!Session::haveRightsOr(self::$rightname, [READ, self::RESERVEANITEM])) {
            return false;
        }

        $ok         = false;
        $showentity = Session::isMultiEntitiesMode();
        $reservation_types     = [];

        if (isset($_SESSION['glpi_saved']['ReservationItem'])) {
            $_POST = $_SESSION['glpi_saved']['ReservationItem'];
        }

        $reserve = isset($_POST['reserve']);
        if ($reserve) {
            Toolbox::manageBeginAndEndPlanDates($_POST['reserve']);
        } else {
            $begin_time                 = time();
            $begin_time                -= ($begin_time % HOUR_TIMESTAMP);
            $_POST['reserve']["begin"]  = date("Y-m-d H:i:s", $begin_time);
            $_POST['reserve']["end"]    = date("Y-m-d H:i:s", $begin_time + HOUR_TIMESTAMP);
            $_POST['reservation_types'] = '';
        }

        $twig_params = [
            'reserve' => $reserve,
            'view_calendar_label' => __("View calendar for all items"),
            'find_free_item_label' => __('Find a free item in a specific period'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% if not reserve %}
                <div id="makesearch" class="text-center mb-3">
                    <a class="btn btn-secondary" href="{{ path('front/reservation.php?reservationitems_id=0') }}">
                        <i class="{{ 'Planning'|itemtype_icon }} me-2"></i>{{ view_calendar_label }}
                    </a>
                    <button type="button" class="btn btn-secondary mw-100 d-inline-block text-truncate" onClick="$('#viewresasearch').toggleClass('d-none');$('#makesearch').toggleClass('d-none')">
                        <i class="ti ti-search me-2"></i>{{ find_free_item_label }}
                    </button>
                </div>
                <div id="viewresasearch" class="d-none text-center">
            {% endif %}
TWIG, $twig_params);

        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_reservationitems',
            'WHERE'           => [
                'is_active' => 1,
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true),
        ]);

        foreach ($iterator as $data) {
            /** @var array{itemtype: string} $data */
            if (is_a($data['itemtype'], CommonDBTM::class, true)) {
                $reservation_types[$data['itemtype']] = $data['itemtype']::getTypeName();
            }
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_peripheraltypes.name',
                'glpi_peripheraltypes.id',
            ],
            'FROM'      => 'glpi_peripheraltypes',
            'LEFT JOIN' => [
                'glpi_peripherals'      => [
                    'ON' => [
                        'glpi_peripheraltypes'  => 'id',
                        'glpi_peripherals'      => 'peripheraltypes_id',
                    ],
                ],
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'items_id',
                        'glpi_peripherals'      => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'itemtype'           => 'Peripheral',
                'is_active'          => 1,
                'peripheraltypes_id' => ['>', 0],
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true),
            'ORDERBY'   => 'glpi_peripheraltypes.name',
        ]);

        foreach ($iterator as $ptype) {
            $id = $ptype['id'];
            $reservation_types["Peripheral#$id"] = $ptype['name'];
        }

        TemplateRenderer::getInstance()->display('pages/tools/find_available_reservation.html.twig', [
            'reservation_types' => $reservation_types,
            'default_location' => (int) ($_POST['locations_id'] ?? User::getById(Session::getLoginUserID())->fields['locations_id'] ?? 0),
        ]);
        echo "</div>";

        // GET method passed to form creation
        echo "<div id='nosearch' class='card'>";
        echo "<form name='form' method='GET' action='" . htmlescape(Reservation::getFormURL()) . "'>";

        $entries = [];
        $location_cache = [];
        $entity_cache = [];
        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);
            $itemname  = $item::getNameField();

            $otherserial = new QueryExpression($DB->quote('') . ' AS ' . $DB::quoteName('otherserial'));
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
                    'glpi_reservationitems.items_id AS items_id',
                ],
                'FROM'   => self::getTable(),
                'INNER JOIN'   => [
                    $itemtable  => [
                        'ON'  => [
                            'glpi_reservationitems' => 'items_id',
                            $itemtable              => 'id', [
                                'AND' => [
                                    'glpi_reservationitems.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'LEFT JOIN'    =>  [
                    'glpi_locations'  => [
                        'ON'  => [
                            $itemtable        => 'locations_id',
                            'glpi_locations'  => 'id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_reservationitems.is_active'   => 1,
                    "$itemtable.is_deleted"             => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', $_SESSION['glpiactiveentities'], $item->maybeRecursive()),
                'ORDERBY'      => [
                    "$itemtable.entities_id",
                    "$itemtable.$itemname",
                ],
            ];

            $begin = $_POST['reserve']["begin"];
            $end   = $_POST['reserve']["end"];
            if (isset($_POST['submit'], $begin, $end)) {
                $criteria['LEFT JOIN']['glpi_reservations'] = [
                    'ON'  => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id', [
                            'AND' => [
                                'glpi_reservations.end'    => ['>', $begin],
                                'glpi_reservations.begin'  => ['<', $end],
                            ],
                        ],
                    ],
                ];
                $criteria['WHERE'][] = ['glpi_reservations.id' => null];
            }
            if (!empty($_POST["reservation_types"])) {
                $tmp = explode('#', $_POST["reservation_types"]);
                $criteria['WHERE'][] = ['glpi_reservationitems.itemtype' => $tmp[0]];
                if (
                    isset($tmp[1]) && ($tmp[0] === Peripheral::class)
                    && ($itemtype === Peripheral::class)
                ) {
                    $criteria['LEFT JOIN']['glpi_peripheraltypes'] = [
                        'ON' => [
                            'glpi_peripherals'      => 'peripheraltypes_id',
                            'glpi_peripheraltypes'  => 'id',
                        ],
                    ];
                    $criteria['WHERE'][] = ["$itemtable.peripheraltypes_id" => $tmp[1]];
                }
            }

            // Filter locations if location was provided/submitted
            if ((int) ($_POST['locations_id'] ?? 0) > 0) {
                $criteria['WHERE'][] = [
                    'glpi_locations.id' => getSonsOf('glpi_locations', (int) $_POST['locations_id']),
                ];
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $row) {
                $entry = [
                    'itemtype' => $itemtype,
                    'id'       => $row['id'],
                    'checkbox' => Html::getCheckbox([
                        'name'  => "item[" . $row["id"] . "]",
                        'value' => $row["id"],
                        'zero_on_empty' => false,
                    ]),
                    'entity'   => '',
                ];

                $typename = $item::getTypeName();
                if ($itemtype === Peripheral::class) {
                    $item->getFromDB($row['items_id']);
                    if (
                        isset($item->fields["peripheraltypes_id"])
                         && ((int) $item->fields["peripheraltypes_id"] !== 0)
                    ) {
                        $typename = Dropdown::getDropdownName(
                            "glpi_peripheraltypes",
                            $item->fields["peripheraltypes_id"]
                        );
                    }
                }
                $item_link = htmlescape(sprintf(__('%1$s - %2$s'), $typename, $row["name"]));
                if ($itemtype::canView()) {
                    $item_link = "<a href='" . htmlescape($itemtype::getFormURLWithId($row['items_id'])) . "&forcetab=Reservation$1'>"
                        . $item_link
                        . "</a>";
                }
                $entry['item'] = $item_link;

                if (!isset($location_cache[$row["location"]])) {
                    $location_cache[$row["location"]] = Dropdown::getDropdownName("glpi_locations", $row["location"]);
                }
                $entry['location'] = $location_cache[$row["location"]];

                $entry['comment'] = RichText::getSafeHtml($row["comment"]);

                if ($showentity) {
                    if (!isset($entity_cache[$row["entities_id"]])) {
                        $entity_cache[$row["entities_id"]] = Dropdown::getDropdownName("glpi_entities", $row["entities_id"]);
                    }
                    $entry['entity'] = $entity_cache[$row["entities_id"]];
                }
                $cal_href = htmlescape(Reservation::getSearchURL() . "?reservationitems_id=" . $row['id']);
                $entry['calendar'] = "<a href='$cal_href'>";
                $entry['calendar'] .= "<i class='" . htmlescape(Planning::getIcon()) . " fa-2x cursor-pointer' title=\"" . __s("Reserve this item") . "\"></i>";

                $ok = true;
                $entries[] = $entry;
            }
        }

        $columns = [
            'checkbox' => [
                'label' => Html::getCheckAllAsCheckbox('nosearch'),
                'raw_header' => true,
            ],
            'item' => self::getTypeName(1),
            'location' => Location::getTypeName(1),
            'comment' => _n('Comment', 'Comments', 1),
        ];
        if ($showentity) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['calendar'] = __("Booking calendar");
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'checkbox' => 'raw_html',
                'item' => 'raw_html',
                'comment' => 'raw_html',
                'calendar' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);

        if ($ok && Session::haveRight("reservation", self::RESERVEANITEM)) {
            echo "<i class='ti ti-corner-left-up mx-3'></i>";
            echo "<th colspan='" . ($showentity ? "5" : "4") . "'>";
            if (isset($_POST['reserve'])) {
                echo Html::hidden('begin', ['value' => $_POST['reserve']["begin"]]);
                echo Html::hidden('end', ['value'   => $_POST['reserve']["end"]]);
            }
            echo Html::submit(_x('button', 'Book'), [
                'class' => 'btn btn-primary mt-2 mb-2',
                'icon'  => 'ti ti-calendar-plus',
            ]);
        }

        echo "<input type='hidden' name='id' value=''>";
        echo "</form>";// No CSRF token needed
        echo "</div>";
    }

    /**
     * @param $name
     *
     * @return array
     * @used-by CronTask
     **/
    public static function cronInfo($name)
    {
        return ['description' => __('Alerts on reservations')];
    }

    /**
     * Cron action on reservation : alert on end of reservations
     *
     * @param CronTask $task Task to log, if NULL use display (default NULL)
     *
     * @return integer 0 : nothing to do 1 : done with success
     * @used-by CronTask
     **/
    public static function cronReservation($task = null)
    {
        global $CFG_GLPI, $DB;

        if (!$CFG_GLPI["use_notifications"]) {
            return 0;
        }

        $cron_status    = 0;
        $items_infos    = [];
        $items_messages = [];

        foreach (Entity::getEntitiesToNotify('use_reservations_alert') as $entity => $value) {
            $secs = (int) $value * HOUR_TIMESTAMP;

            // Reservation already begin and reservation ended in $value hours
            $criteria = [
                'SELECT' => [
                    'glpi_reservationitems.*',
                    'glpi_reservations.end AS end',
                    'glpi_reservations.id AS resaid',
                ],
                'FROM'   => 'glpi_reservations',
                'LEFT JOIN' => [
                    'glpi_alerts'  => [
                        'ON'  => [
                            'glpi_reservations'  => 'id',
                            'glpi_alerts'        => 'items_id', [
                                'AND' => [
                                    'glpi_alerts.itemtype'  => 'Reservation',
                                    'glpi_alerts.type'      => Alert::END,
                                ],
                            ],
                        ],
                    ],
                    'glpi_reservationitems' => [
                        'ON'  => [
                            'glpi_reservations'     => 'reservationitems_id',
                            'glpi_reservationitems' => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'glpi_reservationitems.entities_id' => $entity,
                    new QueryExpression(
                        QueryFunction::unixTimestamp('glpi_reservations.end') . ' - ' . $secs
                            . ' < ' . QueryFunction::unixTimestamp()
                    ),
                    'glpi_reservations.begin'  => ['<', QueryFunction::now()],
                    'glpi_alerts.date'         => null,
                ],
            ];
            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                if ($item_resa = getItemForItemtype($data['itemtype'])) {
                    if ($item_resa->getFromDB($data["items_id"])) {
                        $data['item_name']                     = $item_resa->getName();
                        $data['entity']                        = $entity;
                        $items_infos[$entity][$data['resaid']] = $data;

                        if (!isset($items_messages[$entity])) {
                            $items_messages[$entity] = [__('Device reservations expiring today')];
                        }
                        $items_messages[$entity][] = sprintf(
                            __('%1$s - %2$s'),
                            $item_resa::getTypeName(),
                            $item_resa->getName()
                        );
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
                        'items'       => $items,
                    ]
                )
            ) {
                $messages    = $items_messages[$entity];
                $cron_status = 1;
                if ($task) {
                    $task->addVolume(1);
                    $task->log(sprintf(
                        __('%1$s: %2$s') . "\n",
                        Dropdown::getDropdownName("glpi_entities", $entity),
                        implode("\n", $messages)
                    ));
                } else {
                    //TRANS: %1$s is a name, %2$s is text of message
                    Session::addMessageAfterRedirect(sprintf(
                        __s('%1$s: %2$s'),
                        htmlescape(Dropdown::getDropdownName("glpi_entities", $entity)),
                        implode('<br>', array_map('htmlescape', $messages))
                    ));
                }

                $alert             = new Alert();
                $input["itemtype"] = 'Reservation';
                $input["type"]     = Alert::END;
                foreach (array_keys($items) as $resaid) {
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
                    Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                }
            }
        }
        return $cron_status;
    }

    public function getRights($interface = 'central')
    {
        if ($interface === 'central') {
            $values = parent::getRights();
        } else {
            $values = [READ => __('Read')];
        }
        $values[self::RESERVEANITEM] = __('Make a reservation');

        return $values;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);
        $ong['no_all_tab'] = true;
        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            $tabs = [];
            if (Session::haveRightsOr("reservation", [READ, self::RESERVEANITEM])) {
                $tabs[1] = self::createTabEntry(Reservation::getTypeName(Session::getPluralNumber()));
            }
            if (
                (Session::getCurrentInterface() === "central")
                && Session::haveRight("reservation", READ)
            ) {
                $tabs[2] = self::createTabEntry(__('Administration'), icon: 'ti ti-shield-check');
            }
            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === self::class) {
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
     * @param array{idtable: string, name: string} $post
     * <ul>
     *     <li>idtable: itemtype of items to show</li>
     *     <li>name: input name</li>
     * </ul>
     *
     * @return void
     */
    public static function ajaxDropdown(array $post)
    {
        if ($post['idtable'] && class_exists($post['idtable'])) {
            $result = self::getAvailableItems($post['idtable']);

            if ($result->count() === 0) {
                echo __s('No reservable item!');
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

    /**
     * Get available items for a given itemtype
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return DBmysqlIterator
     */
    public static function getAvailableItems(string $itemtype): DBmysqlIterator
    {
        global $DB;

        $reservation_table = self::getTable();
        $item_table = $itemtype::getTable();

        $criteria = self::getAvailableItemsCriteria($itemtype);
        $criteria['SELECT'] = [
            "$reservation_table.id",
            "$item_table.name",
        ];

        return $DB->request($criteria);
    }

    /**
     * Get available items for a given itemtype
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return int
     */
    public static function countAvailableItems(string $itemtype): int
    {
        global $DB;

        $criteria = self::getAvailableItemsCriteria($itemtype);
        $criteria['COUNT'] = 'total';
        $results = $DB->request($criteria);
        return $results->current()['total'];
    }

    /**
     * Get common criteria for getAvailableItems and countAvailableItems functions
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return array
     */
    private static function getAvailableItemsCriteria(string $itemtype): array
    {
        $reservation_table = self::getTable();
        /** @var CommonDBTM $item */
        $item = getItemForItemtype($itemtype);
        $item_table = $itemtype::getTable();

        $criteria = [
            'FROM' => $item_table,
            'INNER JOIN' => [
                $reservation_table => [
                    'ON' => [
                        $reservation_table => 'items_id',
                        $item_table => 'id',
                        ['AND' => ["$reservation_table.itemtype" => $itemtype]],
                    ],
                ],
            ],
            'WHERE' => [
                "$reservation_table.is_active"   => 1,
                "$item_table.is_deleted"  => 0,
            ],
        ];

        if ($item->isEntityAssign()) {
            $criteria['WHERE'] += getEntitiesRestrictCriteria($item_table, '', '', $item->maybeRecursive());
        }

        if ($item->maybeTemplate()) {
            $criteria['WHERE']["$item_table.is_template"] = 0;
        }

        return $criteria;
    }
}
