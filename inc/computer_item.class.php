<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
class Computer_Item extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1          = 'Computer';
   static public $items_id_1          = 'computers_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;



   /**
    * @since version 0.84
    *
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Count connection for an item
    *
    * @param $item   CommonDBTM object
    *
    * @return integer: count
   **/
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_computers_items',
                                  "`itemtype` = '".$item->getType()."'
                                      AND `items_id` ='".$item->getField('id')."'
                                      AND `is_deleted` = '0'");
   }


   /**
    * Count connection for a Computer
    *
    * @param $comp   Computer object
    *
    * @return integer: count
   **/
   static function countForComputer(Computer $comp) {

      return countElementsInTable('glpi_computers_items',
                                  "`computers_id` ='".$comp->getField('id')."'
                                      AND `is_deleted`='0'");
   }


   /**
    * Count connection for a Computer and an itemtype
    *
    * @since version 0.84
    *
    * @param $comp   Computer object
    * @param $item   CommonDBTM object
    *
    * @return integer: count
   **/
   static function countForAll(Computer $comp, CommonDBTM $item) {

      return countElementsInTable('glpi_computers_items',
                                  "`computers_id` ='".$comp->getField('id')."'
                                   AND `itemtype` = '".$item->getType()."'
                                   AND `items_id` ='".$item->getField('id')."'");
   }


   /**
    * Prepare input datas for adding the relation
    *
    * Overloaded to check is Disconnect needed (during OCS sync)
    * and to manage autoupdate feature
    *
    * @param $input array of datas used to add the item
    *
    * @return the modified $input array
    *
   **/
   function prepareInputForAdd($input) {
      global $DB, $CFG_GLPI;

      $item = static::getItemFromArray(static::$itemtype_2, static::$items_id_2, $input);
      if (!($item instanceof CommonDBTM)
          || (($item->getField('is_global') == 0)
              && ($this->countForItem($item) > 0))) {
         return false;
      }

      $comp = static::getItemFromArray(static::$itemtype_1, static::$items_id_1, $input);
      if (!($item instanceof CommonDBTM)
          || (self::countForAll($comp, $item) >0)) {
         // no duplicates
         return false;
      }

      if (!$item->getField('is_global') ) {
         // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
         $updates = array();

         if ($CFG_GLPI["is_location_autoupdate"]
             && ($comp->fields['locations_id'] != $item->getField('locations_id'))) {

            $updates['locations_id'] = addslashes($comp->fields['locations_id']);
            Session::addMessageAfterRedirect(
                  __('Location updated. The connected items have been moved in the same location.'),
                                             true);
         }
         if (($CFG_GLPI["is_user_autoupdate"]
              && ($comp->fields['users_id'] != $item->getField('users_id')))
             || ($CFG_GLPI["is_group_autoupdate"]
                 && ($comp->fields['groups_id'] != $item->getField('groups_id')))) {

            if ($CFG_GLPI["is_user_autoupdate"]) {
               $updates['users_id'] = $comp->fields['users_id'];
            }
            if ($CFG_GLPI["is_group_autoupdate"]) {
               $updates['groups_id'] = $comp->fields['groups_id'];
            }
            Session::addMessageAfterRedirect(
               __('User or group updated. The connected items have been moved in the same values.'),
                                             true);
         }

         if ($CFG_GLPI["is_contact_autoupdate"]
             && (($comp->fields['contact'] != $item->getField('contact'))
                 || ($comp->fields['contact_num'] != $item->getField('contact_num')))) {

            $updates['contact']     = addslashes($comp->fields['contact']);
            $updates['contact_num'] = addslashes($comp->fields['contact_num']);
            Session::addMessageAfterRedirect(
               __('Alternate username updated. The connected items have been updated using this alternate username.'),
                                             true);
         }

         if (($CFG_GLPI["state_autoupdate_mode"] < 0)
             && ($comp->fields['states_id'] != $item->getField('states_id'))) {

            $updates['states_id'] = $comp->fields['states_id'];
            Session::addMessageAfterRedirect(
                     __('Status updated. The connected items have been updated using this status.'),
                                             true);
         }

         if (($CFG_GLPI["state_autoupdate_mode"] > 0)
             && ($item->getField('states_id') != $CFG_GLPI["state_autoupdate_mode"])) {

            $updates['states_id'] = $CFG_GLPI["state_autoupdate_mode"];
         }

         if (count($updates)) {
            $updates['id'] = $input['items_id'];
            $item->update($updates);
         }
      }
      return parent::prepareInputForAdd($input);
   }


   /**
    * Actions done when item is deleted from the database
    * Overloaded to manage autoupdate feature
    *
    *@return nothing
   **/
   function cleanDBonPurge() {
      global $CFG_GLPI;

      if (!isset($this->input['_no_auto_action'])) {
         //Get the computer name
         $computer = new Computer();
         $computer->getFromDB($this->fields['computers_id']);

         //Get device fields
         if ($device = getItemForItemtype($this->fields['itemtype'])) {
            if ($device->getFromDB($this->fields['items_id'])) {

               if (!$device->getField('is_global')) {
                  $updates = array();
                  if ($CFG_GLPI["is_location_autoclean"] && $device->isField('locations_id')) {
                     $updates['locations_id'] = 0;
                  }
                  if ($CFG_GLPI["is_user_autoclean"] && $device->isField('users_id')) {
                     $updates['users_id'] = 0;
                  }
                  if ($CFG_GLPI["is_group_autoclean"] && $device->isField('groups_id')) {
                     $updates['groups_id'] = 0;
                  }
                  if ($CFG_GLPI["is_contact_autoclean"] && $device->isField('contact')) {
                     $updates['contact'] = "";
                  }
                  if ($CFG_GLPI["is_contact_autoclean"] && $device->isField('contact_num')) {
                     $updates['contact_num'] = "";
                  }
                  if (($CFG_GLPI["state_autoclean_mode"] < 0)
                      && $device->isField('states_id')) {
                     $updates['states_id'] = 0;
                  }

                  if (($CFG_GLPI["state_autoclean_mode"] > 0)
                      && $device->isField('states_id')
                      && ($device->getField('states_id') != $CFG_GLPI["state_autoclean_mode"])) {

                     $updates['states_id'] = $CFG_GLPI["state_autoclean_mode"];
                  }

                  if (count($updates)) {
                     $updates['id'] = $this->fields['items_id'];
                     $device->update($updates);
                  }
               }
            }
         }
      }
   }


   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {

      switch ($input['action']) {
         case "connect" :
            if ($input['itemtype'] == 'Computer') {
               Dropdown::showAllItems("items_id", 0, 0, $_SESSION["glpiactive_entity"],
                                      array('Monitor', 'Peripheral', 'Phone',  'Printer'),
                                      true, true, 'item_itemtype');
               echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                              __s('Connect')."'>";
            } else {
               Computer_Item::dropdownConnect('Computer', $input["itemtype"], "computers_id");
               echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                              __s('Connect')."'>";
            }
            return true;

         case "disconnect" :
            echo "<input type='submit' name='massiveaction' class='submit' value='".
                   __s('Disconnect')."'>";
            return true;

         default :
            return parent::showSpecificMassiveActionsParameters($input);

      }
      return false;
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "connect" :
            foreach ($input["item"] as $key => $val) {
               if ($val == 1) {
                  if (isset($input["computers_id"])) {
                     $input2 = array('computers_id' => $input["computers_id"],
                                    'itemtype'      => $input["itemtype"],
                                    'items_id'      => $key);
                  } else if (isset($input["items_id"])) {
                     $input2 = array('computers_id' => $key,
                                    'itemtype'      => $input["item_itemtype"],
                                    'items_id'      => $input["items_id"]);
                  } else {
                     return false;
                  }
                  if ($this->can(-1, 'w', $input2)) {
                     if ($this->add($input2)) {
                        $res['ok']++;
                     } else {
                        $res['ko']++;
                     }
                  } else {
                     $res['noright']++;
                  }
               }
            }
           break;

         case "disconnect" :
            if (!($item = getItemForItemtype($input["itemtype"]))) {
               return false;
            }
            foreach ($input["item"] as $key => $val) {
               if ($val == 1) {
                  if ($item->can($key, 'd')) {
                     if ($this->disconnectForItem($item)) {
                        $res['ok']++;
                     } else {
                        $res['ko']++;
                     }
                  } else {
                     $res['noright']++;
                  }
               }
            }
            break;

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   /**
   * Disconnect an item to its computer
   *
   * @param $item    CommonDBTM object: the Monitor/Phone/Peripheral/Printer
   *
   * @return boolean : action succeeded
   */
   function disconnectForItem(CommonDBTM $item) {
      global $DB;

      if ($item->getField('id')) {
         $query = "SELECT `id`
                   FROM `glpi_computers_items`
                   WHERE `itemtype` = '".$item->getType()."'
                         AND `items_id` = '".$item->getField('id')."'";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            $ok = true;
            while ($data = $DB->fetch_assoc($result)) {
               if ($this->can($data["id"],'w')) {
                  $ok &= $this->delete($data);
               }
            }
            return $ok;
         }
      }
      return false;
   }


   /**
    *
    * Print the form for computers or templates connections to printers, screens or peripherals
    *
    * @param $comp                     Computer object
    * @param $withtemplate    boolean  Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForComputer(Computer $comp, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $ID      = $comp->fields['id'];
      $canedit = $comp->can($ID,'w');
      $rand    = mt_rand();

      $datas = array();
      $used  = array();
      foreach ($CFG_GLPI["directconnect_types"] as $itemtype) {
         $item = new $itemtype();
         if ($item->canView()) {
            $query = "SELECT `glpi_computers_items`.`id` as assoc_id,
                      `glpi_computers_items`.`computers_id` as assoc_computers_id,
                      `glpi_computers_items`.`itemtype` as assoc_itemtype,
                      `glpi_computers_items`.`items_id` as assoc_items_id,
                      `glpi_computers_items`.`is_dynamic` as assoc_is_dynamic,
                      ".getTableForItemType($itemtype).".*
                      FROM `glpi_computers_items`
                      LEFT JOIN `".getTableForItemType($itemtype)."`
                        ON (`".getTableForItemType($itemtype)."`.`id`
                              = `glpi_computers_items`.`items_id`)
                      WHERE `computers_id` = '$ID'
                            AND `itemtype` = '".$itemtype."'
                            AND `glpi_computers_items`.`is_deleted` = '0'";
            if ($item->maybetemplate()) {
               $query.= " AND NOT `".getTableForItemType($itemtype)."`.`is_template` ";
            }

            if ($result = $DB->query($query)) {
               while ($data = $DB->fetch_assoc($result)) {
                  $datas[]           = $data;
                  $used[$itemtype][] = $data['assoc_items_id'];
               }
            }
         }
      }
      $number = count($datas);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='computeritem_form$rand' id='computeritem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Connect an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
//          Dropdown::showAllItems("items_id", 0, 0, $comp->fields['entities_id'],
//                                 $CFG_GLPI["directconnect_types"], false, true);
         if (!empty($withtemplate)) {
            echo "<input type='hidden' name='_no_history' value='1'>";
         }
         self::dropdownAllConnect('Computer', "items_id", $comp->fields["entities_id"],
                                  $withtemplate, $used);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Connect')."\" class='submit'>";
         echo "<input type='hidden' name='computers_id' value='".$comp->fields['id']."'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      if ($number) {
         echo "<div class='spaced'>";
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('num_displayed'    => $number,
                                         'specific_actions' => array('purge' => _x('button', 'Disconnect')));
            Html::showMassiveActions(__CLASS__, $massiveactionparams);
         }
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";

         if ($canedit) {
            echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
         }

         echo "<th>".__('Type')."</th>";
         echo "<th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            echo "<th>".__('Automatic inventory')."</th>";
         }
         echo "<th>".__('Entity')."</th>";
         echo "<th>".__('Serial number')."</th>";
         echo "<th>".__('Inventory number')."</th>";
         echo "</tr>";


         foreach ($datas as $data) {
            $linkname = $data["name"];
            $itemtype = $data['assoc_itemtype'];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
               $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
            }
            $link = Toolbox::getItemTypeFormURL($itemtype);
            $name = "<a href=\"".$link."?id=".$data["id"]."\">".$linkname."</a>";

            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assoc_id"]);
               echo "</td>";
            }
            echo "<td class='center'>".$data['assoc_itemtype']::getTypeName(1)."</td>";
            echo "<td ".
                  ((isset($data['is_deleted']) && $data['is_deleted'])?"class='tab_bg_2_2'":"").
                 ">".$name."</td>";
            if (Plugin::haveImport()) {
               echo "<td>";
               echo Dropdown::getYesNo($data['assoc_is_dynamic']);
               echo "</td>";
            }
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                               $data['entities_id']);
            echo "</td>";
            echo "<td class='center'>".
                   (isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
            echo "<td class='center'>".
                   (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
            echo "</tr>";
         }

         echo "</table>";
         if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions(__CLASS__, $massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
      }
   }


   /**
    * Prints a direct connection to a computer
    *
    * @param $item                     CommonDBTM object: the Monitor/Phone/Peripheral/Printer
    * @param $withtemplate    integer  withtemplate param (default '')
    *
    * @return nothing (print out a table)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      // Prints a direct connection to a computer
      global $DB;

      $comp   = new Computer();
      $ID     = $item->getField('id');

      if (!$item->can($ID,"r")) {
         return false;
      }
      $canedit = $item->can($ID,"w");
      $rand    = mt_rand();

      // Is global connection ?
      $global  = $item->getField('is_global');

      $used    = array();
      $compids = array();
      $crit    = array('FIELDS'     => array('id', 'computers_id', 'is_dynamic'),
                       'itemtype'   => $item->getType(),
                       'items_id'   => $ID,
                       'is_deleted' => 0);
      foreach ($DB->request('glpi_computers_items', $crit) as $data) {
         $compids[$data['id']] = $data['computers_id'];
         $dynamic[$data['id']] = $data['is_dynamic'];
         $used['Computer'][]   = $data['computers_id'];
      }
      $number = count($compids);

      if ($canedit
          && ($global || !$number)) {
         echo "<div class='firstbloc'>";
         echo "<form name='computeritem_form$rand' id='computeritem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Connect a computer')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
         if ($item->isRecursive()) {
            self::dropdownConnect('Computer', $item->getType(), "computers_id",
                                  getSonsOf("glpi_entities", $item->getEntityID()), 0, $used);
         } else {
            self::dropdownConnect('Computer', $item->getType(), "computers_id",
                                  $item->getEntityID(), 0, $used);
         }
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Connect')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'    => $number,
                                      'specific_actions' => array('purge' => _x('button', 'Disconnect')));
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      if ($number > 0) {
         echo "<tr>";

         if ($canedit) {
            echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
         }

         echo "<th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            echo "<th>".__('Automatic inventory')."</th>";
         }
         echo "<th>".__('Entity')."</th>";
         echo "<th>".__('Serial number')."</th>";
         echo "<th>".__('Inventory number')."</th>";
         echo "</tr>";
         foreach ($compids as $key => $compid) {
            $comp->getFromDB($compid);

            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            echo "<td ".
                  ($comp->getField('is_deleted')?"class='tab_bg_2_2'":"").
                 ">".$comp->getLink()."</td>";
            if (Plugin::haveImport()) {
               echo "<td>";
               echo Dropdown::getYesNo($dynamic[$key]);
               echo "</td>";
            }
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                               $comp->getField('entities_id'));
            echo "</td>";
            echo "<td class='center'>".$comp->getField('serial')."</td>";
            echo "<td class='center'>".$comp->getField('otherserial')."</td>";
            echo "</tr>";
         }
      } else {
         echo "<tr><td class='tab_bg_1 b'><i>".__('Not connected')."</i>";
         echo "</td></tr>";
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Unglobalize an item : duplicate item and connections
    *
    * @param $item   CommonDBTM object to unglobalize
   **/
   static function unglobalizeItem(CommonDBTM $item) {
      global $DB;

      // Update item to unit management :
      if ($item->getField('is_global')) {
         $input = array('id'        => $item->fields['id'],
                        'is_global' => 0);
         $item->update($input);

         // Get connect_wire for this connection
         $query = "SELECT `glpi_computers_items`.`id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->fields['id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->getType()."'";
         $result = $DB->query($query);

         if ($data = $DB->fetch_assoc($result)) {
            // First one, keep the existing one

            // The others = clone the existing object
            unset($input['id']);
            $conn = new self();
            while ($data = $DB->fetch_assoc($result)) {
               $temp = clone $item;
               unset($temp->fields['id']);
               if ($newID=$temp->add($temp->fields)) {
                  $conn->update(array('id'       => $data['id'],
                                      'items_id' => $newID));
               }
            }
         }
      }
   }


   /**
   * Make a select box for connections
   *
   * @since version 0.84
   *
   * @param $fromtype               from where the connection is
   * @param $myname                 select name
   * @param $entity_restrict        Restrict to a defined entity (default = -1)
   * @param $onlyglobal             display only global devices (used for templates) (default 0)
   * @param $used             array Already used items ID: not to display in dropdown
   *
   * @return nothing (print out an HTML select box)
   */
   static function dropdownAllConnect($fromtype, $myname, $entity_restrict=-1,
                                      $onlyglobal=0, $used=array()) {
      global $CFG_GLPI;

      $rand = mt_rand();
//       $use_ajax = false;
//       if ($CFG_GLPI["use_ajax"]) {
//          $nb = 0;
//          if ($entity_restrict >= 0) {
//             $nb = countElementsInTableForEntity(getTableForItemType($itemtype), $entity_restrict);
//          } else {
//             $nb = countElementsInTableForMyEntities(getTableForItemType($itemtype));
//          }
//          if ($nb > $CFG_GLPI["ajax_limit_count"]) {
//             $use_ajax = true;
//          }
//       }
      $options               = array();
      $options['checkright'] = true;
      $options['name']       = 'itemtype';

      $rand = Dropdown::showItemType($CFG_GLPI['directconnect_types'], $options);
      if ($rand) {
         $params = array('itemtype'        => '__VALUE__',
                         'fromtype'        => $fromtype,
                         'value'           => 0,
                         'myname'          => $myname,
                         'onlyglobal'      => $onlyglobal,
                         'entity_restrict' => $entity_restrict,
                         'used'            => $used);

         if ($onlyglobal) {
            $params['condition'] = "`is_global` = '1'";
         }
         Ajax::updateItemOnSelectEvent("itemtype$rand", "show_$myname$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownConnect.php", $params);

         echo "<br><span id='show_$myname$rand'>&nbsp;</span>\n";
      }
      return $rand;

   }


   /**
   * Make a select box for connections
   *
   * @param $itemtype               type to connect
   * @param $fromtype               from where the connection is
   * @param $myname                 select name
   * @param $entity_restrict        Restrict to a defined entity (default = -1)
   * @param $onlyglobal             display only global devices (used for templates) (default 0)
   * @param $used             array Already used items ID: not to display in dropdown
   *
   * @return nothing (print out an HTML select box)
   */
   static function dropdownConnect($itemtype, $fromtype, $myname, $entity_restrict=-1,
                                   $onlyglobal=0, $used=array()) {
      global $CFG_GLPI;

      $rand = mt_rand();

      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb = 0;
         if ($entity_restrict >= 0) {
            $nb = countElementsInTableForEntity(getTableForItemType($itemtype), $entity_restrict);
         } else {
            $nb = countElementsInTableForMyEntities(getTableForItemType($itemtype));
         }
         if ($nb > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }

      $params = array('searchText'      => '__VALUE__',
                      'fromtype'        => $fromtype,
                      'itemtype'        => $itemtype,
                      'myname'          => $myname,
                      'onlyglobal'      => $onlyglobal,
                      'entity_restrict' => $entity_restrict,
                      'used'            => $used);
      $default = "<select name='$myname'><option value='0'>".Dropdown::EMPTY_VALUE."</option>
                  </select>\n";
      Ajax::dropdown($use_ajax, "/ajax/dropdownConnect.php", $params, $default, $rand);

      return $rand;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for Template
      if ($item->can($item->getField('id'),'r')) {
         switch ($item->getType()) {
            case 'Phone' :
            case 'Printer' :
            case 'Peripheral' :
            case 'Monitor' :
               if (Session::haveRight('computer', 'r')) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return self::createTabEntry(_n('Connection','Connections',2),
                                                 self::countForItem($item));
                  }
                  return _n('Connection','Connections',2);
               }
               break;

            case 'Computer' :
               if (Session::haveRight('phone', 'r')
                   || Session::haveRight('printer', 'r')
                   || Session::haveRight('peripheral', 'r')
                   || Session::haveRight('monitor', 'r')) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     return self::createTabEntry(_n('Connection','Connections',2),
                                                 self::countForComputer($item));
                  }
                  return _n('Connection','Connections',2);
               }
               break;
         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Phone' :
         case 'Printer' :
         case 'Peripheral' :
         case 'Monitor' :
            self::showForItem($item);
            return true;

         case 'Computer' :
            self::showForComputer($item);
            return true;
      }
   }


   /**
    * Duplicate connected items to computer from an item template to its clone
    *
    * @since version 0.84
    *
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
   **/
   static function cloneComputer($oldid, $newid) {
      global $DB;

      $query  = "SELECT *
                 FROM `glpi_computers_items`
                 WHERE `computers_id` = '".$oldid."';";
      $result = $DB->query($query);

      foreach ($DB->request($query) as $data) {
         $conn = new Computer_Item();
         $conn->add(array('computers_id' => $newid,
                          'itemtype'     => $data["itemtype"],
                          'items_id'     => $data["items_id"]));
      }
   }


   /**
    * Duplicate connected items to item from an item template to its clone
    *
    * @since version 0.83.3
    *
    * @param $itemtype     type of the item to clone
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
   **/
   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

      $query  = "SELECT *
                 FROM `glpi_computers_items`
                 WHERE `itemtype` = '$itemtype'
                       AND `items_id` = '".$oldid."'";
      $result = $DB->query($query);

      foreach ($DB->request($query) as $data) {
         $conn = new self();
         $conn->add(array('computers_id' => $data["computers_id"],
                          'itemtype'     => $data["itemtype"],
                          'items_id'     => $newid));
      }
   }

}
?>