<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Rack Class
**/
class Rack extends CommonDBTM {
   use DCBreadcrumb;

   const FRONT    = 0;
   const REAR     = 1;

   const POS_NONE = 0;
   const POS_LEFT = 1;
   const POS_RIGHT = 2;

   // orientation in room
   const ROOM_O_NORTH = 1;
   const ROOM_O_EAST  = 2;
   const ROOM_O_SOUTH = 3;
   const ROOM_O_WEST  = 4;

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'datacenter';

   static function getTypeName($nb = 0) {
      //TRANS: Test of comment for translation (mark : //TRANS)
      return _n('Rack', 'Racks', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Item_Rack', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      ;
      return $ong;
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;
      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_name$rand'>".__('Name')."</label></td>";
      echo "<td>";
      $objectName = autoName(
         $this->fields["name"],
         "name",
         (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
         $this->getType(),
         $this->fields["entities_id"]
      );
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td>";

      echo "<td><label for='dropdown_states_id$rand'>".__('Status')."</label></td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => "`is_visible_rack`",
         'rand'      => $rand]
      );
      echo "</td></tr>\n";

      $this->showDcBreadcrumb();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_locations_id$rand'>".__('Location')."</label></td>";
      echo "<td>";
      Location::dropdown([
         'value'  => $this->fields["locations_id"],
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "<td><label for='dropdown_racktypes_id$rand'>".__('Type')."</label></td>";
      echo "<td>";
      RackType::dropdown([
         'value'  => $this->fields["racktypes_id"],
         'rand'   => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_users_id_tech$rand'>".__('Technician in charge of the hardware')."</label></td>";
      echo "<td>";
      User::dropdown([
         'name'   => 'users_id_tech',
         'value'  => $this->fields["users_id_tech"],
         'right'  => 'own_ticket',
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "<td><label for='dropdown_manufacturers_id$rand'>".__('Manufacturer')."</label></td>";
      echo "<td>";
      Manufacturer::dropdown([
         'value' => $this->fields["manufacturers_id"],
         'rand' => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_groups_id_tech$rand'>".__('Group in charge of the hardware')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => '`is_assign`',
         'rand'      => $rand
      ]);

      echo "</td>";
      echo "<td><label for='dropdown_rackmodels_id$rand'>".__('Model')."</label></td>";
      echo "<td>";
      RackModel::dropdown([
         'value'  => $this->fields["rackmodels_id"],
         'rand'   => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_serial$rand'>".__('Serial number')."</label></td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'serial', ['rand' => $rand]);
      echo "</td>";

      echo "<td><label for='textfield_otherserial$rand'>".sprintf(__('%1$s%2$s'), __('Inventory number'), $tplmark).
           "</label></td>";
      echo "<td>";

      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField(
         $this,
         'otherserial',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_dcrooms_id$rand'>".__('Server room')."</label></td>";
      echo "<td>";
      $rooms = $DB->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => DCRoom::getTable()
      ]);
      $rooms_list = [];
      while ($row = $rooms->next()) {
         $rooms_list[$row['id']] = $row['name'];
      }
      Dropdown::showFromArray(
         "dcrooms_id",
         $rooms_list, [
            'value'                 => $this->fields["dcrooms_id"],
            'rand'                  => $rand,
            'display_emptychoice'   => true
         ]
      );
      $current = $this->fields['position'];

      Ajax::updateItemOnSelectEvent(
         "dropdown_dcrooms_id$rand",
         "room_positions",
         $CFG_GLPI["root_doc"]."/ajax/dcroom_size.php",
         ['id' => '__VALUE__', 'current' => $current, 'rand' => $rand]
      );
      Ajax::updateItemOnSelectEvent(
         "dropdown_dcrooms_id$rand",
         "dropdown_locations_id$rand",
         $CFG_GLPI["root_doc"]."/ajax/dropdownLocation.php", [
            'items_id' => '__VALUE__',
            'itemtype' => 'DCRoom'
         ]
      );

      echo "</td>";

      echo "<td><label for='dropdown_position$rand'>".__('Position in room')."</label></td>";
      echo "<td id='room_positions'>";
      $dcroom = new DCRoom();
      $positions = [];
      $used = [];
      if ((int)$this->fields['dcrooms_id'] > 0 && $dcroom->getFromDB($this->fields['dcrooms_id'])) {
         $used = $dcroom->getFilled($current);
         $positions = $dcroom->getAllPositions();
         Dropdown::showFromArray(
            'position',
            $positions, [
               'value'                 => $current,
               'rand'                  => $rand,
               'display_emptychoice'   => true,
               'used'                  => $used
            ]
         );
      } else {
         echo __('No room found or selected');
      }

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_room_orientation$rand'>".__('Door orientation in room')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         "room_orientation",
         [
            self::ROOM_O_NORTH => __('North'),
            self::ROOM_O_EAST  => __('East'),
            self::ROOM_O_SOUTH => __('South'),
            self::ROOM_O_WEST  => __('West'),
         ], [
            'value'                 => $this->fields["room_orientation"],
            'rand'                  => $rand,
            'display_emptychoice'   => true
         ]
      );
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_number_units$rand'>" . __('Number of units') . "</label></td><td>";
      Dropdown::showNumber(
         "number_units", [
            'value'  => $this->fields["number_units"],
            'min'    => 1,
            'max'    => 100,
            'step'   => 1,
            'rand'   => $rand
         ]
      );
      echo "&nbsp;".__('U')."</td>";

      echo "<td><label for='width$rand'>".__('Width')."</label></td>";
      echo "<td>".Html::input("width", ['id' => "width$rand", 'value' => $this->fields["width"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='height$rand'>".__('Height')."</label></td>";
      echo "<td>".Html::input("height", ['id' => "height$rand", 'value' => $this->fields["height"]]);
      echo "<td><label for='depth$rand'>".__('Depth')."</label></td>";
      echo "<td>".Html::input("depth", ['id' => "depth$rand", 'value' => $this->fields["depth"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='max_power$rand'>".__('Max. power (in watts)')."</label></td>";
      echo "<td>".Html::input("max_power", ['id' => "max_power$rand", 'value' => $this->fields["max_power"]]);
      echo "<td><label for='mesured_power$rand'>".__('Measured power (in watts)')."</label></td>";
      echo "<td>".Html::input("mesured_power", ['id' => "mesured_power$rand", 'value' => $this->fields["mesured_power"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='max_weight$rand'>".__('Max. weight')."</label></td>";
      echo "<td>".Html::input("max_weight", ['id' => "max_weight$rand", 'value' => $this->fields["max_weight"]]);
      echo "<td><label for='bgcolor$rand'>".__('Background color')."</label></td>";
      echo "<td>";
      Html::showColorField(
         'bgcolor', [
            'value'  => $this->fields['bgcolor'],
            'rand'   => $rand
         ]
      );
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='comment'>".__('Comments')."</label></td>";
      echo "<td colspan='3' class='middle'>";

      echo "<textarea cols='45' rows='3' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   function rawSearchOptions() {
      global $CFG_GLPI;

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false // implicit key==1
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false, // implicit field is id
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_racktypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_rackmodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => '`is_visible_rack`'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => DCRoom::getTable(),
         'field'              => 'name',
         'name'               => __('Server room'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'number_units',
         'name'               => __('Number of units'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => __('Manufacturer'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => '`is_assign`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case DCRoom::getType():
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable(
                  self::getTable(), [
                     'dcrooms_id'   => $item->getID(),
                     'is_deleted'   => 0
                  ]
               );
            }
            return self::createTabEntry(
               self::getTypeName(Session::getPluralNumber()),
               $nb
            );
            break;
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($item->getType()) {
         case DCRoom::getType():
            self::showForRoom($item);
            break;
      }
   }

   /**
    * Print room's racks
    *
    * @param DCRoom $room DCRoom object
    *
    * @return void
   **/
   static function showForRoom(DCRoom $room) {
      global $DB, $CFG_GLPI;

      $room_id = $room->getID();
      $rand = mt_rand();

      if (!$room->getFromDB($room_id)
          || !$room->can($room_id, READ)) {
         return false;
      }
      $canedit = $room->canEdit($room_id);

      $racks = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'dcrooms_id'   => $room->getID(),
            'is_deleted'   => 0
         ]
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
      echo "<i id='sviewlist' class='pointer fa fa-list-alt' title='".__('View as list')."'></i>";
      echo "<i id='sviewgraph' class='pointer fa fa-th-large selected' title='".__('View graphical representation')."'></i>";
      echo "</div>";

      $racks = iterator_to_array($racks);
      echo "<div id='viewlist'>";

      $rack = new self();
      if (!count($racks)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No rack found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($racks)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Name')."</th>";
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

      $data = [];

      $rows     = (int) $room->fields['vis_rows'];
      $cols     = (int) $room->fields['vis_cols'];
      $w_prct   = 100 / $cols;
      $grid_w   = 40 * $cols;
      $grid_h   = (39 * $rows) + 16;
      $ajax_url = $CFG_GLPI['root_doc']."/ajax/rack.php";

      //fill rows
      $cells    = [];
      $outbound = [];
      foreach ($racks as &$item) {
         $rack->getFromResultSet($item);
         $in = false;

         $x = $y = 0;
         $coord = explode(',', $item['position']);
         if (is_array($coord) && count($coord) == 2) {
            list($x, $y) = $coord;
            $item['_x'] = $x - 1;
            $item['_y'] = $y - 1;
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
         echo __('Following elements are out of room bounds');
         echo "</th></thead><tbody>";
         foreach ($outbound as $out) {
            $rack->getFromResultSet($out);
            echo "<tr><td>".self::getCell($rack, $out)."</td></tr>";
         }
         echo "</tbody></table>";
      }

      echo "<style>";
      for ($i = 0; $i < $cols; $i++) {
         $left  = $i * $w_prct;
         $width = ($i+1) * $w_prct;
         echo "
         .grid-stack > .grid-stack-item[data-gs-x='$i'] { left: $left%;}
         .grid-stack > .grid-stack-item[data-gs-width='".($i+1)."'] {
            min-width: $width%;
            width: $width%;
         }";
      }
      echo "</style>";

      $blueprint = "";
      $blueprint_ctrl = "";
      if (strlen($room->fields['blueprint'])) {
         $blueprint = "
            <div class='blueprint'
                 style='background: url({$room->fields['blueprint']}) no-repeat top left/100% 100%;
                        height: ".$grid_h."px;></div>";
         $blueprint_ctrl = "<span class='mini_toggle active'
                                  id='toggle_blueprint'>".__('Blueprint')."</span>";
      }

      echo "
      <div class='grid-room' style='width: ".($grid_w + 16)."px; min-height: ".$grid_h."px'>
         <span class='racks_view_controls'>
            $blueprint_ctrl
            <span class='mini_toggle active'
                  id='toggle_grid'>".__('Grid')."</span>
            <div class='sep'></div>
         </span>
         <ul class='indexes indexes-x'></ul>
         <ul class='indexes indexes-y'></ul>
         <div class='racks_add' style='width: ".$grid_w."px'></div>
         <div class='grid-stack grid-stack-$cols' style='width: ".$grid_w."px'>";

      foreach ($cells as $cell) {
         if ($rack->getFromDB($cell['id'])) {
            echo self::getCell($rack, $cell);
         }
      }

      // add a locked element to bottom to display a full grid
      echo "<div class='grid-stack-item lock-bottom'
                 data-gs-no-resize='true'
                 data-gs-no-move='true'
                 data-gs-height='1'
                 data-gs-width='$cols'
                 data-gs-x='0'
                 data-gs-y='$rows'></div>";

      echo "</div>"; //.grid-stack
      echo $blueprint;
      echo "</div>"; //.grid-room
      echo "<div class='sep'></div>";
      echo "<div id='grid-dialog'></div>";
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

         $('.grid-room .grid-stack').gridstack({
            width: $cols,
            height: ($rows + 1),
            cellHeight: 39,
            verticalMargin: 0,
            float: true,
            disableOneColumnMode: true,
            animate: true,
            removeTimeout: 100,
            disableResize: true,
            draggable: {
              handle: '.grid-stack-item-content',
              appendTo: 'body',
              containment: '.grid-stack',
              cursor: 'move',
              scroll: true,
            }
         });

         var lockAll = function() {
            // lock all item (prevent pushing down elements)
            $('.grid-stack').each(function (idx, gsEl) {
               $(gsEl).data('gridstack').locked('.grid-stack-item', true);
            });

            // add containment to items, this avoid bad collisions on the start of the grid
            $('.grid-stack .grid-stack-item').draggable('option', 'containment', 'parent');
         };
         lockAll(); // call it immediatly

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

         $('.grid-stack')
            .on('change', function(event, items) {
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
                     var answer = jQuery.parseJSON(answer);

                     // revert to old position
                     if (!answer.status) {
                        dirty = true;
                        grid.move(item.el, x_before_drag, y_before_drag);
                        dirty = false;
                        displayAjaxMessageAfterRedirect();
                     }
                  });
               });
            })
            .on('dragstart', function(event, ui) {
               var element = $(event.target);
               var node    = element.data('_gridstack_node');

               // store position before drag
               x_before_drag = Number(node.x);
               y_before_drag = Number(node.y);

               // disable qtip
               element.qtip('hide', true);
            })
            .on('click', function(event, ui) {
               var grid    = this;
               var element = $(event.target);
               var el_url  = element.find('a').attr('href');

               if (el_url) {
                  window.location = el_url;
               }
            });


         $('#viewgraph .cell_add').on('click', function(){
            var _this = $(this);
            if (_this.find('div').length == 0) {
               var _x = _this.data('x');
               var _y = _this.data('y');

               $.ajax({
                  url : "{$rack->getFormURL()}",
                  data: {
                     room: $room_id,
                     position: _x + ',' + _y,
                     ajax: true
                  },
                  success: function(data) {
                     $('#grid-dialog')
                        .html(data)
                        .dialog({
                           modal: true,
                           width: 'auto'
                        });
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

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   function post_getEmpty() {
      $this->fields['bgcolor'] = '#FEC95C';
   }

   /**
    * Prepares input (for update and add)
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $where = [
         'dcrooms_id'   => $input['dcrooms_id'],
         'position'     => $input['position'],
         'is_deleted'   => false
      ];

      if (!$this->isNewItem()) {
         $where['NOT'] = ['id' => $input['id']];
      }
      $existing = countElementsInTable(self::getTable(), $where);

      if ($existing > 0) {
         Session::addMessageAfterRedirect(
            sprintf(
               __('%1$s position is not available'),
               $input['position']
            ),
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
    * @param string $current Current position to exclude; defaults to null
    *
    * @return array [x => [left => [depth, depth, depth, depth]], [right => [depth, depth, depth, depth]]]
    */
   public function getFilled($itemtype = null, $items_id = null) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => Item_Rack::getTable(),
         'WHERE'  => [
            'racks_id'   => $this->getID()
         ]
      ]);

      $filled = [];
      while ($row = $iterator->next()) {
         $item = new $row['itemtype'];
         if (!$item->getFromDB($row['items_id'])) {
            continue;
         }
         $units = 1;
         $width = 1;
         $depth = 1;
         if ($item->fields[strtolower($item->getType()) . 'models_id'] != 0) {
            $model_class = $item->getType() . 'Model';
            $modelsfield = strtolower($item->getType()) . 'models_id';
            $model = new $model_class;
            if ($model->getFromDB($item->fields[$modelsfield])) {
               $units = $model->fields['required_units'];
               $depth = $model->fields['depth'];
               $width = $model->fields['is_half_rack'] == 0 ? 1 : 0.5;
            }
         }
         $position = $row['position'];
         if (empty($itemtype) || empty($items_id)
            || $itemtype != $row['itemtype'] || $items_id != $row['items_id']
         ) {
            while (--$units >= 0) {
               $content_filled = [
                  self::POS_LEFT    => [0, 0, 0, 0],
                  self::POS_RIGHT   => [0, 0, 0, 0]
               ];

               if (isset($filled[$position + $units])) {
                  $content_filled = $filled[$position + $units];
               }

               if ($row['hpos'] == self::POS_NONE || $row['hpos'] == self::POS_LEFT) {
                  $d = 0;
                  while ($d/4 < $depth) {
                     $pos = ($row['orientation'] == self::REAR) ? 3 - $d : $d;
                     $val = 1;
                     if (isset($content_filled[self::POS_LEFT][$pos]) && $content_filled[self::POS_LEFT][$pos] != 0) {
                        Toolbox::logError('Several elements exists in rack at same place :(');
                        $val += $content_filled[self::POS_LEFT][$pos];
                     }
                     $content_filled[self::POS_LEFT][$pos] = $val;
                     ++$d;
                  }
               }

               if ($row['hpos'] == self::POS_NONE || $row['hpos'] == self::POS_RIGHT) {
                  $d = 0;
                  while ($d/4 < $depth) {
                     $pos = ($row['orientation'] == self::REAR) ? 3 - $d : $d;
                     $val = 1;
                     if (isset($content_filled[self::POS_RIGHT][$pos]) && $content_filled[self::POS_RIGHT][$pos] != 0) {
                        Toolbox::logError('Several elements exists in rack at same place :(');
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

   public function getEmpty() {
      if (!parent::getEmpty()) {
         return false;
      }
      $this->fields['number_units'] = 42;
      return true;
   }

   /**
    * Get cell content
    *
    * @param Rack  $rack Rack instance
    * @param mixed $cell Rack cell (array or false)
    *
    * @return string
    */
   private static function getCell(Rack $rack, $cell) {
      $bgcolor = $rack->getField('bgcolor');
      $fgcolor = Html::getInvertedColor($bgcolor);
      return "<div class='grid-stack-item room_orientation_".$cell['room_orientation']."'
                  data-gs-id='".$cell['id']."'
                  data-gs-height='1'
                  data-gs-width='1'
                  data-gs-x='".$cell['_x']."'
                  data-gs-y='".$cell['_y']."'>
            <div class='grid-stack-item-content'
                  style='background-color: $bgcolor;
                        color: $fgcolor;'>
               <a href='".$rack->getLinkURL()."'
                  style='color: $fgcolor'>".
                  $cell['name']."</a>
               <span class='tipcontent'>
                  <span>
                     <label>".__('name').":</label>".
                     $cell['name']."
                  </span>
                  <span>
                     <label>".__('serial').":</label>".
                     $cell['serial']."
                  </span>
                  <span>
                     <label>".__('Inventory number').":</label>".
                     $cell['otherserial']."
                  </span>
               </span>
            </div><!-- // .grid-stack-item-content -->
         </div>"; // .grid-stack-item
   }
}
