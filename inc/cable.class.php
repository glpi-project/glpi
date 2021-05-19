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
   static $rightname         = 'netpoint';
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
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'rear_socketmodels_id',
         'name'               => SocketModel::getTypeName(1)." (".__('Rear').")",
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'front_socketmodels_id',
         'name'               => SocketModel::getTypeName(1)." (".__('Front').")",
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'front_sockets_id',
         'name'               => Socket::getTypeName(1)." (".__('Front').")",
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'rear_sockets_id',
         'name'               => Socket::getTypeName(1)." (".__('Rear').")",
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '11',
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
      echo "</td>";

      echo "<td>".__('Comments')."</td>";
      echo "<td>";
      echo "<textarea cols='45' rows='5' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".SocketModel::getTypeName(1)." (".__('Rear').")</td>";
      echo "<td>";
      $rand_rear_socketmodel = SocketModel::dropdown(['name'    => 'rear_socketmodels_id',
                                'value'   => $this->fields["rear_socketmodels_id"],
                                'entity'  => $this->fields["entities_id"]]);

      $params = ['socketmodels_id'  => '__VALUE__',
                  'entity'             => $this->fields["entities_id"],
                  'entity'             => $this->fields["entities_id"],
                  'dom_name'           => 'rear_sockets_id',
                  'action'             => 'getSocketByModel'];
      Ajax::updateItemOnSelectEvent("dropdown_rear_socketmodels_id$rand_rear_socketmodel",
                                    "show_rear_sockets_field",
                                    $CFG_GLPI["root_doc"]."/ajax/socket.php",
                                    $params);
      echo "</td>";

      echo "<td>".SocketModel::getTypeName(1)." (".__('Front').")</td>";
      echo "<td>";
      $rand_front_socketmodel = SocketModel::dropdown(['name'    => 'front_socketmodels_id',
                                                        'value'   => $this->fields["front_socketmodels_id"],
                                                        'entity'  => $this->fields["entities_id"]]);

      $params = ['socketmodels_id'  => '__VALUE__',
                  'entity'             => $this->fields["entities_id"],
                  'dom_name'           => 'front_sockets_id',
                  'action'             => 'getSocketByModel'];
      Ajax::updateItemOnSelectEvent("dropdown_front_socketmodels_id$rand_front_socketmodel",
                                    "show_front_sockets_field",
                                    $CFG_GLPI["root_doc"]."/ajax/socket.php",
                                    $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Socket::getTypeName(1)." (".__('Rear').")</td>";
      echo "<td>";
      echo "<span id='show_rear_sockets_field'>";
      Socket::dropdown(['name'      => 'rear_sockets_id',
                        'value'     => $this->fields["rear_sockets_id"],
                        'entity'    => $this->fields["entities_id"],
                        'condition' => [ 'socketmodels_id' => $this->fields['rear_socketmodels_id']]]);
      echo "</span></td>";

      echo "<td>".Socket::getTypeName(1)." (".__('Front').")</td>";
      echo "<td>";
      echo "<span id='show_front_sockets_field'>";
      Socket::dropdown(['name'      => 'front_sockets_id',
                        'value'     => $this->fields["front_sockets_id"],
                        'entity'    => $this->fields["entities_id"],
                        'condition' => [ 'socketmodels_id' => $this->fields['front_socketmodels_id']]
                        ]);
      echo "</span></td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   static function getIcon() {
      return "fas fa-ethernet";
   }

}
