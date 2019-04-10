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
 *  Computer class
**/
class Computer extends CommonDBTM {
   use DCBreadcrumb;

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = ['Item_Disk','ComputerVirtualMachine',
                                          'Computer_SoftwareVersion', 'Infocom',
                                          'NetworkPort', 'ReservationItem',
                                          'Item_OperatingSystem'];
   // Specific ones
   ///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
   public $devices                     = [];

   static $rightname                   = 'computer';
   protected $usenotepad               = true;


   static function getTypeName($nb = 0) {
      return _n('Computer', 'Computers', $nb);
   }


   function useDeletedToLockIfDynamic() {
      return false;
   }


   static function getMenuShorcut() {
      return 'o';
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Item_OperatingSystem', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('Item_Disk', $ong, $options)
         ->addStandardTab('Computer_SoftwareVersion', $ong, $options)
         ->addStandardTab('Computer_Item', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('ComputerVirtualMachine', $ong, $options)
         ->addStandardTab('ComputerAntivirus', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Link', $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Lock', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Reservation', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function post_restoreItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   function post_deleteItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   function post_updateItem($history = 1) {
      global $DB, $CFG_GLPI;

      $changes = [];
      for ($i=0; $i<count($this->updates); $i++) {
         // Update contact of attached items
         if ($this->updates[$i] == 'contact_num' && $CFG_GLPI['is_contact_autoupdate']) {
            $changes['contact_num'] = $this->fields['contact_num'];
         }
         if ($this->updates[$i] == 'contact' && $CFG_GLPI['is_contact_autoupdate']) {
            $changes['contact'] = $this->fields['contact'];
         }
         // Update users and groups of attached items
         if ($this->updates[$i] == 'users_id'
             && $CFG_GLPI['is_user_autoupdate']) {
            $changes['users_id'] = $this->fields['users_id'];
         }
         if ($this->updates[$i] == 'groups_id'
             && $CFG_GLPI['is_group_autoupdate']) {
            $changes['groups_id'] = $this->fields['groups_id'];
         }
         // Update state of attached items
         if (($this->updates[$i] == 'states_id')
             && ($CFG_GLPI['state_autoupdate_mode'] < 0)) {
            $changes['states_id'] = $this->fields['states_id'];
         }
         // Update loction of attached items
         if ($this->updates[$i] == 'locations_id'
             && $CFG_GLPI['is_location_autoupdate']) {
            $changes['locations_id'] = $this->fields['locations_id'];
         }
      }

      if (count($changes)) {
         $update_done = false;

         // Propagates the changes to linked items
         foreach ($CFG_GLPI['directconnect_types'] as $type) {
            $items_result = $DB->request(
               [
                  'SELECT' => ['items_id'],
                  'FROM'   => Computer_Item::getTable(),
                  'WHERE'  => [
                     'itemtype'     => $type,
                     'computers_id' => $this->fields["id"],
                     'is_deleted'   => 0
                  ]
               ]
            );
            $item      = new $type();
            foreach ($items_result as $data) {
               $tID = $data['items_id'];
               $item->getFromDB($tID);
               if (!$item->getField('is_global')) {
                  $changes['id'] = $item->getField('id');
                  if ($item->update($changes)) {
                     $update_done = true;
                  }
               }
            }
         }

         //fields that are not present for devices
         unset($changes['groups_id']);
         unset($changes['users_id']);
         unset($changes['contact_num']);
         unset($changes['contact']);

         if (count($changes) > 0) {
            // Propagates the changes to linked devices
            foreach ($CFG_GLPI['itemdevices'] as $device) {
               $item = new $device();
               $devices_result = $DB->request(
                  [
                     'SELECT' => ['id'],
                     'FROM'   => $item::getTable(),
                     'WHERE'  => [
                        'itemtype'     => self::getType(),
                        'items_id'     => $this->fields["id"],
                        'is_deleted'   => 0
                     ]
                  ]
               );
               foreach ($devices_result as $data) {
                  $tID = $data['id'];
                  $item->getFromDB($tID);
                  $changes['id'] = $item->getField('id');
                  if ($item->update($changes)) {
                     $update_done = true;
                  }
               }
            }
         }

         if ($update_done) {
            if (isset($changes['contact']) || isset($changes['contact_num'])) {
               Session::addMessageAfterRedirect(
                  __('Alternate username updated. The connected items have been updated using this alternate username.'),
                  true);
            }
            if (isset($changes['groups_id']) || isset($changes['users_id'])) {
               Session::addMessageAfterRedirect(
                  __('User or group updated. The connected items have been moved in the same values.'),
                  true);
            }
            if (isset($changes['states_id'])) {
               Session::addMessageAfterRedirect(
                  __('Status updated. The connected items have been updated using this status.'),
                  true);
            }
            if (isset($changes['locations_id'])) {
               Session::addMessageAfterRedirect(
                  __('Location updated. The connected items have been moved in the same location.'),
                  true);
            }
         }
      }
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD OS
         Item_OperatingSystem::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Devices
         Item_devices::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Infocoms
         Infocom::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD volumes
         Item_Disk::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD software
         Computer_SoftwareVersion::cloneComputer($this->input["_oldID"], $this->fields['id']);

         Computer_SoftwareLicense::cloneComputer($this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         Contract_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Documents
         Document_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Ports
         NetworkPort::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // Add connected devices
         Computer_Item::cloneComputer($this->input["_oldID"], $this->fields['id']);

         //Add notepad
         Notepad::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         //Add KB links
         KnowbaseItem_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);
      }
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Certificate_Item::class,
            Change_Item::class,
            Computer_Item::class,
            Computer_SoftwareLicense::class,
            Computer_SoftwareVersion::class,
            ComputerAntivirus::class,
            ComputerVirtualMachine::class,
            Item_Disk::class,
            Item_OperatingSystem::class,
            Item_Problem::class,
            Item_Project::class,
         ]
      );

      Item_Devices::cleanItemDeviceDBOnItemDelete($this->getType(), $this->fields['id'],
                                                  (!empty($this->input['keep_devices'])));
   }


   /**
    * Print the computer form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    * @return boolean
   **/
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td><label for='textfield_name$rand'>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark) .
           "</label></td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_states_id$randDropdown'>".__('Status')."</label></td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_computer' => 1],
         'rand'      => $randDropdown
      ]);
      echo "</td></tr>\n";

      $this->showDcBreadcrumb();

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_locations_id$randDropdown'>".__('Location')."</label></td>";
      echo "<td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"],
                               'rand' => $randDropdown]);
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_computertypes_id$randDropdown'>".__('Type')."</label></td>";
      echo "<td>";
      ComputerType::dropdown(['value' => $this->fields["computertypes_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_users_id_tech$randDropdown'>".__('Technician in charge of the hardware')."</label></td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"],
                           'rand'   => $randDropdown]);
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_manufacturers_id$randDropdown'>".__('Manufacturer')."</label></td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_groups_id_tech$randDropdown'>".__('Group in charge of the hardware')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => ['is_assign' => 1],
         'rand' => $randDropdown
      ]);

      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_computermodels_id$randDropdown'>".__('Model')."</label></td>";
      echo "<td>";
      ComputerModel::dropdown(['value' => $this->fields["computermodels_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      //TRANS: Number of the alternate username
      echo "<td><label for='textfield_contact_num$rand'>".__('Alternate username number')."</label></td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'contact_num', ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='textfield_serial$rand'>".__('Serial number')."</label></td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'serial', ['rand' => $rand]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_contact$rand'>".__('Alternate username')."</label></td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'contact', ['rand' => $rand]);
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
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_users_id$randDropdown'>".__('User')."</label></td>";
      echo "<td>";
      User::dropdown(['value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all',
                           'rand'   => $randDropdown]);
      echo "</td>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_networks_id$randDropdown'>".__('Network')."</label></td>";
      echo "<td>";
      Network::dropdown(['value' => $this->fields["networks_id"], 'rand' => $randDropdown]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_groups_id$randDropdown'>".__('Group')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'value'     => $this->fields["groups_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_itemgroup' => 1],
         'rand'      => $randDropdown
      ]);

      echo "</td>";

      // Display auto inventory informations
      $rowspan        = 4;

      echo "<td rowspan='$rowspan'><label for='comment'>".__('Comments')."</label></td>";
      echo "<td rowspan='$rowspan' class='middle'>";

      echo "<textarea cols='45' rows='".($rowspan+3)."' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_domains_id$randDropdown'>".__('Domain')."</label></td>";
      echo "<td >";
      Domain::dropdown(['value'  => $this->fields["domains_id"],
                             'entity' => $this->fields["entities_id"],
                             'rand'   => $randDropdown]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_uuid$rand'>".__('UUID')."</label></td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'uuid', ['rand' => $rand]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      $randDropdown = mt_rand();
      echo "<td><label for='dropdown_autoupdatesystems_id$randDropdown'>".__('Update Source')."</label></td>";
      echo "<td >";
      AutoUpdateSystem::dropdown(['value' => $this->fields["autoupdatesystems_id"], 'rand' => $randDropdown]);
      echo "</td></tr>";
      // Display auto inventory informations
      if (!empty($ID)
          && Plugin::haveImport()
          && $this->fields["is_dynamic"]) {
         echo "<tr class='tab_bg_1'><td colspan='4'>";
         Plugin::doHook("autoinventory_information", $this);
         echo "</td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   function getLinkedItems() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['itemtype', 'items_id'],
         'FROM'   => 'glpi_computers_items',
         'WHERE'  => ['computers_id' => $this->getID()]
      ]);

      $tab = [];
      while ($data = $iterator->next()) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      }
      return $tab;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['Item_OperatingSystem'.MassiveAction::CLASS_ACTION_SEPARATOR.'update']    = OperatingSystem::getTypeName();
         $actions['Computer_Item'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']    = _x('button', 'Connect');
         $actions['Computer_SoftwareVersion'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'] = _x('button', 'Install');

         $kb_item = new KnowbaseItem();
         $kb_item->getEmpty();
         if ($kb_item->canViewItem()) {
            $actions['KnowbaseItem_Item'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'] = _x('button', 'Link knowledgebase article');
         }
      }

      return $actions;
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
         'table'              => 'glpi_computertypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_computermodels',
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
         'condition'          => ['is_visible_computer' => 1]
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => 'glpi_autoupdatesystems',
         'field'              => 'name',
         'name'               => __('Update Source'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '47',
         'table'              => $this->getTable(),
         'field'              => 'uuid',
         'name'               => __('UUID'),
         'datatype'           => 'string'
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
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'contact',
         'name'               => __('Alternate username'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'contact_num',
         'name'               => __('Alternate username number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => __('Group'),
         'condition'          => ['is_itemgroup' => 1],
         'datatype'           => 'dropdown'
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
         'id'                 => '32',
         'table'              => 'glpi_networks',
         'field'              => 'name',
         'name'               => __('Network'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '33',
         'table'              => 'glpi_domains',
         'field'              => 'name',
         'name'               => __('Domain'),
         'datatype'           => 'dropdown'
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
         'condition'          => ['is_assign' => 1],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      // add operating system search options
      $tab = array_merge($tab, Item_OperatingSystem::rawSearchOptionsToAdd(get_class($this)));

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Item_Disk::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, ComputerVirtualMachine::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, ComputerAntivirus::rawSearchOptionsToAdd());

      return $tab;
   }

}
