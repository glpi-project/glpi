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
 * PDU Class
**/
class PDU extends CommonDBTM {
   use DCBreadcrumb;

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'datacenter';

   static function getTypeName($nb = 0) {
      return _n('PDU', 'PDUs', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Pdu_Plug', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
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
         'condition' => ['is_visible_pdu' => 1],
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
      echo "<td><label for='dropdown_pdutypes_id$rand'>".__('Type')."</label></td>";
      echo "<td>";
      PDUType::dropdown([
         'value'  => $this->fields["pdutypes_id"],
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
         'condition' => ['is_assign' => 1],
         'rand'      => $rand
      ]);

      echo "</td>";
      echo "<td><label for='dropdown_pdumodels_id$rand'>".__('Model')."</label></td>";
      echo "<td>";
      PDUModel::dropdown([
         'value'  => $this->fields["pdumodels_id"],
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

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_pdutypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
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

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
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
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_pdu' => 1]
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
         'id'                 => '40',
         'table'              => 'glpi_pdumodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => ['is_assign' => 1],
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
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Change_Item::class,
            Pdu_Plug::class,
            PDU_Rack::class,
         ]
      );
   }

   protected function getMainTabs() {
      return [
         'Pdu_Plug'
      ];
   }

}
