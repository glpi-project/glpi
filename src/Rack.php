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
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\DCBreadcrumb;
use Glpi\Features\DCBreadcrumbInterface;
use Glpi\Features\StateInterface;

/**
 * Rack Class
 **/
class Rack extends CommonDBTM implements AssignableItemInterface, DCBreadcrumbInterface, StateInterface
{
    use DCBreadcrumb;
    use Glpi\Features\State;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
        getEmpty as getEmptyAssignableItem;
    }

    public const FRONT    = 0;
    public const REAR     = 1;

    public const POS_NONE = 0;
    public const POS_LEFT = 1;
    public const POS_RIGHT = 2;

    // orientation in room
    public const ROOM_O_NORTH = 1;
    public const ROOM_O_EAST  = 2;
    public const ROOM_O_SOUTH = 3;
    public const ROOM_O_WEST  = 4;

    // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'datacenter';

    public static function getTypeName($nb = 0)
    {
        //TRANS: Test of comment for translation (mark : //TRANS)
        return _n('Rack', 'Racks', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this
         ->addStandardTab(Item_Rack::class, $ong, $options)
         ->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Document_Item::class, $ong, $options)
         ->addStandardTab(Item_Ticket::class, $ong, $options)
         ->addStandardTab(Item_Problem::class, $ong, $options)
         ->addStandardTab(Change_Item::class, $ong, $options)
         ->addStandardTab(Reservation::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public static function rawSearchOptionsToAdd($itemtype)
    {
        return [
            [
                'id'                 => 'rack',
                'name'               => _n('Rack', 'Racks', Session::getPluralNumber()),
            ],
            [
                'id'                 => '180',
                'table'              => Rack::getTable(),
                'field'              => 'name',
                'name'               => __('Name'),
                'datatype'           => 'dropdown',
                'massiveaction'      => false,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'              => Item_Rack::getTable(),
                        'joinparams'         => [
                            'jointype'           => 'itemtype_item',
                            'specific_itemtype'  => $itemtype,
                        ],
                    ],
                ],
            ],
            [
                'id'                 => '181',
                'table'              => Item_Rack::getTable(),
                'field'              => 'position',
                'name'               => __('Position'),
                'datatype'           => 'number',
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'itemtype_item',
                    'specific_itemtype'  => $itemtype,
                ],
            ],
        ];
    }


    /**
     * Print the rack form
     *
     * @param $ID integer ID of the item
     * @param $options array
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     * @return boolean item found
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/rack.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_racktypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_rackmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => DCRoom::getTable(),
            'field'              => 'name',
            'name'               => DCRoom::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'number_units',
            'name'               => __('Number of units'),
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '85',
            'table'              => Datacenter::getTable(),
            'field'              => 'name',
            'name'               => Datacenter::getTypeName(1),
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => DCRoom::getTable(),
                ],
            ],
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch (get_class($item)) {
            case DCRoom::class:
                $nb = 0;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        self::getTable(),
                        [
                            'dcrooms_id'   => $item->getID(),
                            'is_deleted'   => 0,
                        ]
                    );
                }
                return self::createTabEntry(
                    self::getTypeName(Session::getPluralNumber()),
                    $nb,
                    $item::getType()
                );
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof DCRoom) {
            return self::showForRoom($item);
        }
        return false;
    }

    /**
     * Print room's racks
     *
     * @param DCRoom $room DCRoom object
     *
     * @return bool
     **/
    public static function showForRoom(DCRoom $room): bool
    {
        global $DB;

        $room_id = $room->getID();
        $rand = mt_rand();

        if (
            !$room->getFromDB($room_id)
            || !$room->can($room_id, READ)
        ) {
            return false;
        }
        $canedit = $room->canEdit($room_id);

        $racks = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'dcrooms_id'   => $room->getID(),
                'is_deleted'   => 0,
            ],
        ]);
        $entries = [];

        $racks = iterator_to_array($racks);
        $rack = new self();
        foreach ($racks as $row) {
            $rack->getFromResultSet($row);
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $rack->getID(),
                'name'     => $rack->getLink(),
            ];
        }

        $rows     = (int) $room->fields['vis_rows'];
        $cols     = (int) $room->fields['vis_cols'];
        if ($cols === 0) {
            $cols = 1; //prevent divizion by zero
        }
        $cell_w   = (int) $room->fields['vis_cell_width'];
        $cell_h   = (int) $room->fields['vis_cell_height'];
        $grid_w   = $cell_w * $cols;
        $grid_h   = $cell_h * $rows;

        //fill rows
        $cells    = [];
        $outbound = [];
        foreach ($racks as &$item) {
            $rack->getFromResultSet($item);
            $in = false;

            $x = $y = 0;
            $coord = explode(',', $item['position']);
            if (count($coord) == 2) {
                [$x, $y] = $coord;
                $item['_x'] = (int) $x - 1;
                $item['_y'] = (int) $y - 1;
            } else {
                $item['_x'] = null;
                $item['_y'] = null;
            }

            if ($x <= $cols && $y <= $rows && $x > 0 && $y > 0) {
                $in = true;
                $cells[] = $item;
            }

            if ($in === false) {
                $outbound[] = $item;
            }
        }

        $outbound = array_map(static function ($out) use ($rack) {
            $rack->getFromResultSet($out);
            return [$rack, $out];
        }, $outbound);
        $cells = array_map(static function ($cell) use ($rack) {
            $rack->getFromDB($cell['id']);
            return [$rack, $cell];
        }, $cells);

        $blueprint_url = '';
        if (!empty($room->fields['blueprint'])) {
            $blueprint_url = Toolbox::getPictureUrl($room->fields['blueprint']);
        }

        TemplateRenderer::getInstance()->display('pages/management/dcroom_racks.html.twig', [
            'room' => $room,
            'datatable_params' => [
                'is_tab' => true,
                'nofilter' => true,
                'columns' => [
                    'name' => __('Name'),
                ],
                'formatters' => [
                    'name' => 'raw_html',
                ],
                'entries' => $entries,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => $canedit,
                'massiveactionparams' => [
                    'num_displayed' => count($entries),
                    'container'     => 'mass' . static::class . $rand,
                ],
            ],
            'cols' => $cols,
            'rows' => $rows,
            'cell_w' => $cell_w,
            'cell_h' => $cell_h,
            'grid_w' => $grid_w,
            'grid_h' => $grid_h,
            'cells' => $cells,
            'outbound' => $outbound,
            'blueprint_url' => $blueprint_url,
        ]);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAssignableItem($input);
        if ($input === false) {
            return false;
        }
        if ($this->prepareInput($input)) {
            if (isset($input["id"]) && ($input["id"] > 0)) {
                $input["_oldID"] = $input["id"];
            }
            unset($input['id']);
            unset($input['withtemplate']);
            if (!isset($input['bgcolor']) || empty($input['bgcolor'])) {
                $input['bgcolor'] = '#FEC95C';
            }

            return $input;
        }
        return false;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForUpdateAssignableItem($input);
        if ($input === false) {
            return false;
        }
        if (array_key_exists('bgcolor', $input) && empty($input['bgcolor'])) {
            $input['bgcolor'] = '#FEC95C';
        }
        return $this->prepareInput($input);
    }

    public function post_getEmpty()
    {
        $this->fields['bgcolor'] = '#FEC95C';
    }

    /**
     * Prepares input (for update and add)
     *
     * @param array $input Input data
     *
     * @return false|array
     */
    private function prepareInput($input)
    {
        if (!array_key_exists('dcrooms_id', $input) || $input['dcrooms_id'] == 0) {
            // Position is not set if room not selected
            return $input;
        }

        if ($input['position'] == 0) {
            Session::addMessageAfterRedirect(
                __s('Position must be set'),
                true,
                ERROR
            );
            return false;
        }

        $where = [
            'dcrooms_id'   => $input['dcrooms_id'],
            'position'     => $input['position'],
            'is_deleted'   => false,
        ];

        if (!$this->isNewItem()) {
            $where['NOT'] = ['id' => $input['id']];
        }
        $existing = countElementsInTable(self::getTable(), $where);

        if ($existing > 0) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('%1$s position is not available'),
                    $input['position']
                )),
                true,
                ERROR
            );
            return false;
        }
        return $input;
    }

    /**
     * Get already filled places
     *
     * @param string $itemtype Item type
     * @param int    $items_id Item ID
     *
     * @return array [x => [left => [depth, depth, depth, depth]], [right => [depth, depth, depth, depth]]]
     */
    public function getFilled($itemtype = null, $items_id = null)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => Item_Rack::getTable(),
            'WHERE'  => [
                'racks_id'   => $this->getID(),
            ],
        ]);

        $filled = [];
        foreach ($iterator as $row) {
            $item = getItemForItemtype($row['itemtype']);
            if (!$item->getFromDB($row['items_id'])) {
                continue;
            }
            $units = 1;
            $depth = 1;
            $model = $item->getModelClassInstance();
            $modelsfield = $model::getForeignKeyField();
            if ($item->fields[$modelsfield] != 0) {
                if ($model->getFromDB($item->fields[$modelsfield])) {
                    $units = $model->fields['required_units'];
                    $depth = $model->fields['depth'];
                }
            }
            $position = $row['position'];
            if (
                empty($itemtype) || empty($items_id)
                || $itemtype != $row['itemtype'] || $items_id != $row['items_id']
            ) {
                while (--$units >= 0) {
                    $content_filled = [
                        self::POS_LEFT    => [0, 0, 0, 0],
                        self::POS_RIGHT   => [0, 0, 0, 0],
                    ];

                    if (isset($filled[$position + $units])) {
                        $content_filled = $filled[$position + $units];
                    }

                    if ($row['hpos'] == self::POS_NONE || $row['hpos'] == self::POS_LEFT) {
                        $d = 0;
                        while ($d / 4 < $depth) {
                            $pos = ($row['orientation'] == self::REAR) ? 3 - $d : $d;
                            $val = 1;
                            if (isset($content_filled[self::POS_LEFT][$pos]) && $content_filled[self::POS_LEFT][$pos] != 0) {
                                trigger_error('Several elements exists in rack at same place :(', E_USER_WARNING);
                                $val += $content_filled[self::POS_LEFT][$pos];
                            }
                            $content_filled[self::POS_LEFT][$pos] = $val;
                            ++$d;
                        }
                    }

                    if ($row['hpos'] == self::POS_NONE || $row['hpos'] == self::POS_RIGHT) {
                        $d = 0;
                        while ($d / 4 < $depth) {
                            $pos = ($row['orientation'] == self::REAR) ? 3 - $d : $d;
                            $val = 1;
                            if (isset($content_filled[self::POS_RIGHT][$pos]) && $content_filled[self::POS_RIGHT][$pos] != 0) {
                                trigger_error('Several elements exists in rack at same place :(', E_USER_WARNING);
                                $val += $content_filled[self::POS_RIGHT][$pos];
                            }
                            $content_filled[self::POS_RIGHT][$pos] = $val;
                            ++$d;
                        }
                    }

                    $filled[$position + $units] = $content_filled;
                }
            }
        }

        return $filled;
    }

    public function getEmpty()
    {
        if (!$this->getEmptyAssignableItem() || !parent::getEmpty()) {
            return false;
        }
        $this->fields['number_units'] = 42;
        return true;
    }

    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_Rack::class,
                PDU_Rack::class,
            ]
        );
    }

    public static function getIcon()
    {
        return "ti ti-server";
    }
}
