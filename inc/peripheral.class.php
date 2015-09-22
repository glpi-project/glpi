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
 * Peripheral Class
**/
class Peripheral extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = array('Infocom', 'NetworkPort', 'ReservationItem');

   static $rightname                   = 'peripheral';
   protected $usenotepad               = true;


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Device', 'Devices', $nb);
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


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);
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
    * Print the peripheral form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $target       = $this->getFormURL();
      $withtemplate = $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":""));
      echo "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "name", array('value' => $objectName));
      echo "</td>\n";
      echo "<td>".__('Status')."</td>\n";
      echo "<td>";
      State::dropdown(array('value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_peripheral`"));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>\n";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".__('Type')."</td>\n";
      echo "<td>";
      PeripheralType::dropdown(array('value' => $this->fields["peripheraltypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>\n";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
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
      PeripheralModel::dropdown(array('value' => $this->fields["peripheralmodels_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username number')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "contact_num");
      echo "</td>";
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
                 (        isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, "otherserial", array('value' => $objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>\n";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>\n";
      echo "<td>".__('Management type')."</td>\n";
      echo "<td>";
      Dropdown::showGlobalSwitch($this->fields["id"],
                                 array('withtemplate' => $withtemplate,
                                       'value'        => $this->fields["is_global"],
                                       'management_restrict'
                                                      => $CFG_GLPI["peripherals_management_restrict"],
                                       'target'       => $target));
      echo "</td></tr>\n";

      // Display auto inventory informations
      $rowspan        = 3;
      $inventory_show = false;

       if (!empty($ID)
           && $this->fields["is_dynamic"]) {
          $inventory_show = true;
          $rowspan       -= 1;
       }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group')."</td>\n";
      echo "<td>";
      Group::dropdown(array('value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`'));
      echo "</td>\n";
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>\n";
      echo "<td rowspan='$rowspan'>
            <textarea cols='45' rows='".($rowspan+3)."' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Brand')."</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "brand");
      echo "</td></tr>\n";

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
         //TRANS: %s is the datetime of insertion
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td>";
      if ($inventory_show) {
         echo "<td rowspan='1'>".__('Automatic inventory')."</td>";
         echo "<td rowspan='1'>";
         Plugin::doHook("autoinventory_information", $this);
         echo "</td>";
      }
      echo "</tr>\n";

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


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab+=Location::getSearchOptionsToAdd();

      $tab[4]['table']           = 'glpi_peripheraltypes';
      $tab[4]['field']           = 'name';
      $tab[4]['name']            = __('Type');
      $tab[4]['datatype']        = 'dropdown';

      $tab[40]['table']          = 'glpi_peripheralmodels';
      $tab[40]['field']          = 'name';
      $tab[40]['name']           = __('Model');
      $tab[40]['datatype']       = 'dropdown';

      $tab[31]['table']          = 'glpi_states';
      $tab[31]['field']          = 'completename';
      $tab[31]['name']           = __('Status');
      $tab[31]['datatype']        = 'dropdown';
      $tab[31]['condition']      = "`is_visible_peripheral`";

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'serial';
      $tab[5]['name']            = __('Serial number');
      $tab[5]['datatype']        = 'string';

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'otherserial';
      $tab[6]['name']            = __('Inventory number');
      $tab[6]['datatype']        = 'string';

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
      $tab[71]['datatype']        = 'dropdown';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[11]['table']          = $this->getTable();
      $tab[11]['field']          = 'brand';
      $tab[11]['name']           = __('Brand');
      $tab[11]['datatype']       = 'string';

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
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[82]['table']          = $this->getTable();
      $tab[82]['field']          = 'is_global';
      $tab[82]['name']           = __('Global management');
      $tab[82]['datatype']       = 'bool';
      $tab[82]['massiveaction']  = false;

      $tab += Notepad::getSearchOptionsToAdd();

      return $tab;
   }

}
?>
