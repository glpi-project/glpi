<?php
/*
 * @version $Id$
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
 * Printer Class
**/
class Printer  extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = array('Infocom', 'NetworkPort', 'ReservationItem');

   static $rightname                   = 'printer';
   protected $usenotepad               = true;



   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Printer', 'Printers', $nb);
   }


   /**
    * @see CommonDBTM::useDeletedToLockIfDynamic()
    *
    * @since version 0.84
   **/
   function useDeletedToLockIfDynamic() {
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Cartridge', $ong, $options);
      $this->addStandardTab('Item_Devices', $ong, $options);
      $this->addStandardTab('Computer_Item', $ong, $options);
      $this->addStandardTab('NetworkPort', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Can I change recusvive flag to false
    * check if there is "linked" object in another entity
    *
    * Overloaded from CommonDBTM
    *
    * @return booleen
   **/
   function canUnrecurs() {
      global $DB, $CFG_GLPI;

      $ID = $this->fields['id'];

      if (($ID < 0)
          || !$this->fields['is_recursive']) {
         return true;
      }

      if (!parent::canUnrecurs()) {
         return false;
      }

      $entities = getAncestorsOf("glpi_entities",$this->fields['entities_id']);
      $entities[] = $this->fields['entities_id'];

      // RELATION : printers -> _port -> _wire -> _port -> device

      // Evaluate connection in the 2 ways
      for ($tabend = array("networkports_id_1" => "networkports_id_2",
                           "networkports_id_2" => "networkports_id_1") ;
           list($enda, $endb) = each($tabend) ; ) {

         $sql = "SELECT `itemtype`,
                        GROUP_CONCAT(DISTINCT `items_id`) AS ids
                 FROM `glpi_networkports_networkports`,
                      `glpi_networkports`
                 WHERE `glpi_networkports_networkports`.`$endb` = `glpi_networkports`.`id`
                       AND `glpi_networkports_networkports`.`$enda`
                            IN (SELECT `id`
                                FROM `glpi_networkports`
                                WHERE `itemtype` = '".$this->getType()."'
                                      AND `items_id` = '$ID')
                 GROUP BY `itemtype`";
         $res = $DB->query($sql);

         if ($res) {
            while ($data = $DB->fetch_assoc($res)) {
               $itemtable = getTableForItemType($data["itemtype"]);
               if ($item = getItemForItemtype($data["itemtype"])) {
                  // For each itemtype which are entity dependant
                  if ($item->isEntityAssign()) {

                     if (countElementsInTable($itemtable, ['id' => $data["ids"],
                                              'NOT' => [ 'entities_id' => $entities]]) > 0) {
                        return false;
                     }
                  }
               }
            }
         }
      }
      return true;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      if (isset($input['init_pages_counter'])) {
         $input['init_pages_counter'] = intval($input['init_pages_counter']);
      } else {
         $input['init_pages_counter'] = 0;
      }
      if (isset($input['last_pages_counter'])) {
         $input['last_pages_counter'] = intval($input['last_pages_counter']);
      } else {
         $input['last_pages_counter'] = $input['init_pages_counter'];
      }

      return $input;
   }


   function prepareInputForUpdate($input) {

      if (isset($input['init_pages_counter'])) {
         $input['init_pages_counter'] = intval($input['init_pages_counter']);
      }
      if (isset($input['last_pages_counter'])) {
         $input['last_pages_counter'] = intval($input['last_pages_counter']);
      }

      return $input;
   }


   function post_addItem() {
      global $DB, $CFG_GLPI;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Devices
         Item_devices::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Infocoms
         Infocom::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Ports
         NetworkPort::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         Contract_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Documents
         Document_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Computers
         Computer_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

      }
   }


   function cleanDBonPurge() {
      global $DB;

      $ci = new Computer_Item();
      $ci->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $query = "UPDATE `glpi_cartridges`
                SET `printers_id` = NULL
                WHERE `printers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);

      $ip = new Item_Problem();
      $ip->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $ci = new Change_Item();
      $ci->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $ip = new Item_Project();
      $ip->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      Item_Devices::cleanItemDeviceDBOnItemDelete($this->getType(), $this->fields['id'],
                                                  (!empty($this->input['keep_devices'])));
   }


   /**
    * Print the printer form
    *
    * @param $ID        integer ID of the item
    * @param $options   array of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
     *@return boolean item found
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $target       = $this->getFormURL();
      $withtemplate = $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>\n";
      echo "<td>".__('Status')."</td>\n";
      echo "<td>";
      State::dropdown(array('value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_printer`"));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>\n";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".__('Type')."</td>\n";
      echo "<td>";
      PrinterType::dropdown(array('value' => $this->fields["printertypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>\n";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".__('Manufacturer')."</td>\n";
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
      echo "<td>".__('Model')."</td>\n";
      echo "<td>";
      PrinterModel::dropdown(array('value' => $this->fields["printermodels_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username number')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "contact_num");
      echo "</td>\n";
      echo "<td>".__('Serial number')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "contact");
      echo "</td>\n";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'otherserial', array('value' => $objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>\n";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>\n";
      echo "<td>".__('Management type')."</td>";
      echo "<td>";
      $globalitem = array();
      $globalitem['withtemplate'] = $withtemplate;
      $globalitem['value']        = $this->fields["is_global"];
      $globalitem['target']       = $target;

      if ($this->can($ID, UPDATE)) {
         $globalitem['management_restrict'] = $CFG_GLPI["printers_management_restrict"];
      }
      Dropdown::showGlobalSwitch($this->fields["id"], $globalitem);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group')."</td>\n";
      echo "<td>";
      Group::dropdown(array('value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`'));
      echo "</td>\n";
      echo "<td>".__('Network')."</td>\n";
      echo "<td>";
      Network::dropdown(array('value' => $this->fields["networks_id"]));
      echo "</td></tr>\n";

      // Display auto inventory informations
      $rowspan        = 5;
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Domain')."</td>\n";
      echo "<td>";
      Domain::dropdown(array('value'  => $this->fields["domains_id"],
                             'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>\n";
      echo "<td rowspan='$rowspan'>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Memory')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "memory_size");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Initial page counter')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "init_pages_counter", array('size' => 10));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Current counter of pages')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "last_pages_counter", array('size' => 10));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Port','Ports', Session::getPluralNumber())."</td>";
      echo "<td>\n<table>";
      // serial interface
      echo "<tr><td>".__('Serial')."</td><td width='80'>";
      Dropdown::showYesNo("have_serial", $this->fields["have_serial"]);
      echo "</td>";
      // parallel interface?
      echo "<td>".__('Parallel')."</td><td width='80'>";
      Dropdown::showYesNo("have_parallel", $this->fields["have_parallel"]);
      echo "</td></tr>";
      // USB interface?
      echo "<tr><td>".__('USB')."</td><td>";
      Dropdown::showYesNo("have_usb", $this->fields["have_usb"]);
      echo "</td>";
      // ethernet interface?
      echo "<td>".__('Ethernet')."</td><td>";
      Dropdown::showYesNo("have_ethernet",$this->fields["have_ethernet"]);
      echo "</td></tr>";
      // wifi ?
      echo "<tr><td>".__('Wifi')."</td><td colspan='3'>";
      Dropdown::showYesNo("have_wifi", $this->fields["have_wifi"]);
      echo "</td></tr></table>\n";
      echo "</td>";
      echo "</tr>";
      // Display auto inventory informations
      if (!empty($ID)
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

      $query = "SELECT 'Computer', `computers_id`
                FROM `glpi_computers_items`
                WHERE `itemtype` = '".$this->getType()."'
                      AND `items_id` = '" . $this->fields['id']."'";
      $tab = array();
      foreach ($DB->request($query) as $data) {
         $tab['Computer'][$data['computers_id']] = $data['computers_id'];
      }
      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $actions = parent::getSpecificMassiveActions($checkitem);
      if (static::canUpdate()) {
         Computer_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   function getSearchOptionsNew() {
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
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::getSearchOptionsToAddNew());

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_printertypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_printermodels',
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
         'condition'          => '`is_visible_printer`'
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
         'condition'          => '`is_itemgroup`',
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
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => $this->getTable(),
         'field'              => 'have_serial',
         'name'               => __('Serial'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => $this->getTable(),
         'field'              => 'have_parallel',
         'name'               => __('Parallel'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => $this->getTable(),
         'field'              => 'have_usb',
         'name'               => __('USB'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => $this->getTable(),
         'field'              => 'have_ethernet',
         'name'               => __('Ethernet'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '46',
         'table'              => $this->getTable(),
         'field'              => 'have_wifi',
         'name'               => __('Wifi'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'memory_size',
         'name'               => __('Memory'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'init_pages_counter',
         'name'               => __('Initial page counter'),
         'datatype'           => 'number',
         'nosearch'           => true
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'last_pages_counter',
         'name'               => __('Current counter of pages'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => '_virtual',
         'linkfield'          => '_virtual',
         'name'               => _n('Cartridge','Cartridges', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nosort'             => true
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_cartridges',
         'field'              => 'id',
         'name'               => __('Number of used cartridges'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_use` IS NOT NULL
                                                      AND NEWTABLE.`date_out` IS NULL'
         ]
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => 'glpi_cartridges',
         'field'              => 'id',
         'name'               => __('Number of worn cartridges'),
         'datatype'           => 'count',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => 'AND NEWTABLE.`date_out` IS NOT NULL'
         ]
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
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '82',
         'table'              => $this->getTable(),
         'field'              => 'is_global',
         'name'               => __('Global management'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::getSearchOptionsToAddNew(get_class($this)));

      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      return $tab;
   }


   /**
    * Add a printer. If already exist in dustbin restore it
    *
    * @param $name          the printer's name (need to be addslashes)
    * @param $manufacturer  the software's manufacturer (need to be addslashes)
    * @param $entity        the entity in which the software must be added
    * @param $comment       comment (default '')
   **/
   function addOrRestoreFromTrash($name, $manufacturer, $entity, $comment='') {
      global $DB;

      //Look for the software by his name in GLPI for a specific entity
      $query_search = "SELECT `glpi_printers`.`id`, `glpi_printers`.`is_deleted`
                       FROM `glpi_printers`
                       WHERE `name` = '$name'
                             AND `is_template` = '0'
                             AND `entities_id` = '$entity'";

      $result_search = $DB->query($query_search);

      if ($DB->numrows($result_search) > 0) {
         //Printer already exists for this entity, get his ID
         $data = $DB->fetch_assoc($result_search);
         $ID   = $data["id"];

         // restore software
         if ($data['is_deleted']) {
            $this->removeFromTrash($ID);
         }

      } else {
         $ID = 0;
      }

      if (!$ID) {
         $ID = $this->addPrinter($name, $manufacturer, $entity, $comment);
      }
      return $ID;
   }


   /**
    * Create a new printer
    *
    * @param $name         the printer's name (need to be addslashes)
    * @param $manufacturer the printer's manufacturer (need to be addslashes)
    * @param $entity       the entity in which the printer must be added
    * @param $comment      (default '')
    *
    * @return the printer's ID
   **/
   function addPrinter($name, $manufacturer, $entity, $comment = '') {
      global $DB, $CFG_GLPI;

      $manufacturer_id = 0;
      if ($manufacturer != '') {
         $manufacturer_id = Dropdown::importExternal('Manufacturer', $manufacturer);
      }

      //If there's a printer in a parent entity with the same name and manufacturer
      $sql = "SELECT `id`
              FROM `glpi_printers`
              WHERE `manufacturers_id` = '$manufacturer_id'
                    AND `name` = '$name' " .
                    getEntitiesRestrictRequest('AND', 'glpi_printers', 'entities_id', $entity,
                                               true);

      $res_printer = $DB->query($sql);
      if ($printer = $DB->fetch_assoc($res_printer)) {
         $id = $printer["id"];
      } else {
         $input["name"]             = $name;
         $input["manufacturers_id"] = $manufacturer_id;
         $input["entities_id"]      = $entity;

         $id = $this->add($input);
      }
      return $id;
   }


   /**
    * Restore a software from dustbin
    *
    * @param $ID  the ID of the software to put in dustbin
    *
    * @return boolean (success)
   **/
   function removeFromTrash($ID) {
      return $this->restore(array("id" => $ID));
   }

}
