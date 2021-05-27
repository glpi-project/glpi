<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/// Class Cable
class Cable extends CommonDBTM {

   // From CommonDBTM
   public $dohistory         = true;
   static $rightname         = 'cable_management';
   public $can_be_translated = false;

   static function getTypeName($nb = 0) {
      return _n('Cable', 'Cables', $nb);
   }

   static function getFieldLabel() {
      return _n('Cable', 'Cables', 1);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function post_getEmpty() {
      $this->fields['color'] = '#dddddd';
   }

   function rawSearchOptions() {
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
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_cabletypes',
         'field'              => 'name',
         'name'               => _n('Cable type', 'Cable types', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_cablestrands',
         'field'              => 'name',
         'name'               => _n('Cable strand', 'Cable strands', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'rear_itemtype',
         'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber())." ".__('Rear'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'socket_link_types',
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'front_items_id',
         'name'               => __('Associated item ID')." ".__('Front'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => ['front_itemtype']
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'front_itemtype',
         'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber())." ".__('Front'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'socket_link_types',
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'rear_items_id',
         'name'               => __('Associated item ID')." ".__('Rear'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => ['rear_itemtype']
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'rear_socketmodels_id',
         'name'               => SocketModel::getTypeName(1)." (".__('Rear').")",
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'front_socketmodels_id',
         'name'               => SocketModel::getTypeName(1)." (".__('Front').")",
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'front_sockets_id',
         'name'               => Socket::getTypeName(1)." (".__('Front').")",
         'datatype'           => 'dropdown',
         'massiveaction'       => false,
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'rear_sockets_id',
         'name'               => Socket::getTypeName(1)." (".__('Rear').")",
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'color',
         'name'               => __('Color'),
         'datatype'           => 'color'
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
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown'
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
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_cable' => 1]
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }

   /**
    * @since 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'items_id':

            if (isset($values['itemtype'])) {
               if (isset($options['comments']) && $options['comments']) {
                  $valueData = Dropdown::getDropdownName(
                     getTableForItemType($values['itemtype']),
                     $values[$field],
                     1
                  );
                  return sprintf(
                     __('%1$s %2$s'),
                     $valueData['name'],
                     Html::showToolTip($valueData['comment'], ['display' => false])
                  );

               }

               if ($values[$field] > 0) {
                  return Dropdown::getDropdownName(
                     getTableForItemType($values['itemtype']),
                     $values[$field]
                  );
               }

            } else {
               return ' ';
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
   * Print the main form
   *
   * @param integer $ID      Integer ID of the item
   * @param array  $options  Array of possible options:
   *     - target for the Form
   *     - withtemplate : template or basic item
   *
   * @return void|boolean (display) Returns false if there is a rights error.
   **/
   function showForm($ID, $options = []) {

      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Inventory number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "otherserial");
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".CableType::getTypeName(1)."</td>";
      echo "<td>";
      CableType::dropdown(['name'   => 'cabletypes_id',
                           'value'  => $this->fields["cabletypes_id"]]);
      echo "</td>";
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".CableStrand::getTypeName(1)."</td>";
      echo "<td>";
      CableStrand::dropdown(['name'    => 'cablestrands_id',
                              'value'  => $this->fields["cablestrands_id"]]);
      echo "</td>";
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Color')."</td>";
      echo "<td>";
      Html::showColorField("color", ["value" => $this->fields["color"]]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_computer' => 1],
      ]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(['name'  => 'users_id_tech',
                     'value'  => $this->fields["users_id_tech"],
                     'entity' => $this->fields["entities_id"]]);
      echo "</td><td></td></tr>";

      echo "<tr><td>".__('Comments')."</td>";
      echo "<td  colspan='3'>";
      echo "<textarea cols='45' rows='5' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td>";
      echo "</tr>";

      $rand_itemtype_rear = rand();
      $rand_itemtype_front = rand();

      $rand_items_id_rear = rand();
      $rand_items_id_front = rand();

      $rand_socket_model_rear = rand();
      $rand_socket_model_front = rand();

      $rand_socket_rear = rand();
      $rand_socket_front = rand();

      echo "<tr class='headerRow'>";
      echo "<th colspan='2'>".__('Rear')."</th>";
      echo "<th colspan='2'>".__('Front')."</th>";
      echo "<tr>";

      //Line to display itemtype dropdown
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Asset')."</td>";

      echo "<td>";

      Dropdown::showFromArray('rear_itemtype', Socket::getSocketLinkTypes(), ['value'                 => $this->fields["rear_itemtype"],
                                                                              'rand'                  => $rand_itemtype_rear]);

      $params = ['itemtype'        => '__VALUE__',
                  'dom_name'       => 'rear_items_id',
                  'rand'           => $rand_items_id_rear,
                  'action'         => 'getItemsFromItemtype'];
      Ajax::updateItemOnSelectEvent("dropdown_rear_itemtype$rand_itemtype_rear",
                                    "show_rear_items_id_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      echo "<span id='show_rear_items_id_field'>";

      if (!empty($this->fields["rear_itemtype"])) {
         $rear_itemtype = $this->fields["rear_itemtype"];
      } else {
         $rear_itemtype = "Computer";
      }
      $rear_itemtype::dropdown(['name'                  => 'rear_items_id',
                                                'value'                 => $this->fields["rear_items_id"],
                                                'rand'                  => $rand_items_id_rear,
                                                'display_emptychoice'   => true]);

      echo "</span></td>";

      echo "<td>".__('Asset')."</td>";
      echo "<td>";
      Dropdown::showFromArray('front_itemtype', Socket::getSocketLinkTypes(), ['value'                => $this->fields["front_itemtype"],
                                                                               'rand'                 => $rand_itemtype_front]);

      $params = ['itemtype'   => '__VALUE__',
                 'dom_name'   => 'front_items_id',
                 'rand'       => $rand_items_id_front,
                 'action'     => 'getItemsFromItemtype'];

      Ajax::updateItemOnSelectEvent("dropdown_front_itemtype$rand_itemtype_front",
                                    "show_front_items_id_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      echo "<span id='show_front_items_id_field'>";
      if (!empty($this->fields["front_itemtype"])) {
         $front_itemtype = $this->fields["front_itemtype"];
      } else {
         $front_itemtype = "Computer";
      }

      $front_itemtype ::dropdown(['name'                 => 'front_items_id',
                                 'value'                => $this->fields["front_items_id"],
                                 'rand'                 => $rand_items_id_front,
                                 'display_emptychoice'  => true]);

      echo "</span></td>";
      echo "</tr>";

      //Line to display dropdown socketmodel
      echo "<tr class='tab_bg_1'>";
      echo "<td>".SocketModel::getTypeName(1)."</td>";
      echo "<td>";

      SocketModel::dropdown(['name'    => 'rear_socketmodels_id',
                             'value'   => $this->fields["rear_socketmodels_id"],
                             'rand'    => $rand_socket_model_rear,
                             'entity'  => $this->fields["entities_id"]]);

      $params = ['itemtype'          => '__VALUE0__',
                  'items_id'         => '__VALUE1__',
                  'socketmodels_id'  => '__VALUE2__',
                  'dom_name'         => 'rear_sockets_id',
                  'rand'             => $rand_socket_rear,
                  'action'           => 'getSocketByModelAndItem'];

      Ajax::updateItemOnSelectEvent(["dropdown_rear_itemtype".$rand_itemtype_rear,
                                     "dropdown_rear_items_id".$rand_items_id_rear,
                                     "dropdown_rear_socketmodels_id".$rand_socket_model_rear],
                                    "show_rear_sockets_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      echo "</td>";

      echo "<td>".SocketModel::getTypeName(1)."</td>";
      echo "<td>";

      SocketModel::dropdown(['name'    => 'front_socketmodels_id',
                             'value'   => $this->fields["front_socketmodels_id"],
                             'rand'    => $rand_socket_model_front,
                             'entity'  => $this->fields["entities_id"]]);

      $params = ['itemtype'         => '__VALUE0__',
                 'items_id'         => '__VALUE1__',
                 'socketmodels_id'  => '__VALUE2__',
                 'dom_name'         => 'front_sockets_id',
                 'rand'             => $rand_socket_front,
                 'action'           => 'getSocketByModelAndItem'];

      Ajax::updateItemOnSelectEvent(["dropdown_front_itemtype".$rand_itemtype_front,
                                    "dropdown_front_items_id".$rand_items_id_front,
                                    "dropdown_front_socketmodels_id".$rand_socket_model_front],
                                    "show_front_sockets_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      echo "</td></tr>";

      //Line to display dropdown socket
      echo "<tr class='tab_bg_1'>";
      echo "<td>".Socket::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span id='show_rear_sockets_field'>";
      Socket::dropdown(['name'      => 'rear_sockets_id',
                        'rand'      => $rand_socket_rear,
                        'value'     => $this->fields["rear_sockets_id"],
                        'condition' => ['socketmodels_id' => $this->fields['rear_socketmodels_id'],
                                        'itemtype'  => $this->fields['rear_itemtype'],
                                        'items_id'  => $this->fields['rear_items_id']]
                        ]);

      echo "</span>";
      echo "</td>";

      echo "<td>".Socket::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span id='show_front_sockets_field'>";

      Socket::dropdown(['name'      => 'front_sockets_id',
                        'rand'      => $rand_socket_front,
                        'value'     => $this->fields["front_sockets_id"],
                        'condition' => ['socketmodels_id' => $this->fields['front_socketmodels_id'],
                                        'itemtype'  => $this->fields['front_itemtype'],
                                        'items_id'  => $this->fields['front_items_id']]
                     ]);

      echo "</span>";
      echo "</td></tr>";

      //Line to display asset breadcrum (item, datacenter / dcroom / position)


      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";

      echo "<span id='show_rear_asset_breadcrumb'>";
      if ($this->fields['rear_items_id']) {
         $this->getDCBreadCrumb('rear');
      }
      echo "</span>";
      

      $params = ['itemtype'  => '__VALUE0__',
                 'items_id'  => '__VALUE1__',
                 'action'    => 'getItemBreadCrumb'];

      Ajax::updateItemOnSelectEvent(["dropdown_rear_itemtype".$rand_itemtype_rear,
                                     "dropdown_rear_items_id".$rand_items_id_rear],
                                     "show_rear_asset_breadcrumb",
                                     $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                     $params);

      echo "</td>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";

      echo "<span id='show_front_asset_breadcrumb'>";
      if ($this->fields['front_items_id']) {
         $this->getDCBreadCrumb('front');
      }
      echo "</span>";

      $params = ['itemtype'  => '__VALUE0__',
                  'items_id'  => '__VALUE1__',
                  'action'    => 'getItemBreadCrumb'];

      Ajax::updateItemOnSelectEvent(["dropdown_front_itemtype".$rand_itemtype_front,
                              "dropdown_front_items_id".$rand_items_id_front],
                              "show_front_asset_breadcrumb",
                              $CFG_GLPI["root_doc"]."/ajax/cable.php",
                              $params);
      echo "</td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   private function getDCBreadCrumb($side = 'front'){
      if ($this->fields[$side.'_items_id']) {
         $item = new $this->fields[$side.'_itemtype']();
         $item->getFromDB($this->fields[$side.'_items_id']);
         if (method_exists($item,'showDcBreadcrumb')) {
            echo $item->showDcBreadcrumb(true);
         }
      }
   }

   static function getIcon() {
      return "fas fa-ethernet";
   }

}
