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

/**
 * Rack Class
 **/
class Rack extends CommonDBTM
{
    use Glpi\Features\DCBreadcrumb;
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
        switch ($item->getType()) {
            case DCRoom::getType():
                self::showForRoom($item);
                break;
        }
        return true;
    }

    /**
     * Print room's racks
     *
     * @param DCRoom $room DCRoom object
     *
     * @return void
     **/
    public static function showForRoom(DCRoom $room)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

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

        Session::initNavigateListItems(
            self::getType(),
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $room->getTypeName(1),
                $room->getName()
            )
        );

        echo "<div id='switchview'>";
        echo "<i id='sviewlist' class='pointer ti ti-list' title='" . __s('View as list') . "'></i>";
        echo "<i id='sviewgraph' class='pointer ti ti-layout-grid selected' title='" . __s('View graphical representation') . "'></i>";
        echo "</div>";

        $racks = iterator_to_array($racks);
        echo "<div id='viewlist'>";

        $rack = new self();
        if (!count($racks)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __s('No rack found') . "</th></tr>";
            echo "</table>";
        } else {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($racks)),
                    'container'       => 'mass' . __CLASS__ . $rand,
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header .= "</th>";
            }
            $header .= "<th>" . __s('Name') . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($racks as $row) {
                $rack->getFromResultSet($row);
                echo "<tr lass='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                    echo "</td>";
                }
                echo "<td>" . $rack->getLink() . "</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";

            if ($canedit && count($racks)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
            }
            if ($canedit) {
                Html::closeForm();
            }
        }
        echo "</div>";

        echo "<div id='viewgraph'>";

        $rows     = (int) $room->fields['vis_rows'];
        $cols     = (int) $room->fields['vis_cols'];
        if ($cols === 0) {
            $cols = 1; //prevent divizion by zero
        }
        $w_prct   = 100 / $cols;
        $cell_w   = (int) $room->fields['vis_cell_width'];
        $cell_h   = (int) $room->fields['vis_cell_height'];
        $grid_w   = $cell_w * $cols;
        $grid_h   = $cell_h * $rows;
        $ajax_url = $CFG_GLPI['root_doc'] . "/ajax/rack.php";

        //fill rows
        $cells    = [];
        $outbound = [];
        foreach ($racks as &$item) {
            $rack->getFromResultSet($item);
            $in = false;

            $x = $y = 0;
            $coord = explode(',', $item['position']);
            if (is_array($coord) && count($coord) == 2) {
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

        if (count($outbound)) {
            echo "<table class='outbound'><thead><th>";
            echo __s('Following elements are out of room bounds');
            echo "</th></thead><tbody>";
            foreach ($outbound as $out) {
                $rack->getFromResultSet($out);
                echo "<tr><td>" . self::getCell($rack, $out) . "</td></tr>";
            }
            echo "</tbody></table>";
        }

        echo "<style>
            :root {
                --dcroom-grid-cellw: {$cell_w}px;
                --dcroom-grid-cellh: {$cell_h}px;
            }";
        for ($i = 0; $i < $cols; $i++) {
            $left  = $i * $w_prct;
            $width = ($i + 1) * $w_prct;
            echo "
         .grid-stack > .grid-stack-item[gs-x='$i'] { left: $left%;}
         .grid-stack > .grid-stack-item[gs-w='" . ($i + 1) . "'] {
            min-width: $width%;
            width: $width%;
         }";
        }
        echo "</style>";

        $blueprint = "";
        $blueprint_ctrl = "";
        if (!empty($room->fields['blueprint'])) {
            $blueprint_url = Toolbox::getPictureUrl($room->fields['blueprint']);
            $blueprint = "
            <div class='blueprint'
                 style='background: url({$blueprint_url}) no-repeat top left/100% 100%;
                        height: " . $grid_h . "px;'></div>";
            $blueprint_ctrl = "<span class='mini_toggle active'
                                  id='toggle_blueprint'>" . __('Blueprint') . "</span>";
        }

        echo "
      <div class='grid-room' style='width: " . ($grid_w + 16) . "px; min-height: " . ($grid_h + 16) . "px'>
         <span class='racks_view_controls'>
            $blueprint_ctrl
            <span class='mini_toggle active'
                  id='toggle_grid'>" . __('Grid') . "</span>
            <div class='clearfix'></div>
         </span>
         <ul class='indexes indexes-x'></ul>
         <ul class='indexes indexes-y'></ul>";

        $dcroom = new DCRoom();
        if ($dcroom->canCreate()) {
            echo "<div class='racks_add' style='width: " . $grid_w . "px'></div>";
        }

        echo "<div class='grid-stack grid-stack-$cols' style='width: " . $grid_w . "px'>";

        foreach ($cells as $cell) {
            if ($rack->getFromDB($cell['id'])) {
                echo self::getCell($rack, $cell);
            }
        }

        // add a locked element to bottom to display a full grid
        echo "<div class='grid-stack-item lock-bottom'
                 gs-no-resize='true'
                 gs-no-move='true'
                 gs-h='1'
                 gs-w='$cols'
                 gs-x='0'
                 gs-y='$rows'></div>";

        echo "</div>"; //.grid-stack
        echo $blueprint;
        echo "</div>"; //.grid-room
        echo "</div>"; // #viewgraph

        $rack_add_tip = __s('Insert a rack here');
        $js = <<<JAVASCRIPT
      $(function() {
         $(document)
            .on('click', '#sviewlist', function() {
               $('#viewlist').show();
               $('#viewgraph').hide();
               $(this).addClass('selected');
               $('#sviewgraph').removeClass('selected');
            })
            .on('click', '#sviewgraph', function() {
               $('#viewlist').hide();
               $('#viewgraph').show();
               $(this).addClass('selected');
               $('#sviewlist').removeClass('selected');
            })
            .on("click", "#toggle_blueprint", function() {
               $(this).toggleClass('active');
               $('#viewgraph').toggleClass('clear_blueprint');
            })
            .on("click", "#toggle_grid", function() {
               $(this).toggleClass('active');
               $('#viewgraph').toggleClass('clear_grid');
            })

         window.dcroom_grid = GridStack.init({
            column: $cols,
            maxRow: ($rows + 1),
            cellHeight: {$cell_h},
            margin: 0,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
         });

         // add indexes
         for (var x = 1; x <= $cols; x++) {
            $('.indexes-x').append('<li>' + getBijectiveIndex(x) + '</li>');
         }
         for (var y = 1; y <= $rows; y++) {
            $('.indexes-y').append('<li>' + y + '</li>');
         }
         // append cells for adding racks
         for (var y = 1; y <= $rows; y++) {
            for (var x = 1; x <= $cols; x++) {
               $('.racks_add')
                  .append('<div class=\"cell_add\" data-x='+x+' data-y='+y+'><span class="tipcontent">{$rack_add_tip}</span></div>');
            }
         }

         var x_before_drag = 0;
         var y_before_drag = 0;
         var dirty = false;
         var is_dragged = false;

         window.dcroom_grid.on('change', function(event, items) {
           if (dirty) {
              return;
           }
           var grid = $(event.target).data('gridstack');

           $.each(items, function(index, item) {
              $.post('{$ajax_url}', {
                 id: item.id,
                 dcrooms_id: $room_id,
                 action: 'move_rack',
                 x: item.x + 1,
                 y: item.y + 1,
              }, function(answer) {
                 // revert to old position
                 if (!answer.status) {
                    dirty = true;
                    grid.update(item.el, {
                       'x': x_before_drag,
                       'y': y_before_drag
                    });
                    dirty = false;
                    displayAjaxMessageAfterRedirect();
                 }
              });
           });
         })
        .on('dragstart', function(event, ui) {
            is_dragged = true;
            var element = $(event.target);
            var node    = element[0].gridstackNode;

            // store position before drag
            x_before_drag = Number(node.x);
            y_before_drag = Number(node.y);

            // disable qtip
            element.qtip('hide', true);
        })
        .on('dragstop', function(event, ui) {
            setTimeout(() => { // prevent unwanted click (cannot find another way)
                is_dragged = false;
            }, 50);
        })


        $('.grid-stack')
            .on('click', function(event, ui) {
                var grid    = this;
                var element = $(event.target);
                var el_url  = element.find('a').attr('href');

                if (el_url && !is_dragged) {
                    window.location = el_url;
                }
            });


         $('#viewgraph .cell_add').on('click', function(){
            var _this = $(this);
            if (_this.find('div').length == 0) {
               var _x = _this.data('x');
               var _y = _this.data('y');

               glpi_ajax_dialog({
                  url : "{$rack->getFormURL()}",
                  method: 'GET',
                  dialogclass: 'modal-xl',
                  params: {
                     room: $room_id,
                     position: _x + ',' + _y,
                     ajax: true
                  }
               });
            }
         });

         $('#viewgraph .cell_add, #viewgraph .grid-stack-item').each(function() {
            var tipcontent = $(this).find('.tipcontent');
            if (tipcontent.length) {
               $(this).qtip({
                  position: {
                     my: 'left center',
                     at: 'right center',
                  },
                  content: {
                     text: tipcontent
                  },
                  style: {
                     classes: 'qtip-shadow qtip-bootstrap rack_tipcontent'
                  }
               });
            }
         });
      });
JAVASCRIPT;

        echo Html::scriptBlock($js);
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
        /** @var \DBmysql $DB */
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

    /**
     * Get cell content
     *
     * @param Rack  $rack Rack instance
     * @param mixed $cell Rack cell (array or false)
     *
     * @return string
     */
    private static function getCell(Rack $rack, $cell)
    {
        $bgcolor = htmlescape($rack->getField('bgcolor'));
        $fgcolor = htmlescape(Html::getInvertedColor($bgcolor));
        return "<div class='grid-stack-item room_orientation_" . htmlescape($cell['room_orientation']) . "'
                  gs-id='" . htmlescape($cell['id']) . "'
                  gs-locked='true'
                  gs-h='1'
                  gs-w='1'
                  gs-x='" . htmlescape($cell['_x']) . "'
                  gs-y='" . htmlescape($cell['_y']) . "'>
            <div class='grid-stack-item-content'
                  style='background-color: $bgcolor;
                        color: $fgcolor;'>
               <a href='" . $rack->getLinkURL() . "'
                  style='color: $fgcolor'>" .
                  htmlescape($cell['name']) . "</a>
               <span class='tipcontent'>
                  <span>
                     <label>" . __s('name') . ":</label>" .
                     htmlescape($cell['name']) . "
                  </span>
                  <span>
                     <label>" . __s('serial') . ":</label>" .
                     htmlescape($cell['serial']) . "
                  </span>
                  <span>
                     <label>" . __s('Inventory number') . ":</label>" .
                     htmlescape($cell['otherserial']) . "
                  </span>
               </span>
            </div><!-- // .grid-stack-item-content -->
         </div>"; // .grid-stack-item
    }


    public static function getIcon()
    {
        return "ti ti-server";
    }
}
