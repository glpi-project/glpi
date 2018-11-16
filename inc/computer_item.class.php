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
 * Computer_Item Class
 *
 * Relation between Computer and Items (monitor, printer, phone, peripheral only)
**/
class Computer_Item extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1          = 'Computer';
   static public $items_id_1          = 'computers_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Count connection for a Computer and an itemtype
    *
    * @since 0.84
    *
    * @param $comp   Computer object
    * @param $item   CommonDBTM object
    *
    * @return integer: count
   **/
   static function countForAll(Computer $comp, CommonDBTM $item) {

      return countElementsInTable('glpi_computers_items',
                                  ['computers_id' => $comp->getField('id'),
                                   'itemtype'     => $item->getType(),
                                   'items_id'     => $item->getField('id')]);
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      $item = static::getItemFromArray(static::$itemtype_2, static::$items_id_2, $input);
      if (!($item instanceof CommonDBTM)
          || (($item->getField('is_global') == 0)
              && ($this->countForItem($item) > 0))) {
         return false;
      }

      $comp = static::getItemFromArray(static::$itemtype_1, static::$items_id_1, $input);
      if (!($comp instanceof Computer)
          || (self::countForAll($comp, $item) >0)) {
         // no duplicates
         return false;
      }

      if (!$item->getField('is_global')) {
         // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
         $updates = [];

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
            $history = true;
            if (isset($input['_no_history']) && $input['_no_history']) {
               $history = false;
            }
            $item->update($updates, $history);
         }
      }
      return parent::prepareInputForAdd($input);
   }


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
                  $updates = [];
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


   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0,
                                                CommonDBTM $checkitem = null) {

      $action_prefix = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR;
      $specificities = self::getRelationMassiveActionsSpecificities();

      if (in_array($itemtype, $specificities['itemtypes'])) {
         $actions[$action_prefix.'add']    = _x('button', 'Connect');
         $actions[$action_prefix.'remove'] = _x('button', 'Disconnect');
      }
      parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
   }


   static function getRelationMassiveActionsSpecificities() {

      $specificities              = parent::getRelationMassiveActionsSpecificities();

      $specificities['itemtypes'] = ['Monitor', 'Peripheral', 'Phone', 'Printer'];

      $specificities['select_items_options_2']['entity_restrict'] = $_SESSION['glpiactive_entity'];
      $specificities['select_items_options_2']['onlyglobal']      = true;

      $specificities['only_remove_all_at_once']                   = true;

      // Set the labels for add_item and remove_item
      $specificities['button_labels']['add']                      = _sx('button', 'Connect');
      $specificities['button_labels']['remove']                   = _sx('button', 'Disconnect');

      return $specificities;
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
         $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $this->getTable(),
            'WHERE'  => [
               'itemtype'  => $item->getType(),
               'items_id'  => $item->getID()
            ]
         ]);

         if (count($iterator) > 0) {
            $ok = true;
            while ($data = $iterator->next()) {
               if ($this->can($data["id"], UPDATE)) {
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
    * @param Computer $comp         Computer object
    * @param boolean  $withtemplate Template or basic item (default 0)
    *
    * @return void
   **/
   static function showForComputer(Computer $comp, $withtemplate = 0) {
      global $CFG_GLPI;

      $ID      = $comp->fields['id'];
      $canedit = $comp->canEdit($ID);
      $rand    = mt_rand();

      $datas = [];
      $used  = [];
      foreach ($CFG_GLPI["directconnect_types"] as $itemtype) {
         $item = new $itemtype();
         if ($item->canView()) {
            $iterator = self::getTypeItems($ID, $itemtype);

            while ($data = $iterator->next()) {
               $data['assoc_itemtype'] = $itemtype;
               $datas[]           = $data;
               $used[$itemtype][] = $data['id'];
            }
         }
      }
      $number = count($datas);

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='firstbloc'>";
         echo "<form name='computeritem_form$rand' id='computeritem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Connect an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         if (!empty($withtemplate)) {
            echo "<input type='hidden' name='_no_history' value='1'>";
         }
         self::dropdownAllConnect('Computer', "items_id", $comp->fields["entities_id"],
                                  $withtemplate, $used);
         echo "</td><td class='center' width='20%'>";
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
            $massiveactionparams
               = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $number),
                       'specific_actions'
                           => ['purge' => _x('button', 'Disconnect')],
                       'container'
                           => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';

         if ($canedit) {
            $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .=  "</th>";
         }

         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            $header_end .= "<th>".__('Automatic inventory')."</th>";
         }
         $header_end .= "<th>".__('Entity')."</th>";
         $header_end .= "<th>".__('Serial number')."</th>";
         $header_end .= "<th>".__('Inventory number')."</th>";
         $header_end .= "</tr>";
         echo $header_begin.$header_top.$header_end;

         foreach ($datas as $data) {
            $linkname = $data["name"];
            $itemtype = $data['assoc_itemtype'];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
               $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
            }
            $link = $itemtype::getFormURLWithID($data["id"]);
            $name = "<a href=\"".$link."\">".$linkname."</a>";

            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
               echo "</td>";
            }
            echo "<td class='center'>".$data['assoc_itemtype']::getTypeName(1)."</td>";
            echo "<td ".
                  ((isset($data['is_deleted']) && $data['is_deleted'])?"class='tab_bg_2_2'":"").
                 ">".$name."</td>";
            if (Plugin::haveImport()) {
               echo "<td>".Dropdown::getYesNo($data['is_dynamic'])."</td>";
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
         echo $header_begin.$header_bottom.$header_end;

         echo "</table>";
         if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
      }
   }


   /**
    * Prints a direct connection to a computer
    *
    * @param $item                     CommonDBTM object: the Monitor/Phone/Peripheral/Printer
    * @param $withtemplate    integer  withtemplate param (default 0)
    *
    * @return void
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      // Prints a direct connection to a computer
      global $DB;

      $comp   = new Computer();
      $ID     = $item->getField('id');

      if (!$item->can($ID, READ)) {
         return;
      }
      $canedit = $item->canEdit($ID);
      $rand    = mt_rand();

      // Is global connection ?
      $global  = $item->getField('is_global');

      $used    = [];
      $compids = [];
      $dynamic = [];
      $result = $DB->request(
         [
            'SELECT' => ['id', 'computers_id', 'is_dynamic'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'itemtype'   => $item->getType(),
               'items_id'   => $ID,
               'is_deleted' => 0,
            ]
         ]
      );
      foreach ($result as $data) {
         $compids[$data['id']] = $data['computers_id'];
         $dynamic[$data['id']] = $data['is_dynamic'];
         $used['Computer'][]   = $data['computers_id'];
      }
      $number = count($compids);
      if ($canedit
          && ($global || !$number)
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
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
         $massiveactionparams
            = ['num_displayed'
                        => min($_SESSION['glpilist_limit'], $number),
                    'specific_actions'
                        => ['purge' => _x('button', 'Disconnect')],
                    'container'
                        => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";

      if ($number > 0) {
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';

         if ($canedit) {
            $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= "</th>";
         }

         $header_end .= "<th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            $header_end .= "<th>".__('Automatic inventory')."</th>";
         }
         $header_end .= "<th>".__('Entity')."</th>";
         $header_end .= "<th>".__('Serial number')."</th>";
         $header_end .= "<th>".__('Inventory number')."</th>";
         $header_end .= "</tr>";
         echo $header_begin.$header_top.$header_end;

         foreach ($compids as $key => $compid) {
            $comp->getFromDB($compid);

            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $key);
               echo "</td>";
            }
            echo "<td ".
                  ($comp->getField('is_deleted')?"class='tab_bg_2_2'":"").
                 ">".$comp->getLink()."</td>";
            if (Plugin::haveImport()) {
               echo "<td>".Dropdown::getYesNo($dynamic[$key])."</td>";
            }
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                               $comp->getField('entities_id'));
            echo "</td>";
            echo "<td class='center'>".$comp->getField('serial')."</td>";
            echo "<td class='center'>".$comp->getField('otherserial')."</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
      } else {
         echo "<tr><td class='tab_bg_1 b'><i>".__('Not connected')."</i>";
         echo "</td></tr>";
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
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
         $input = ['id'        => $item->fields['id'],
                        'is_global' => 0];
         $item->update($input);

         // Get connect_wire for this connection
         $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'items_id'  => $item->getID(),
               'itemtype'  => $item->getType()
            ]
         ]);

         $first = true;
         while ($data = $iterator->next()) {
            if ($first) {
               $first = false;
               unset($input['id']);
               $conn = new self();
            } else {
               $temp = clone $item;
               unset($temp->fields['id']);
               if ($newID=$temp->add($temp->fields)) {
                  $conn->update(['id'       => $data['id'],
                                 'items_id' => $newID]);
               }
            }
         }
      }
   }


   /**
   * Make a select box for connections
   *
   * @since 0.84
   *
   * @param string            $fromtype        from where the connection is
   * @param string            $myname          select name
   * @param integer|integer[] $entity_restrict Restrict to a defined entity (default = -1)
   * @param boolean           $onlyglobal      display only global devices (used for templates) (default 0)
   * @param integer[]         $used            Already used items ID: not to display in dropdown
   *
   * @return integer Random generated number used for select box ID (select box HTML is printed)
   */
   static function dropdownAllConnect($fromtype, $myname, $entity_restrict = -1,
                                      $onlyglobal = 0, $used = []) {
      global $CFG_GLPI;

      $rand = mt_rand();

      $options               = [];
      $options['checkright'] = true;
      $options['name']       = 'itemtype';

      $rand = Dropdown::showItemType($CFG_GLPI['directconnect_types'], $options);
      if ($rand) {
         $params = ['itemtype'        => '__VALUE__',
                         'fromtype'        => $fromtype,
                         'value'           => 0,
                         'myname'          => $myname,
                         'onlyglobal'      => $onlyglobal,
                         'entity_restrict' => $entity_restrict,
                         'used'            => $used];

         if ($onlyglobal) {
            $params['condition'] = "`is_global` = 1";
         }
         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_$myname$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownConnect.php", $params);

         echo "<br><div id='show_$myname$rand'>&nbsp;</div>\n";
      }
      return $rand;

   }


   /**
   * Make a select box for connections
   *
   * @param string            $itemtype        type to connect
   * @param string            $fromtype        from where the connection is
   * @param string            $myname          select name
   * @param integer|integer[] $entity_restrict Restrict to a defined entity (default = -1)
   * @param boolean           $onlyglobal      display only global devices (used for templates) (default 0)
   * @param integer[]         $used            Already used items ID: not to display in dropdown
   *
   * @return integer Random generated number used for select box ID (select box HTML is printed)
   */
   static function dropdownConnect($itemtype, $fromtype, $myname, $entity_restrict = -1,
                                   $onlyglobal = 0, $used = []) {
      global $CFG_GLPI;

      $rand     = mt_rand();

      $field_id = Html::cleanId("dropdown_".$myname.$rand);
      $param    = ['entity_restrict' => $entity_restrict,
                        'fromtype'        => $fromtype,
                        'itemtype'        => $itemtype,
                        'onlyglobal'      => $onlyglobal,
                        'used'            => $used];

      echo Html::jsAjaxDropdown($myname, $field_id,
                                $CFG_GLPI['root_doc']."/ajax/getDropdownConnect.php",
                                $param);

      return $rand;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      // can exists for Template
      if ($item->can($item->getField('id'), READ)) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Phone' :
            case 'Printer' :
            case 'Peripheral' :
            case 'Monitor' :
               if (Computer::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(_n('Connection', 'Connections', Session::getPluralNumber()),
                                              $nb);
               }
               break;

            case 'Computer' :
               if (Phone::canView()
                   || Printer::canView()
                   || Peripheral::canView()
                   || Monitor::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForMainItem($item);
                  }
                  return self::createTabEntry(_n('Connection', 'Connections', Session::getPluralNumber()),
                                              $nb);
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Phone' :
         case 'Printer' :
         case 'Peripheral' :
         case 'Monitor' :
            self::showForItem($item, $withtemplate);
            return true;

         case 'Computer' :
            self::showForComputer($item, $withtemplate);
            return true;
      }
   }


   /**
    * Duplicate connected items to computer from an item template to its clone
    *
    * @since 0.84
    *
    * @param integer $oldid ID of the item to clone
    * @param integer $newid ID of the item cloned
   **/
   static function cloneComputer($oldid, $newid) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => ['computers_id' => $oldid]
      ]);

      while ($data = $iterator->next()) {
         $conn = new Computer_Item();
         $conn->add(['computers_id' => $newid,
                     'itemtype'     => $data["itemtype"],
                     'items_id'     => $data["items_id"]]);
      }
   }


   /**
    * Duplicate connected items to item from an item template to its clone
    *
    * @since 0.83.3
    *
    * @param string  $itemtype type of the item to clone
    * @param integer $oldid    ID of the item to clone
    * @param integer $newid    ID of the item cloned
   **/
   static function cloneItem($itemtype, $oldid, $newid) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'itemtype'  => $itemtype,
            'items_id'  => $oldid
         ]
      ]);

      while ($data = $iterator->next()) {
         $conn = new self();
         $conn->add(['computers_id' => $data["computers_id"],
                     'itemtype'     => $data["itemtype"],
                     'items_id'     => $newid]);
      }
   }


   /**
    * @since 9.1.7
    *
    * @param CommonDBTM $item     item linked to the computer to check
    * @param integer[]  $entities entities to check
    *
    * @return boolean
   **/
   static function canUnrecursSpecif(CommonDBTM $item, $entities) {
      global $DB;

      // RELATION : computers -> items
      $iterator = $DB->request([
         'SELECT' => [
            'itemtype',
            new \QueryExpression('GROUP_CONCAT(DISTINCT '.$DB->quoteName('items_id').') AS ids'),
            'computers_id'
         ],
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'itemtype'  => $item->getType(),
            'items_id'  => $item->fields['id']
         ],
         'GROUP'  => 'itemtype'
      ]);

      while ($data = $iterator->next()) {
         if (countElementsInTable("glpi_computers",
                                    ['id' => $data["computers_id"],
                                    'NOT' => ['entities_id' => $entities]]) > 0) {
            return false;
         }
      }
      return true;
   }


   protected static function getListForItemParams(CommonDBTM $item, $noent = false) {
      $params = parent::getListForItemParams($item, $noent);
      $params['WHERE'][self::getTable() . '.is_deleted'] = 0;
      return $params;
   }
}
