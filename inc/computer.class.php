<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Computer class
**/
class Computer extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = array('ComputerDisk','ComputerVirtualMachine',
                                               'Computer_SoftwareVersion', 'Infocom',
                                               'NetworkPort', 'ReservationItem');
   // Specific ones
   ///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
   public $devices                     = array();

   static $rightname                   = 'computer';
   protected $usenotepad               = true;


   /**
    * Name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Computer', 'Computers', $nb);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @since version 9.1
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong = array(1 => __('Operating System'));
               return $ong;
         }
      }
   }


   /**
    * @see CommonDBTM::useDeletedToLockIfDynamic()
    *
    * @since version 0.84
   **/
   function useDeletedToLockIfDynamic() {
      return false;
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since version 0.85
   **/
   static function getMenuShorcut() {
      return 'o';
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong)
         ->addStandardTab(__CLASS__, $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('ComputerDisk', $ong, $options)
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
         ->addStandardTab('Lock', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Reservation', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
    *
    * @since version 9.1
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showOperatingSystem($item);
      return true;
   }


   /**
    * Print the computer's operating system form
    *
    * @param $comp Computer object
    *
    * @since version 9.1
    *
    * @return Nothing (call to classes members)
   **/
   static function showOperatingSystem(Computer $comp) {
      global $DB;

      $ID = $comp->fields['id'];
      $colspan = 4;

      echo "<div class='center'>";

      $comp->initForm($ID);
      $comp->showFormHeader(['formtitle' => false]);

      echo "<tr class='headerRow'><th colspan='".$colspan."'>";
      echo __('Operating system');
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      OperatingSystem::dropdown(array('value' => $comp->fields["operatingsystems_id"]));
      echo "</td>";
      echo "<td>".__('Version')."</td>";
      echo "<td >";
      OperatingSystemVersion::dropdown(array('value' => $comp->fields["operatingsystemversions_id"]));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Architecture')."</td>";
      echo "<td >";
      OperatingSystemArchitecture::dropdown(array('value'
                                                 => $comp->fields["operatingsystemarchitectures_id"]));
      echo "</td>";
      echo "<td>".__('Service pack')."</td>";
      echo "<td >";
      OperatingSystemServicePack::dropdown(array('value'
                                                 => $comp->fields["operatingsystemservicepacks_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Kernel version')."</td>";
      echo "<td >";
      Html::autocompletionTextField($comp, 'os_kernel_version');
      echo "</td>";
      echo "<td>".__('Product ID')."</td>";
      echo "<td >";
      Html::autocompletionTextField($comp, 'os_licenseid');
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td >";
      Html::autocompletionTextField($comp, 'os_license_number');
      echo "</td><td colspan='2'></td></tr>";

      $comp->showFormButtons(array('candel' => false, 'formfooter' => false));
   }


   function post_restoreItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   function post_deleteItem() {

      $comp_softvers = new Computer_SoftwareVersion();
      $comp_softvers->updateDatasForComputer($this->fields['id']);
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {
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
             && $this->fields['users_id']
             && $CFG_GLPI['is_user_autoupdate']) {
            $changes['users_id'] = $this->fields['users_id'];
         }
         if ($this->updates[$i] == 'groups_id'
             && $this->fields['groups_id']
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
             && $this->fields['locations_id']
             && $CFG_GLPI['is_location_autoupdate']) {
            $changes['locations_id'] = $this->fields['locations_id'];
         }
      }
      // Propagates the changes to linked item
      if (count($changes)) {
         $update_done = false;
         foreach (['Monitor', 'Peripheral', 'Phone', 'Printer'] as $t) {
            $crit = ['FIELDS'       => ['items_id'],
                     'itemtype'     => $t,
                     'computers_id' => $this->fields["id"],
                     'is_deleted'   => 0];
            $item      = new $t();
            foreach ($DB->request('glpi_computers_items', $crit) as $data) {
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


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Devices
         Item_devices::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Infocoms
         Infocom::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD volumes
         ComputerDisk::cloneComputer($this->input["_oldID"], $this->fields['id']);

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

         //Add KB links
         KnowbaseItem_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);
      }
   }


   function cleanDBonPurge() {

      $csv = new Computer_SoftwareVersion();
      $csv->cleanDBonItemDelete('Computer', $this->fields['id']);

      $csl = new Computer_SoftwareLicense();
      $csl->cleanDBonItemDelete('Computer', $this->fields['id']);

      $ip = new Item_Problem();
      $ip->cleanDBonItemDelete('Computer', $this->fields['id']);

      $ci = new Change_Item();
      $ci->cleanDBonItemDelete('Computer', $this->fields['id']);

      $ip = new Item_Project();
      $ip->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $ci = new Computer_Item();
      $ci->cleanDBonItemDelete('Computer', $this->fields['id']);

      Item_Devices::cleanItemDeviceDBOnItemDelete($this->getType(), $this->fields['id'],
                                                  (!empty($this->input['keep_devices'])));

      $disk = new ComputerDisk();
      $disk->cleanDBonItemDelete('Computer', $this->fields['id']);

      $vm = new ComputerVirtualMachine();
      $vm->cleanDBonItemDelete('Computer', $this->fields['id']);

      $antivirus = new ComputerAntivirus();
      $antivirus->cleanDBonItemDelete('Computer', $this->fields['id']);
   }


   /**
    * Print the computer form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown(array('value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_computer`"));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ComputerType::dropdown(array('value' => $this->fields["computertypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Manufacturer::dropdown(array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`'));

      echo "</td>";
      echo "<td>".__('Model')."</td>";
      echo "<td>";
      ComputerModel::dropdown(array('value' => $this->fields["computermodels_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      //TRANS: Number of the alternate username
      echo "<td>".__('Alternate username number')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'contact_num');
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'serial');
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'contact');
      echo "</td>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'otherserial', array('value' => $objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>";
      echo "<td>".__('Network')."</td>";
      echo "<td>";
      Network::dropdown(array('value' => $this->fields["networks_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Group::dropdown(array('value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`'));

      echo "</td>";

      // Display auto inventory informations
      $rowspan        = 4;

      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";

      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Domain')."</td>";
      echo "<td >";
      Domain::dropdown(array('value'  => $this->fields["domains_id"],
                             'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('UUID')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'uuid');
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Update Source')."</td>";
      echo "<td >";
      AutoUpdateSystem::dropdown(array('value' => $this->fields["autoupdatesystems_id"]));
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


   /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
   **/
   function getLinkedItems() {
      global $DB;

      $query = "SELECT `itemtype`, `items_id`
                FROM `glpi_computers_items`
                WHERE `computers_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($DB->request($query) as $data) {
         $tab[$data['itemtype']][$data['items_id']] = $data['items_id'];
      }
      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
    **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['Computer_Item'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']    = _x('button', 'Connect');
         $actions['Computer_SoftwareVersion'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'] = _x('button', 'Install');
         MassiveAction::getAddTransferList($actions);

         $kb_item = new KnowbaseItem();
         $kb_item->getEmpty();
         if ($kb_item->canViewItem()) {
            $actions['KnowbaseItem_Item'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'] = _x('button', 'Link knowledgebase article');
         }
      }

      return $actions;
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
         'condition'          => '`is_visible_computer`'
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => 'glpi_operatingsystems',
         'field'              => 'name',
         'name'               => __('Operating system'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '46',
         'table'              => 'glpi_operatingsystemversions',
         'field'              => 'name',
         'name'               => __('Version of the operating system'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '41',
         'table'              => 'glpi_operatingsystemservicepacks',
         'field'              => 'name',
         'name'               => __('Service pack'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => 'glpi_autoupdatesystems',
         'field'              => 'name',
         'name'               => __('Update Source'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => 'glpi_computers',
         'field'              => 'os_license_number',
         'name'               => __('Serial of the operating system'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => 'glpi_computers',
         'field'              => 'os_licenseid',
         'name'               => __('Product ID of the operating system'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => 'glpi_operatingsystemarchitectures',
         'field'              => 'name',
         'name'               => __('Operating system architecture'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '47',
         'table'              => 'glpi_computers',
         'field'              => 'uuid',
         'name'               => __('UUID'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '48',
         'table'              => 'glpi_computers',
         'field'              => 'os_kernel_version',
         'name'               => __('Kernel version of the operating system'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_computers',
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_computers',
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => 'glpi_computers',
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_computers',
         'field'              => 'contact',
         'name'               => __('Alternate username'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_computers',
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
         'condition'          => '`is_itemgroup`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => 'glpi_computers',
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => 'glpi_computers',
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_networks',
         'field'              => 'name',
         'name'               => __('Network Device'),
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

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::getSearchOptionsToAddNew(get_class($this)));

      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      $tab[] = [
          'id'                => 'periph',
          'name'              => _n('Component', 'Components', Session::getPluralNumber())
      ];

      $items_device_joinparams   = array('jointype'          => 'itemtype_item',
                                         'specific_itemtype' => 'Computer');

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_deviceprocessors',
         'field'              => 'designation',
         'name'               => __('Processor'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_deviceprocessors',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => 'glpi_items_deviceprocessors',
         'field'              => 'frequency',
         'name'               => __('Processor frequency'),
         'unit'               => 'MHz',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 100,
         'massiveaction'      => false,
         'joinparams'         => $items_device_joinparams,
         'computation'        => 'SUM(TABLE.`frequency`) / COUNT(TABLE.`id`)'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => 'glpi_devicememories',
         'field'              => 'designation',
         'name'               => __('Memory type'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicememories',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '35',
         'table'              => 'glpi_items_devicememories',
         'field'              => 'size',
         'unit'               => 'Mio',
         'name'               => __('Memory (Mio)'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 100,
         'massiveaction'      => false,
         'joinparams'         => $items_device_joinparams,
         'computation'        => '(SUM(TABLE.`size`) / COUNT(TABLE.`id`))
                                    * COUNT(DISTINCT TABLE.`id`)'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => 'glpi_devicenetworkcards',
         'field'              => 'designation',
         'name'               => __('Network interface'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicenetworkcards',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => 'glpi_items_devicenetworkcards',
         'field'              => 'mac',
         'name'               => __('MAC address'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => $items_device_joinparams
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => 'glpi_devicesoundcards',
         'field'              => 'designation',
         'name'               => __('Soundcard'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicesoundcards',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicegraphiccards',
         'field'              => 'designation',
         'name'               => __('Graphics card'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicegraphiccards',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => 'glpi_devicemotherboards',
         'field'              => 'designation',
         'name'               => __('System board'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicemotherboards',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => 'glpi_deviceharddrives',
         'field'              => 'designation',
         'name'               => __('Hard drive type'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_deviceharddrives',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '34',
         'table'              => 'glpi_items_deviceharddrives',
         'field'              => 'capacity',
         'name'               => __('Hard drive size'),
         'unit'               => 'Mio',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 1000,
         'massiveaction'      => false,
         'joinparams'         => $items_device_joinparams,
         'computation'        => '(SUM(TABLE.`capacity`) / COUNT(TABLE.`id`))
                                       * COUNT(DISTINCT TABLE.`id`)'
      ];

      $tab[] = [
         'id'                 => '39',
         'table'              => 'glpi_devicepowersupplies',
         'field'              => 'designation',
         'name'               => __('Power supply'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicepowersupplies',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '95',
         'table'              => 'glpi_devicepcis',
         'field'              => 'designation',
         'name'               => __('Other component'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicepcis',
               'joinparams'         => $items_device_joinparams
            ]
         ]
      ];

      $tab[] = [
          'id'                 => 'disk',
          'name'               => _n('Volume', 'Volumes', Session::getPluralNumber())
      ];

      $tab[] = [
         'id'                 => '156',
         'table'              => 'glpi_computerdisks',
         'field'              => 'name',
         'name'               => __('Volume'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '150',
         'table'              => 'glpi_computerdisks',
         'field'              => 'totalsize',
         'name'               => __('Global size (Mio)'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 1000,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '151',
         'table'              => 'glpi_computerdisks',
         'field'              => 'freesize',
         'name'               => __('Free size'),
         'forcegroupby'       => true,
         'datatype'           => 'number',
         'width'              => 1000,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '152',
         'table'              => 'glpi_computerdisks',
         'field'              => 'freepercent',
         'name'               => __('Free percentage'),
         'forcegroupby'       => true,
         'datatype'           => 'decimal',
         'width'              => 2,
         'computation'        => 'ROUND(100*TABLE.freesize/TABLE.totalsize)',
         'computationgroupby' => true,
         'unit'               => '%',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '153',
         'table'              => 'glpi_computerdisks',
         'field'              => 'mountpoint',
         'name'               => __('Mount point'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '154',
         'table'              => 'glpi_computerdisks',
         'field'              => 'device',
         'name'               => __('Partition'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '155',
         'table'              => 'glpi_filesystems',
         'field'              => 'name',
         'name'               => __('File system'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_computerdisks',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => 'virtualmachine',
         'name'               => _n('Virtual machine', 'Virtual machines', Session::getPluralNumber())
      ];

      $tab[] = [
         'id'                 => '160',
         'table'              => 'glpi_computervirtualmachines',
         'field'              => 'name',
         'name'               => __('Virtual machine'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '161',
         'table'              => 'glpi_virtualmachinestates',
         'field'              => 'name',
         'name'               => __('State of the virtual machine'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_computervirtualmachines',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '162',
         'table'              => 'glpi_virtualmachinesystems',
         'field'              => 'name',
         'name'               => __('Virtualization model'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_computervirtualmachines',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '163',
         'table'              => 'glpi_virtualmachinetypes',
         'field'              => 'name',
         'name'               => __('Virtualization system'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_computervirtualmachines',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '164',
         'table'              => 'glpi_computervirtualmachines',
         'field'              => 'vcpu',
         'name'               => __('Virtual machine processor number'),
         'datatype'           => 'number',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '165',
         'table'              => 'glpi_computervirtualmachines',
         'field'              => 'ram',
         'name'               => __('Memory of virtual machines'),
         'datatype'           => 'string',
         'unit'               => 'Mio',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '166',
         'table'              => 'glpi_computervirtualmachines',
         'field'              => 'uuid',
         'name'               => __('Virtual machine UUID'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab = array_merge($tab, ComputerAntivirus::getSearchOptionsToAddNew());

      return $tab;
   }

}
