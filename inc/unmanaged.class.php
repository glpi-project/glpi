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

/**
 * Not managed devices from inventory
 */
class Unmanaged extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'config';

   static function getTypeName($nb = 0) {
      return _n('Unmanaged device', 'Unmanaged devices', $nb);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'name', ['size' => 35]);
      echo "</td>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . _n('Type', 'Types', 1) . "</td>";
      echo "<td>";
      Dropdown::showItemTypes(
         'item_type', [
            'Computer',
            'NetworkEquipment',
            'Printer',
            'Peripheral',
            'Phone'
         ], [
            'value' => $this->fields["itemtype"]
         ]
      );
      echo "</td>";
      echo "<td>" . __('Alternate username') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'contact', ['size' => 35]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . Location::getTypeName(1) . "</td>";
      echo "<td>";
      Dropdown::show(
         'Location', [
            'name'   => 'locations_id',
            'value'  => $this->fields['locations_id']
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Approved device') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("accepted", $this->fields["accepted"]);
      echo "</td>";
      echo "<td>" . __('Serial Number') . "</td>";
      echo "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'serial', ['size' => 35]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Network hub') . "</td>";
      echo "<td>";
      echo Dropdown::getYesNo($this->fields["hub"]);
      echo "</td>";
      echo "<td>" . __('Inventory number') . "</td>";
      echo "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'otherserial', ['size' => 35]);
      echo "</td>";
      echo "</tr>";

      if ((!empty($this->fields["ip"])) || (!empty($this->fields["mac"]))) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __('IP') . "</td>";
         echo "<td>";
         Html::autocompletionTextField($this, 'ip', ['size' => 35]);
         echo "</td>";

         echo "<td colspan='2'></td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Sysdescr') . "</td>";
      echo "<td>";
      echo "<textarea name='sysdescr'  cols='45' rows='5'>".$this->fields["sysdescr"]."</textarea>";
      echo "</td>";
      echo "<td>" . __('Comments') . "</td>";
      echo "</td>";
      echo "<td>";
      echo "<textarea  cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'        => '2',
         'table'     => $this->getTable(),
         'field'     => 'id',
         'name'      => __('ID'),
      ];

      $tab[] = [
         'id'        => '3',
         'table'     => 'glpi_locations',
         'field'     => 'name',
         'linkfield' => 'locations_id',
         'name'      => Location::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'           => '4',
         'table'        => $this->getTable(),
         'field'        => 'serial',
         'name'         => __('Serial Number'),
         'autocomplete' => true,
      ];

      $tab[] = [
         'id'           => '5',
         'table'        => $this->getTable(),
         'field'        => 'otherserial',
         'name'         => __('Inventory number'),
         'autocomplete' => true,
      ];

      $tab[] = [
         'id'           => '6',
         'table'        => $this->getTable(),
         'field'        => 'contact',
         'name'         => Contact::getTypeName(1),
         'autocomplete' => true,
      ];

      $tab[] = [
         'id'        => '7',
         'table'     => $this->getTable(),
         'field'     => 'hub',
         'name'      => __('Network hub'),
         'datatype'  => 'bool',
      ];

      $tab[] = [
         'id'        => '8',
         'table'     => 'glpi_entities',
         'field'     => 'completename',
         'linkfield' => 'entities_id',
         'name'      => Entity::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '9',
         'table'     => 'glpi_domains',
         'field'     => 'name',
         'linkfield' => 'domains_id',
         'name'      => Domain::getTypeName(1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '10',
         'table'     => $this->getTable(),
         'field'     => 'comment',
         'name'      => __('Comments'),
         'datatype'  => 'text',
      ];

      $tab[] = [
         'id'        => '13',
         'table'     => $this->getTable(),
         'field'     => 'itemtype',
         'name'      => _n('Type', 'Types', 1),
         'datatype'  => 'dropdown',
      ];

      $tab[] = [
         'id'        => '14',
         'table'     => $this->getTable(),
         'field'     => 'date_mod',
         'name'      => __('Last update'),
         'datatype'  => 'datetime',
      ];

      $tab[] = [
         'id'        => '15',
         'table'     => $this->getTable(),
         'field'     => 'sysdescr',
         'name'      => __('Sysdescr'),
         'datatype'  => 'text',
      ];

      $tab[] = [
         'id'           => '18',
         'table'        => $this->getTable(),
         'field'        => 'ip',
         'name'         => __('IP'),
         'autocomplete' => true,
      ];

      return $tab;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            NetworkPort::class
         ]
      );
   }

   static function getIcon() {
      return "fas fa-question";
   }

   function getSpecificMassiveActions($checkitem = null) {
      $actions = [];
      if (self::canUpdate()) {
         $actions['Unmanaged'.MassiveAction::CLASS_ACTION_SEPARATOR.'convert']    = __('Convert');
      }
      return $actions;
   }

   static function getMassiveActionsForItemtype(
      array &$actions,
      $itemtype,
      $is_deleted = 0,
      CommonDBTM $checkitem = null
   ) {
      if (self::canUpdate()) {
         $actions['Unmanaged'.MassiveAction::CLASS_ACTION_SEPARATOR.'convert']    = __('Convert');
      }
   }

   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;
      switch ($ma->getAction()) {
         case 'convert':
            echo __('Select an itemtype: ') . ' ';
            Dropdown::showFromArray('itemtype', $CFG_GLPI['inventory_types']);
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   static function processMassiveActionsForOneItemtype(
      MassiveAction $ma,
      CommonDBTM $item,
      array $ids
   ) {
      global $CFG_GLPI;
      switch ($ma->getAction()) {
         case 'convert':
            $unmanaged = new self();
            foreach ($ids as $id) {
               $itemtype = $CFG_GLPI['inventory_types'][$_POST['itemtype']];
               $unmanaged->convert($id, $itemtype);
               $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
            }
            break;

      }
   }

   /**
    * Convert to a managed asset
    */
   public function convert($items_id, $itemtype) {
      global $DB;

      $this->getFromDB($items_id);
      $netport = new NetworkPort();

      $iterator = $DB->request([
         'SELECT' => ['id'],
         'FROM' => NetworkPort::getTable(),
         'WHERE' => [
            'itemtype' => self::getType(),
            'items_id' => $items_id
         ]
      ]);

      if (!empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
      }

      $asset = new $itemtype;
      $asset_data = [
         'name'          => $this->fields['name'],
         'entities_id'   => $this->fields['entities_id'],
         'serial'        => $this->fields['serial'],
         'uuid'          => $this->fields['uuid'],
         'is_dynamic'    => 1
      ];
      $assets_id = $asset->add(Toolbox::addslashes_deep($asset_data));

      while ($row = $iterator->next()) {
         $row += [
            'items_id' => $assets_id,
            'itemtype' => $itemtype
         ];
         $netport->update(Toolbox::addslashes_deep($row));
      }
      $this->deleteFromDB(1);
   }
}
