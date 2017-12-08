<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

      if ($this->isNewItem()) {
         if (isset($_GET['position'])) {
            $this->fields['position'] = $_GET['position'];
         }
         if (isset($_GET['room'])) {
            $this->fields['dcrooms_id'] = $_GET['room'];
         }
      }

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
      echo "<td><label for='mesured_power$rand'>".__('Mesured power (in watts)')."</label></td>";
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

   function getSearchOptionsNew() {
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

      $tab = array_merge($tab, Location::getSearchOptionsToAddNew());

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
         'condition'          => '`is_visible_computer`'
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

      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      return $tab;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case DCRoom::getType():
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

      $ID = $room->getID();
      $rand = mt_rand();

      if (!$room->getFromDB($ID)
          || !$room->can($ID, READ)) {
         return false;
      }
      $canedit = $room->canEdit($ID);

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
      //all rows; empty
      for ($i = 1; $i < (int)$room->fields['vis_rows'] + 1; ++$i) {
         $obj = ['num' => $i];
         for ($y = 1; $y < (int)$room->fields['vis_cols'] +1; ++$y) {
            $obj["f$y"] = "<div class='rack-add'><span class='tipcontent'>" . __('Insert a rack here')  . "</span><i class='fa fa-plus-square'></i></div>";
         }
         $data[$i] = $obj;
      }

      //fill rows
      $outbound = [];
      foreach ($racks as $row) {
         $rack->getFromResultSet($row);
         $in = false;
         if (preg_match('/(\d+),\s?(\d+)/', $row['position'], $position)) {
            $x = $position[1];
            $y = $position[2];
            if (isset($data[$y]) && isset($data[$y]["f$x"])) {
               $in = true;
               $style = '';
               if ($rack->getField('bgcolor') != '') {
                  $style = " style='background-color:" . $rack->getField('bgcolor') . ";" .
                     "border-color: " . $rack->getField('bgcolor') . "'";
               }
               $data[$y]["f$x"] = "<div data-id='{$rack->getID()}' $style>" . $rack->getName() .
                  "<a href='{$rack->getLinkURL()}'><i class='fa fa-external-link'></i></a>".
                  "<span class='tipcontent'><strong>".__('Name:')."</strong> {$rack->getName()}<br/>
                  <strong>".__('Serial:')."</strong> {$rack->getField('serial')}</span></div>";
            }
         }

         if ($in === false) {
            $outbound[] = $row;
         }
      }

      if (count($outbound)) {
         echo "<table><thead><th colspan='10' class='redips-mark'>";
         echo __('Following elements are out of room bounds');
         echo "</th></thead><tbody>";
         echo "<tr>";
         $count = 0;
         foreach ($outbound as $out) {
            if ($count % 10 == 0) {
               echo "</tr><tr>";
            }
            $rack->getFromResultSet($out);

            $style = '';
            if ($rack->getField('bgcolor') != '') {
               $style = " style='background-color:" . $rack->getField('bgcolor') . ";" .
                  "border-color: " . $rack->getField('bgcolor') . "'";
            }

            echo "<td><div data-id='{$rack->getID()}' $style>" . $rack->getName() .
               "<a href='{$rack->getLinkURL()}'><i class='fa fa-link'></i></a>".
               "<span class='tipcontent'><strong>".__('Name:')."</strong> {$rack->getName()}<br/>
               <strong>".__('Serial:')."</strong> {$rack->getField('serial')}</span></div></td>";
            ++$count;
         }
         echo "</tr></tbody></table>";
      }

      echo "<table class='rooms'><thead><tr>";
      for ($i = 0; $i < (int)$room->fields['vis_cols'] + 1; ++$i) {
         if ($i === 0) {
            echo "<th></th>";
         } else {
            echo "<th>$i</th>";
         }
      }
      echo "</tr></thead>";
      foreach ($data as $pos => $row) {
         echo "<tr>";
         foreach ($row as $col => $cell) {
            $col = str_replace('f', '', $col);
            if (is_int($cell)) {
               echo "<th>$cell</th>";
            } else {
               echo "<td data-x='$col' data-y='$pos'>$cell</td>";
            }
         }
         echo "</tr>";
      }
      echo "</table>";
      echo "</div>";

      $js = "$(function(){
         $('#sviewlist').on('click', function(){
            $('#viewlist').show();
            $('#viewgraph').hide();
            $(this).addClass('selected');
            $('#sviewgraph').removeClass('selected');
         });
         $('#sviewgraph').on('click', function(){
            $('#viewlist').hide();
            $('#viewgraph').show();
            $(this).addClass('selected');
            $('#sviewlist').removeClass('selected');
         });

         $('#viewgraph .rack-add').on('click', function(){
            var _this = $(this);
            if (_this.find('div').length == 0) {
               var _x = _this.data('x');
               var _y = _this.data('y');
               window.location = '{$rack->getFormURL()}?room={$room->getID()}&position=' + _x + ',' + _y;
            }
         });

         $('#viewgraph table div').each(function() {
            var _this = $(this);
            _this.qtip({
               position: { viewport: $(window) },
               content: {
                  text: _this.find('.tipcontent')
               },
               style: {
                  classes: 'qtip-shadow qtip-bootstrap'
               }
            });
         });
      });";
      echo Html::scriptBlock($js);
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
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
    * @return array [x => ['depth' => 1, 'orientation' => 0, 'width' => 1, 'hpos' =>0]]
    *               orientation will not be available if depth is > 0.5; hpos will not be available
    *               if width is = 1
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
         $item->getFromDB($row['items_id']);
         $units = 1;
         $width = 1;
         $depth = 1;
         if ($item->fields[strtolower($item->getType()) . 'models_id'] != 0) {
            $model_class = $item->getType() . 'Model';
            $modelsfield = strtolower($item->getType()) . 'models_id';
            $model = new $model_class;
            $model->getFromDB($item->fields[$modelsfield]);
            $units = $model->fields['required_units'];
            $depth = $model->fields['depth'];
            $width = $model->fields['is_half_rack'] == 0 ? 1 : 0.5;
         }
         $position = $row['position'];
         if (empty($itemtype) || empty($items_id)
            || $itemtype != $row['itemtype'] || $items_id != $row['items_id']
         ) {
            while (--$units >= 0) {
               if (isset($filled[$position + $units])) {
                  $filled[$position + $units]['width'] += $width;
                  $filled[$position + $units]['depth'] += $depth;
                  if ($filled[$position + $units]['depth'] == 1) {
                     unset($filled[$position + $units]['orientation']);
                  }
                  if ($filled[$position + $units]['width'] == 1) {
                     unset($filled[$position + $units]['hpos']);
                  }
               } else {
                  $values = [
                     'width'  => $width,
                     'depth'  => $depth
                  ];
                  if ($width <= 0.5) {
                     $values['hpos'] = $row['hpos'];
                  }
                  if ($depth <= 0.5) {
                     $values['orientation'] = $row['orientation'];
                  }
                  $filled[$position + $units] = $values;
               }
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
}
