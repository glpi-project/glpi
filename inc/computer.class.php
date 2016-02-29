<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
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
   var $devices                        = array();

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
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('ComputerDisk', $ong, $options)
         ->addStandardTab('Computer_SoftwareVersion', $ong, $options)
         ->addStandardTab('Computer_Item', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('ComputerVirtualMachine', $ong, $options)
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

      for ($i=0 ; $i<count($this->updates) ; $i++) {
         // Update contact of attached items
         if ((($this->updates[$i] == "contact") || ($this->updates[$i] == "contact_num"))
             && $CFG_GLPI["is_contact_autoupdate"]) {

            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');

            $update_done = false;
            $updates3[0] = "contact";
            $updates3[1] = "contact_num";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'
                               AND NOT `is_deleted`";
               if ($result = $DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item      = new $t();
                  if ($resultnum > 0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('contact') != $this->fields['contact']
                               || ($item->getField('contact_num') != $this->fields['contact_num'])) {

                              $tmp["id"]          = $item->getField('id');
                              $tmp['contact']     = $this->fields['contact'];
                              $tmp['contact_num'] = $this->fields['contact_num'];
                              $item->update($tmp);
                              $update_done        = true;
                           }
                        }
                     }
                  }
               }
            }

            if ($update_done) {
               Session::addMessageAfterRedirect(
                  __('Alternate username updated. The connected items have been updated using this alternate username.'),
                  true);
            }
         }

         // Update users and groups of attached items
         if ((($this->updates[$i] == "users_id")
              && ($this->fields["users_id"] != 0)
              && $CFG_GLPI["is_user_autoupdate"])
             || (($this->updates[$i] == "groups_id")
                 && ($this->fields["groups_id"] != 0)
                 && $CFG_GLPI["is_group_autoupdate"])) {

            $items = array('Monitor', 'Peripheral', 'Phone', 'Printer');

            $update_done = false;
            $updates4[0] = "users_id";
            $updates4[1] = "groups_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'
                               AND NOT `is_deleted`";

               if ($result = $DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item      = new $t();
                  if ($resultnum > 0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if (($item->getField('users_id') != $this->fields["users_id"])
                               || ($item->getField('groups_id') != $this->fields["groups_id"])) {

                              $tmp["id"] = $item->getField('id');

                              if ($CFG_GLPI["is_user_autoupdate"]) {
                                 $tmp["users_id"] = $this->fields["users_id"];
                              }
                              if ($CFG_GLPI["is_group_autoupdate"]) {
                                 $tmp["groups_id"] = $this->fields["groups_id"];
                              }
                              $item->update($tmp);
                              $update_done = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               Session::addMessageAfterRedirect(
                  __('User or group updated. The connected items have been moved in the same values.'),
                  true);
            }
         }

         // Update state of attached items
         if (($this->updates[$i] == "states_id")
             && ($CFG_GLPI["state_autoupdate_mode"] < 0)) {
            $items       = array('Monitor', 'Peripheral', 'Phone', 'Printer');
            $update_done = false;

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'
                               AND NOT `is_deleted`";

               if ($result = $DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item      = new $t();

                  if ($resultnum > 0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('states_id') != $this->fields["states_id"]) {
                              $tmp["id"]        = $item->getField('id');
                              $tmp["states_id"] = $this->fields["states_id"];
                              $item->update($tmp);
                              $update_done      = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               Session::addMessageAfterRedirect(
                     __('Status updated. The connected items have been updated using this status.'),
                     true);
            }
         }

         // Update loction of attached items
         if (($this->updates[$i] == "locations_id")
             && ($this->fields["locations_id"] != 0)
             && $CFG_GLPI["is_location_autoupdate"]) {

            $items       = array('Monitor', 'Peripheral', 'Phone', 'Printer');
            $update_done = false;
            $updates2[0] = "locations_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id` = '".$this->fields["id"]."'
                               AND `itemtype` = '".$t."'
                               AND NOT `is_deleted`";

               if ($result = $DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item      = new $t();

                  if ($resultnum > 0) {
                     for ($j=0 ; $j<$resultnum ; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('locations_id') != $this->fields["locations_id"]) {
                              $tmp["id"]           = $item->getField('id');
                              $tmp["locations_id"] = $this->fields["locations_id"];
                              $item->update($tmp);
                              $update_done         = true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
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
      echo "<td>".sprintf(__('%1$s%2$s'),__('Name'),
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
      Html::autocompletionTextField($this,'contact_num');
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this,'serial');
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'contact');
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
      $rowspan        = 10;
      $inventory_show = false;

      if (!empty($ID)
          && Plugin::haveImport()
          && $this->fields["is_dynamic"]) {
         $inventory_show = true;
         $rowspan       -= 4;
      }

      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";
      echo "<textarea style='width:95%' rows='".($rowspan+3)."' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Domain')."</td>";
      echo "<td >";
      Domain::dropdown(array('value'  => $this->fields["domains_id"],
                             'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Operating system')."</td>";
      echo "<td>";
      OperatingSystem::dropdown(array('value' => $this->fields["operatingsystems_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Service pack')."</td>";
      echo "<td >";
      OperatingSystemServicePack::dropdown(array('value'
                                                 => $this->fields["operatingsystemservicepacks_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Version of the operating system')."</td>";
      echo "<td >";
      OperatingSystemVersion::dropdown(array('value' => $this->fields["operatingsystemversions_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Product ID of the operating system')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'os_licenseid');
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Serial of the operating system')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'os_license_number');
      echo "</td>";

      if ($inventory_show) {
         echo "<td rowspan='4' colspan='2'>";
         Plugin::doHook("autoinventory_information", $this);
         echo "</td>";
      }
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('UUID')."</td>";
      echo "<td >";
      Html::autocompletionTextField($this, 'uuid');
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ((!isset($options['withtemplate']) || ($options['withtemplate'] == 0))
          && !empty($this->fields['template_name'])) {
         echo "<span class='small_space'>";
         printf(__('Created from the template %s'), $this->fields['template_name']);
         echo "</span>";
      } else {
         echo "&nbsp;";
      }
      echo "</td><td>";
      if (isset($options['withtemplate']) && $options['withtemplate']) {
         //TRANS: %s is the datetime of insertion
         printf(__('Created on %s'), Html::convDateTime($_SESSION["glpi_currenttime"]));
      } else {
         //TRANS: %s is the datetime of update
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Update Source')."</td>";
      echo "<td >";
      AutoUpdateSystem::dropdown(array('value' => $this->fields["autoupdatesystems_id"]));
      echo "</td></tr>";

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
      }

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   function getSearchOptions() {
      global $CFG_GLPI;

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false; // implicit key==1

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false; // implicit field is id
      $tab[2]['datatype']        = 'number';

      $tab += Location::getSearchOptionsToAdd();

      $tab[4]['table']           = 'glpi_computertypes';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Type');
      $tab[4]['datatype']        = 'dropdown';

      $tab[40]['table']          = 'glpi_computermodels';
      $tab[40]['field']          = 'name';
      $tab[40]['name']           = __('Model');
      $tab[40]['datatype']       = 'dropdown';

      $tab[31]['table']          = 'glpi_states';
      $tab[31]['field']          = 'completename';
      $tab[31]['name']           = __('Status');
      $tab[31]['datatype']       = 'dropdown';
      $tab[31]['condition']      = "`is_visible_computer`";

      $tab[45]['table']          = 'glpi_operatingsystems';
      $tab[45]['field']          = 'name';
      $tab[45]['name']           = __('Operating system');
      $tab[45]['datatype']       = 'dropdown';

      $tab[46]['table']          = 'glpi_operatingsystemversions';
      $tab[46]['field']          = 'name';
      $tab[46]['name']           = __('Version of the operating system');
      $tab[46]['datatype']       = 'dropdown';

      $tab[41]['table']          = 'glpi_operatingsystemservicepacks';
      $tab[41]['field']          = 'name';
      $tab[41]['name']           = __('Service pack');
      $tab[41]['datatype']       = 'dropdown';

      $tab[42]['table']          = 'glpi_autoupdatesystems';
      $tab[42]['field']          = 'name';
      $tab[42]['name']           = __('Update Source');
      $tab[42]['datatype']       = 'dropdown';

      $tab[43]['table']          = $this->getTable();
      $tab[43]['field']          = 'os_license_number';
      $tab[43]['name']           = __('Serial of the operating system');
      $tab[43]['datatype']       = 'string';

      $tab[44]['table']          = $this->getTable();
      $tab[44]['field']          = 'os_licenseid';
      $tab[44]['name']           = __('Product ID of the operating system');
      $tab[44]['datatype']       = 'string';

      $tab[47]['table']          = $this->getTable();
      $tab[47]['field']          = 'uuid';
      $tab[47]['name']           = __('UUID');
      $tab[47]['datatype']       = 'string';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'serial';
      $tab[5]['name']            = __('Serial number');
      $tab[5]['datatype']        = 'string';

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'otherserial';
      $tab[6]['name']            = __('Inventory number');
      $tab[6]['datatype']        = 'string';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'contact';
      $tab[7]['name']            = __('Alternate username');
      $tab[7]['datatype']        = 'string';

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'contact_num';
      $tab[8]['name']            = __('Alternate username number');
      $tab[8]['datatype']        = 'string';

      $tab[70]['table']          = 'glpi_users';
      $tab[70]['field']          = 'name';
      $tab[70]['name']           = __('User');
      $tab[70]['datatype']       = 'dropdown';
      $tab[70]['right']          = 'all';

      $tab[71]['table']          = 'glpi_groups';
      $tab[71]['field']          = 'completename';
      $tab[71]['name']           = __('Group');
      $tab[71]['condition']      = '`is_itemgroup`';
      $tab[71]['datatype']       = 'dropdown';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[32]['table']          = 'glpi_networks';
      $tab[32]['field']          = 'name';
      $tab[32]['name']           = __('Network');
      $tab[32]['datatype']       = 'dropdown';

      $tab[33]['table']          = 'glpi_domains';
      $tab[33]['field']          = 'name';
      $tab[33]['name']           = __('Domain');
      $tab[33]['datatype']       = 'dropdown';

      $tab[23]['table']          = 'glpi_manufacturers';
      $tab[23]['field']          = 'name';
      $tab[23]['name']           = __('Manufacturer');
      $tab[23]['datatype']       = 'dropdown';

      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id_tech';
      $tab[24]['name']           = __('Technician in charge of the hardware');
      $tab[24]['datatype']       = 'dropdown';
      $tab[24]['right']          = 'own_ticket';

      $tab[49]['table']          = 'glpi_groups';
      $tab[49]['field']          = 'completename';
      $tab[49]['linkfield']      = 'groups_id_tech';
      $tab[49]['name']           = __('Group in charge of the hardware');
      $tab[49]['condition']      = '`is_assign`';
      $tab[49]['datatype']       = 'dropdown';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';

      $tab += Notepad::getSearchOptionsToAdd();

      $tab['periph']             = _n('Component', 'Components', Session::getPluralNumber());

      $items_device_joinparams   = array('jointype'          => 'itemtype_item',
                                         'specific_itemtype' => 'Computer');

      $tab[17]['table']          = 'glpi_deviceprocessors';
      $tab[17]['field']          = 'designation';
      $tab[17]['name']           = __('Processor');
      $tab[17]['forcegroupby']   = true;
      $tab[17]['usehaving']      = true;
      $tab[17]['massiveaction']  = false;
      $tab[17]['datatype']       = 'string';
      $tab[17]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_deviceprocessors',
                                                   'joinparams' => $items_device_joinparams));

      $tab[36]['table']          = 'glpi_items_deviceprocessors';
      $tab[36]['field']          = 'frequency';
      $tab[36]['name']           = __('Processor frequency');
      $tab[36]['unit']           = __('MHz');
      $tab[36]['forcegroupby']   = true;
      $tab[36]['usehaving']      = true;
      $tab[36]['datatype']       = 'number';
      $tab[36]['width']          = 100;
      $tab[36]['massiveaction']  = false;
      $tab[36]['joinparams']     = $items_device_joinparams;
      $tab[36]['computation']    = "SUM(TABLE.`frequency`) / COUNT(TABLE.`id`)";

      $tab[10]['table']          = 'glpi_devicememories';
      $tab[10]['field']          = 'designation';
      $tab[10]['name']           = __('Memory type');
      $tab[10]['forcegroupby']   = true;
      $tab[10]['usehaving']      = true;
      $tab[10]['massiveaction']  = false;
      $tab[10]['datatype']       = 'string';
      $tab[10]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicememories',
                                                   'joinparams' => $items_device_joinparams));

      $tab[35]['table']          = 'glpi_items_devicememories';
      $tab[35]['field']          = 'size';
      $tab[35]['unit']           = __('Mio');
      $tab[35]['name']           = sprintf(__('%1$s (%2$s)'),__('Memory'),__('Mio'));
      $tab[35]['forcegroupby']   = true;
      $tab[35]['usehaving']      = true;
      $tab[35]['datatype']       = 'number';
      $tab[35]['width']          = 100;
      $tab[35]['massiveaction']  = false;
      $tab[35]['joinparams']     = $items_device_joinparams;
      $tab[35]['computation']    = "(SUM(TABLE.`size`) / COUNT(TABLE.`id`))
                                    * COUNT(DISTINCT TABLE.`id`)";


      $tab[11]['table']          = 'glpi_devicenetworkcards';
      $tab[11]['field']          = 'designation';
      $tab[11]['name']           = _n('Network interface', 'Network interfaces', 1);
      $tab[11]['forcegroupby']   = true;
      $tab[11]['massiveaction']  = false;
      $tab[11]['datatype']       = 'string';
      $tab[11]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicenetworkcards',
                                                   'joinparams' => $items_device_joinparams));

      $tab[20]['table']          = 'glpi_items_devicenetworkcards';
      $tab[20]['field']          = 'mac';
      $tab[20]['name']           = __('MAC address');
      $tab[20]['forcegroupby']   = true;
      $tab[20]['massiveaction']  = false;
      $tab[20]['datatype']       = 'string';
      $tab[20]['joinparams']     = $items_device_joinparams;

      $tab[12]['table']          = 'glpi_devicesoundcards';
      $tab[12]['field']          = 'designation';
      $tab[12]['name']           = __('Soundcard');
      $tab[12]['forcegroupby']   = true;
      $tab[12]['massiveaction']  = false;
      $tab[12]['datatype']       = 'string';
      $tab[12]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicesoundcards',
                                                   'joinparams' => $items_device_joinparams));

      $tab[13]['table']          = 'glpi_devicegraphiccards';
      $tab[13]['field']          = 'designation';
      $tab[13]['name']           = __('Graphics card');
      $tab[13]['forcegroupby']   = true;
      $tab[13]['massiveaction']  = false;
      $tab[13]['datatype']       = 'string';
      $tab[13]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicegraphiccards',
                                                   'joinparams' => $items_device_joinparams));

      $tab[14]['table']          = 'glpi_devicemotherboards';
      $tab[14]['field']          = 'designation';
      $tab[14]['name']           = __('System board');
      $tab[14]['forcegroupby']   = true;
      $tab[14]['massiveaction']  = false;
      $tab[14]['datatype']       = 'string';
      $tab[14]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicemotherboards',
                                                   'joinparams' => $items_device_joinparams));


      $tab[15]['table']          = 'glpi_deviceharddrives';
      $tab[15]['field']          = 'designation';
      $tab[15]['name']           = __('Hard drive type');
      $tab[15]['forcegroupby']   = true;
      $tab[15]['usehaving']      = true;
      $tab[15]['massiveaction']  = false;
      $tab[15]['datatype']       = 'string';
      $tab[15]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_deviceharddrives',
                                                   'joinparams' => $items_device_joinparams));

      $tab[34]['table']          = 'glpi_items_deviceharddrives';
      $tab[34]['field']          = 'capacity';
      $tab[34]['name']           = __('Hard drive size');
      $tab[34]['unit']           = __('Mio');
      $tab[34]['forcegroupby']   = true;
      $tab[34]['usehaving']      = true;
      $tab[34]['datatype']       = 'number';
      $tab[34]['width']          = 1000;
      $tab[34]['massiveaction']  = false;
      $tab[34]['joinparams']     = $items_device_joinparams;
      $tab[34]['computation']    = "(SUM(TABLE.`capacity`) / COUNT(TABLE.`id`))
                                       * COUNT(DISTINCT TABLE.`id`)";

      $tab[39]['table']          = 'glpi_devicepowersupplies';
      $tab[39]['field']          = 'designation';
      $tab[39]['name']           = __('Power supply');
      $tab[39]['forcegroupby']   = true;
      $tab[39]['usehaving']      = true;
      $tab[39]['massiveaction']  = false;
      $tab[39]['datatype']       = 'string';
      $tab[39]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicepowersupplies',
                                                   'joinparams' => $items_device_joinparams));

      $tab[95]['table']          = 'glpi_devicepcis';
      $tab[95]['field']          = 'designation';
      $tab[95]['name']           = __('Other component');
      $tab[95]['forcegroupby']   = true;
      $tab[95]['usehaving']      = true;
      $tab[95]['massiveaction']  = false;
      $tab[95]['datatype']       = 'string';
      $tab[95]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_items_devicepcis',
                                                   'joinparams' => $items_device_joinparams));

      $tab['disk']               = _n('Volume', 'Volumes', Session::getPluralNumber());

      $tab[156]['table']         = 'glpi_computerdisks';
      $tab[156]['field']         = 'name';
      $tab[156]['name']          = __('Volume');
      $tab[156]['forcegroupby']  = true;
      $tab[156]['massiveaction'] = false;
      $tab[156]['datatype']      = 'dropdown';
      $tab[156]['joinparams']    = array('jointype' => 'child');

      $tab[150]['table']         = 'glpi_computerdisks';
      $tab[150]['field']         = 'totalsize';
      $tab[150]['name']          = sprintf(__('%1$s (%2$s)'), __('Global size'), __('Mio'));
      $tab[150]['forcegroupby']  = true;
      $tab[150]['usehaving']     = true;
      $tab[150]['datatype']      = 'number';
      $tab[150]['width']         = 1000;
      $tab[150]['massiveaction'] = false;
      $tab[150]['joinparams']    = array('jointype' => 'child');

      $tab[151]['table']         = 'glpi_computerdisks';
      $tab[151]['field']         = 'freesize';
      $tab[151]['name']          = __('Free size');
      $tab[151]['forcegroupby']  = true;
      $tab[151]['datatype']      = 'number';
      $tab[151]['width']         = 1000;
      $tab[151]['massiveaction'] = false;
      $tab[151]['joinparams']    = array('jointype' => 'child');

      $tab[152]['table']         = 'glpi_computerdisks';
      $tab[152]['field']         = 'freepercent';
      $tab[152]['name']          = __('Free percentage');
      $tab[152]['forcegroupby']  = true;
      $tab[152]['datatype']      = 'decimal';
      $tab[152]['width']         = 2;
      $tab[152]['computation']   = "ROUND(100*TABLE.freesize/TABLE.totalsize)";
      $tab[152]['unit']          = '%';
      $tab[152]['massiveaction'] = false;
      $tab[152]['joinparams']    = array('jointype' => 'child');

      $tab[153]['table']         = 'glpi_computerdisks';
      $tab[153]['field']         = 'mountpoint';
      $tab[153]['name']          = __('Mount point');
      $tab[153]['forcegroupby']  = true;
      $tab[153]['massiveaction'] = false;
      $tab[153]['datatype']      = 'string';
      $tab[153]['joinparams']    = array('jointype' => 'child');

      $tab[154]['table']         = 'glpi_computerdisks';
      $tab[154]['field']         = 'device';
      $tab[154]['name']          = __('Partition');
      $tab[154]['forcegroupby']  = true;
      $tab[154]['massiveaction'] = false;
      $tab[154]['datatype']      = 'string';
      $tab[154]['joinparams']    = array('jointype' => 'child');

      $tab[155]['table']         = 'glpi_filesystems';
      $tab[155]['field']         = 'name';
      $tab[155]['name']          = __('File system');
      $tab[155]['forcegroupby']  = true;
      $tab[155]['massiveaction'] = false;
      $tab[155]['datatype']      = 'dropdown';
      $tab[155]['joinparams']    = array('beforejoin'
                                         => array('table'      => 'glpi_computerdisks',
                                                  'joinparams' => array('jointype' => 'child')));

      $tab['virtualmachine']     = _n('Virtual machine', 'Virtual machines', Session::getPluralNumber());

      $tab[160]['table']         = 'glpi_computervirtualmachines';
      $tab[160]['field']         = 'name';
      $tab[160]['name']          = __('Virtual machine');
      $tab[160]['forcegroupby']  = true;
      $tab[160]['massiveaction'] = false;
      $tab[160]['datatype']      = 'dropdown';
      $tab[160]['joinparams']    = array('jointype' => 'child');

      $tab[161]['table']         = 'glpi_virtualmachinestates';
      $tab[161]['field']         = 'name';
      $tab[161]['name']          = __('State of the virtual machine');
      $tab[161]['forcegroupby']  = true;
      $tab[161]['massiveaction'] = false;
      $tab[161]['datatype']      = 'dropdown';
      $tab[161]['joinparams']    = array('beforejoin'
                                          => array('table'      => 'glpi_computervirtualmachines',
                                                   'joinparams' => array('jointype' => 'child')));

      $tab[162]['table']         = 'glpi_virtualmachinesystems';
      $tab[162]['field']         = 'name';
      $tab[162]['name']          = __('Virtualization model');
      $tab[162]['forcegroupby']  = true;
      $tab[162]['massiveaction'] = false;
      $tab[162]['datatype']      = 'dropdown';
      $tab[162]['joinparams']    = array('beforejoin'
                                          => array('table'      => 'glpi_computervirtualmachines',
                                                   'joinparams' => array('jointype' => 'child')));

      $tab[163]['table']         = 'glpi_virtualmachinetypes';
      $tab[163]['field']         = 'name';
      $tab[163]['name']          = __('Virtualization system');
      $tab[163]['datatype']      = 'dropdown';
      $tab[163]['forcegroupby']  = true;
      $tab[163]['massiveaction'] = false;
      $tab[163]['joinparams']    = array('beforejoin'
                                          => array('table'      => 'glpi_computervirtualmachines',
                                                   'joinparams' => array('jointype' => 'child')));

      $tab[164]['table']         = 'glpi_computervirtualmachines';
      $tab[164]['field']         = 'vcpu';
      $tab[164]['name']          = __('Virtual machine processor number');
      $tab[164]['datatype']      = 'number';
      $tab[164]['forcegroupby']  = true;
      $tab[164]['massiveaction'] = false;
      $tab[164]['joinparams']    = array('jointype' => 'child');

      $tab[165]['table']         = 'glpi_computervirtualmachines';
      $tab[165]['field']         = 'ram';
      $tab[165]['name']          = __('Memory of virtual machines');
      $tab[165]['datatype']      = 'number';
      $tab[165]['unit']          = __('Mio');
      $tab[165]['forcegroupby']  = true;
      $tab[165]['massiveaction'] = false;
      $tab[165]['joinparams']    = array('jointype' => 'child');

      $tab[166]['table']         = 'glpi_computervirtualmachines';
      $tab[166]['field']         = 'uuid';
      $tab[166]['name']          = __('Virtual machine UUID');
      $tab[165]['datatype']      = 'string';
      $tab[166]['forcegroupby']  = true;
      $tab[166]['massiveaction'] = false;
      $tab[166]['joinparams']    = array('jointype' => 'child');

      return $tab;
   }

}
?>
