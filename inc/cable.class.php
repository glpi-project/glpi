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

   static function getTypeName($nb = 0) {
      return _n('Cable', 'Cables', $nb);
   }

   static function getFieldLabel() {
      return self::getTypeName(1);
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
      $this->fields['itemtype_endpoint_a'] = 'Computer';
      $this->fields['itemtype_endpoint_b'] = 'Computer';
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
         'field'              => 'itemtype_endpoint_a',
         'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Endpoint A')),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'socket_types',
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'items_id_endpoint_b',
         'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint B')),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => ['itemtype_endpoint_b']
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'itemtype_endpoint_b',
         'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Endpoint B')),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'socket_types',
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'items_id_endpoint_a',
         'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint A')),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => ['itemtype_endpoint_a']
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'socketmodels_id_endpoint_a',
         'name'               => sprintf(__('%s (%s)'), SocketModel::getTypeName(1), __('Endpoint A')),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'linkfield'          => 'socketmodels_id_endpoint_b',
         'name'               => sprintf(__('%s (%s)'), SocketModel::getTypeName(1), __('Endpoint B')),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'sockets_id_endpoint_b',
         'name'               => sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint B')),
         'datatype'           => 'dropdown',
         'massiveaction'       => false,
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => Socket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'sockets_id_endpoint_a',
         'name'               => sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint A')),
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
         'id'                 => '87',
         'table'              => $this->getTable(),
         'field'              => '_virtual_datacenter_position', // virtual field
         'additionalfields'   => [
            'items_id_endpoint_a',
            'itemtype_endpoint_a'
         ],
         'name'               => sprintf(__('%s (%s)'), __('Data center position'), __('Endpoint A')),
         'datatype'           => 'specific',
         'nosearch'           => true,
         'nosort'             => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '88',
         'table'              => $this->getTable(),
         'field'              => '_virtual_datacenter_position', // virtual field
         'additionalfields'   => [
            'items_id_endpoint_b',
            'itemtype_endpoint_b'
         ],
         'name'               => sprintf(__('%s (%s)'), __('Data center position'), __('Endpoint B')),
         'datatype'           => 'specific',
         'nosearch'           => true,
         'nosort'             => true,
         'massiveaction'      => false
      ];

      return $tab;
   }


   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id_endpoint_a' :
            if (isset($values['itemtype_endpoint_a']) && !empty($values['itemtype_endpoint_a'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype_endpoint_a'], $options);
            }
         case 'items_id_endpoint_b' :
            if (isset($values['itemtype_endpoint_b']) && !empty($values['itemtype_endpoint_b'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype_endpoint_b'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'items_id_endpoint_a' :
         case 'items_id_endpoint_b' :
            $itemtype = $values[str_replace('items_id', 'itemtype', $field)] ?? null;
            if ($itemtype !== null && class_exists($itemtype)) {
               if ($values[$field] > 0) {
                  $item = new $itemtype();
                  $item->getFromDB($values[$field]);
                  return "<a href='" . $item->getLinkURL(). "'>".$item->fields['name']."</a>";
               }
            } else {
               return ' ';
            }
            break;
         case '_virtual_datacenter_position':
            $itemtype = isset($values['itemtype_endpoint_b']) ? $values['itemtype_endpoint_b'] : $values['itemtype_endpoint_a'];
            $items_id = isset($values['items_id_endpoint_b']) ? $values['items_id_endpoint_b'] : $values['items_id_endpoint_a'];

            if (method_exists($itemtype, 'getDcBreadcrumbSpecificValueToDisplay')) {
               return $itemtype::getDcBreadcrumbSpecificValueToDisplay($items_id);
            }
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
         'condition' => ['is_visible_cable' => 1],
      ]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(['name'  => 'users_id_tech',
                     'value'  => $this->fields["users_id_tech"]]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr><td>".__('Comments')."</td>";
      echo "<td  colspan='3'>";
      echo "<textarea cols='45' rows='5' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td>";
      echo "</tr>";

      $rand_itemtype_endpoint_a = rand();
      $rand_itemtype_endpoint_b = rand();

      $rand_items_id_endpoint_a = rand();
      $rand_items_id_endpoint_b = rand();

      $rand_socket_model_rear = rand();
      $rand_socket_model_front = rand();

      echo "<tr class='headerRow'>";
      echo "<th colspan='2'>".__('Endpoint A')."</th>";
      echo "<th colspan='2'>".__('Endpoint B')."</th>";
      echo "<tr>";

      //Line to display itemtype / items_id dropdown for REAR and FRONT
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Asset')."</td>";

      //rear itemtype
      echo "<td><span id='show_itemtype_field' class='input_rear_listener'>";
      Dropdown::showFromArray('itemtype_endpoint_a', Socket::getSocketLinkTypes(), ['value'  => $this->fields["itemtype_endpoint_a"],
                                                                              'rand'   => $rand_itemtype_endpoint_a]);
      echo "</span>";

      //listerner to update rear items_id
      $params = ['itemtype'   => '__VALUE__',
                  'dom_name'  => 'items_id_endpoint_a',
                  'dom_rand'  => $rand_items_id_endpoint_a,
                  'action'    => 'get_items_from_itemtype'];
      Ajax::updateItemOnSelectEvent("dropdown_itemtype_endpoint_a$rand_itemtype_endpoint_a",
                                    "show_items_id_endpoint_a_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      //rear items_id
      echo "<span id='show_items_id_endpoint_a_field' class='input_rear_listener'>";
      $this->fields["itemtype_endpoint_a"]::dropdown(['name'                => 'items_id_endpoint_a',
                                                'value'               => $this->fields["items_id_endpoint_a"],
                                                'rand'                => $rand_items_id_endpoint_a,
                                                'entity_restrict'     => ($this->fields['is_recursive'] ?? false)
                                                                              ? getSonsOf('glpi_entities', $this->fields['entities_id'])
                                                                              : $this->fields['entities_id'],
                                                'display_emptychoice' => true,
                                                'display_dc_position' => true]);

      echo "</span></td>";
      echo "<td>".__('Asset')."</td>";

      //font itemtype
      echo "<td><span id='show_itemtype_field' class='input_front_listener'>";
      Dropdown::showFromArray('itemtype_endpoint_b', Socket::getSocketLinkTypes(), ['value' => $this->fields["itemtype_endpoint_b"],
                                                                               'rand'  => $rand_itemtype_endpoint_b]);
      echo "</span>";

      //listerner to update front items_id
      $params = ['itemtype'   => '__VALUE__',
                 'dom_name'   => 'items_id_endpoint_b',
                 'dom_rand'   => $rand_items_id_endpoint_b,
                 'action'     => 'get_items_from_itemtype'];

      Ajax::updateItemOnSelectEvent("dropdown_itemtype_endpoint_b$rand_itemtype_endpoint_b",
                                    "show_items_id_endpoint_b_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      //front items_id
      echo "<span id='show_items_id_endpoint_b_field'>";
      $this->fields["itemtype_endpoint_b"]::dropdown(['name'                 => 'items_id_endpoint_b',
                                                 'value'                => $this->fields["items_id_endpoint_b"],
                                                 'rand'                 => $rand_items_id_endpoint_b,
                                                 'entity_restrict'      => ($this->fields['is_recursive'] ?? false)
                                                                           ? getSonsOf('glpi_entities', $this->fields['entities_id'])
                                                                           : $this->fields['entities_id'],
                                                 'display_emptychoice'  => true,
                                                 'display_dc_position'  => true]);

      echo "</span></td>";
      echo "</tr>";

      //Line to display dropdown socketmodel
      echo "<tr class='tab_bg_1'>";
      echo "<td>".SocketModel::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span class='input_rear_listener'>";
      SocketModel::dropdown(['name'    => 'socketmodels_id_endpoint_a',
                             'value'   => $this->fields["socketmodels_id_endpoint_a"],
                             'rand'    => $rand_socket_model_rear]);
      echo "</span>";
      echo "</td>";

      echo "<td>".SocketModel::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span class='input_front_listener'>";
      SocketModel::dropdown(['name'    => 'socketmodels_id_endpoint_b',
                             'value'   => $this->fields["socketmodels_id_endpoint_b"],
                             'rand'    => $rand_socket_model_front]);
      echo "</span>";
      echo "</td></tr>";

      //Line to display dropdown socket
      echo "<tr class='tab_bg_1'>";
      echo "<td>".Socket::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span id='show_rear_sockets_field'>";
      Socket::dropdown(['name'      => 'sockets_id_endpoint_a',
                        'value'     => $this->fields["sockets_id_endpoint_a"],
                        'condition' => ['socketmodels_id'   => $this->fields['socketmodels_id_endpoint_a'],
                                        'itemtype'          => $this->fields['itemtype_endpoint_a'],
                                        'items_id'          => $this->fields['items_id_endpoint_a']]
                        ]);
      echo "</span>";
      echo "</td>";

      echo "<td>".Socket::getTypeName(1)."</td>";
      echo "<td>";
      echo "<span id='show_front_sockets_field'>";
      Socket::dropdown(['name'      => 'sockets_id_endpoint_b',
                        'value'     => $this->fields["sockets_id_endpoint_b"],
                        'condition' => ['socketmodels_id'   => $this->fields['socketmodels_id_endpoint_b'],
                                        'itemtype'          => $this->fields['itemtype_endpoint_b'],
                                        'items_id'          => $this->fields['items_id_endpoint_b']]
                        ]);
      echo "</span>";
      echo "</td></tr>";

      //Line to display asset breadcrum (datacenter / dcroom / rack / position)
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";

      echo "<span id='show_rear_asset_breadcrumb'>";
      if ($this->fields['items_id_endpoint_a']) {
         if (method_exists($this->fields['itemtype_endpoint_a'], 'getDcBreadcrumbSpecificValueToDisplay')) {
            echo $this->fields['itemtype_endpoint_a']::getDcBreadcrumbSpecificValueToDisplay($this->fields['items_id_endpoint_a']);
         }
      }
      echo "</span>";

      //Listener to update breacrumb / socket
      echo Html::scriptBlock("
         //listener to remove socket selector and breadcrumb
         $(document).on('change', '#dropdown_itemtype_endpoint_a".$rand_itemtype_endpoint_a."', function(e) {
            $('#show_rear_asset_breadcrumb').empty();
            $('#show_rear_sockets_field').empty();
         });

         //listener to refresh socket selector and breadcrumb
         $(document).on('change', '#dropdown_items_id_endpoint_a".$rand_items_id_endpoint_a."', function(e) {
            var items_id = $('#dropdown_items_id_endpoint_a".$rand_items_id_endpoint_a."').find(':selected').val();
            var itemtype = $('#dropdown_itemtype_endpoint_a".$rand_itemtype_endpoint_a."').find(':selected').val();
            var socketmodels_id = $('#dropdown_socketmodels_id_endpoint_a".$rand_socket_model_rear."').find(':selected').val();
            refreshAssetBreadcrumb(itemtype, items_id, 'show_rear_asset_breadcrumb');
            refreshSocketDropdown(itemtype, items_id, socketmodels_id, 'sockets_id_endpoint_a', 'show_rear_sockets_field');

         });
      ");

      echo "</td>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";

      echo "<span id='show_front_asset_breadcrumb'>";
      if ($this->fields['items_id_endpoint_b']) {
         if (method_exists($this->fields['itemtype_endpoint_b'], 'getDcBreadcrumbSpecificValueToDisplay')) {
            echo $this->fields['itemtype_endpoint_b']::getDcBreadcrumbSpecificValueToDisplay($this->fields['items_id_endpoint_b']);
         }
      }
      echo "</span>";

      //Listener to update breacrumb / socket
      echo Html::scriptBlock("
         //listener to remove socket selector and breadcrumb
         $(document).on('change', '#dropdown_itemtype_endpoint_b".$rand_itemtype_endpoint_b."', function(e) {
            $('#show_front_asset_breadcrumb').empty();
            $('#show_front_sockets_field').empty();
         });

         //listener to refresh socket selector and breadcrumb
         $(document).on('change', '#dropdown_items_id_endpoint_b".$rand_items_id_endpoint_b."', function(e) {
            var items_id = $('#dropdown_items_id_endpoint_b".$rand_items_id_endpoint_b."').find(':selected').val();
            var itemtype = $('#dropdown_itemtype_endpoint_b".$rand_itemtype_endpoint_b."').find(':selected').val();
            var socketmodels_id = $('#dropdown_socketmodels_id_endpoint_b".$rand_socket_model_front."').find(':selected').val();
            refreshAssetBreadcrumb(itemtype, items_id, 'show_front_asset_breadcrumb');
            refreshSocketDropdown(itemtype, items_id, socketmodels_id, 'sockets_id_endpoint_b', 'show_front_sockets_field');

         });
      ");

      echo "</td></tr>";

      $options['colspan'] = 3;
      $this->showFormButtons($options);
      return true;
   }

   static function getIcon() {
      return "fas fa-ethernet";
   }

}
