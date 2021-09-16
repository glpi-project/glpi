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

use Glpi\Event;

/// Socket class
class Socket extends CommonDBChild {


   // From CommonDBChild
   static public $itemtype = 'itemtype';
   static public $items_id = 'items_id';
   static public $checkParentRights  = self::DONT_CHECK_ITEM_RIGHTS;

   // From CommonDBTM
   public $dohistory          = true;
   static $rightname          = 'cable_management';
   public $can_be_translated  = false;

   const REAR    = 1;
   const FRONT   = 2;

   function canCreateItem() {
      return Session::haveRight(static::$rightname, CREATE);
   }


   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
   * NetworkPort Form
   * @return string ID of the select
   **/
   static function showNetworkPortForm($itemtype, $items_id, $networkports_id = 0, $options = []) {

      global $CFG_GLPI;

      //if form is called from an item, retrive itemtype and items
      if (isset($options['_add_fromitem'])) {
         $itemtype = $options['_add_fromitem']["_from_itemtype"];
         $items_id = $options['_add_fromitem']["_from_items_id"];
      }

      $rand_itemtype = rand();
      $rand_items_id = rand();

      echo "<span id='show_itemtype_field' class='input_listener'>";
      Dropdown::showFromArray('itemtype', self::getSocketLinkTypes(), ['value' => $itemtype,
                                                                       'rand' => $rand_itemtype]);
      echo "</span>";

      $params = ['itemtype'   => '__VALUE__',
                 'dom_rand'   => $rand_items_id,
                 'dom_name'   => 'items_id',
                 'action'     => 'get_items_from_itemtype'];
      Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand_itemtype",
                                    "show_items_id_field",
                                    $CFG_GLPI["root_doc"]."/ajax/cable.php",
                                    $params);

      echo "<span id='show_items_id_field' class='input_listener'>";
      if (!empty($itemtype)) {
         $rand_items_id =  $itemtype::dropdown(['name'                  => 'items_id',
                                                'value'                 => $items_id,
                                                'display_emptychoice'   => true,
                                                'display_dc_position'   => true,
                                                'rand' => $rand_items_id]);
      }
      echo "</span>";

      echo "<span id='show_networkport_field'>";
      NetworkPort::dropdown(['name'                => 'networkports_id',
                             'value'               => $networkports_id,
                             'display_emptychoice' => true,
                             'condition'           => ['items_id' => $items_id,
                                                       'itemtype' => $itemtype]]);
      echo "</span>";

      //Listener to update breacrumb / socket
      echo Html::scriptBlock("
         //listener to remove socket selector and breadcrumb
         $(document).on('change', '#dropdown_itemtype".$rand_itemtype."', function(e) {
            $('#show_front_asset_breadcrumb').empty();
            $('#show_front_sockets_field').empty();
         });

         //listener to refresh socket selector and breadcrumb
         $(document).on('change', '#dropdown_items_id".$rand_items_id."', function(e) {
            var items_id = $('#dropdown_items_id".$rand_items_id."').find(':selected').val();
            var itemtype = $('#dropdown_itemtype".$rand_itemtype."').find(':selected').val();
            refreshAssetBreadcrumb(itemtype, items_id, 'show_asset_breadcrumb');
            refreshNetworkPortDropdown(itemtype, items_id, 'show_networkport_field');

         });
      ");

   }

   /**
    * Print the version form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - itemtype type of the item for add process
    *     - items_id ID of the item for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options = []) {

      $itemtype = null;
      if (isset($options['itemtype']) && !empty($options['itemtype'])) {
         $itemtype = $options['itemtype'];
      } else if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
      } else {
         throw new \RuntimeException('Unable to retrieve itemtype');
      }

      $item = new $itemtype();
      if ($ID > 0) {
         $this->check($ID, READ);
         $item->getFromDB($this->fields['items_id']);
      } else {
         $this->check(-1, CREATE, $options);
         $item->getFromDB($options['items_id']);
      }

      $this->showFormHeader($options);

      //if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='items_id' value='".$options['items_id']."'>";
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
      //}

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "position");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Location::getTypeName(0)."</td>";
      echo "<td>";
      Location::dropdown(['value' => $this->fields['locations_id']]);
      echo "</td>";
      echo "<td>".SocketModel::getTypeName(1)."</td>";
      echo "<td>";
      SocketModel::dropdown(['value' => $this->fields['socketmodels_id']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Wiring side')."</td>";
      echo "<td>";
      self::dropdownWiringSide('wiring_side', ['value' => $this->fields['wiring_side']]);
      echo "</td>";
      echo "<td>"._n('Network port', 'Network ports', Session::getPluralNumber())."</td>";
      echo "<td>";
      self::showNetworkPortForm($this->fields['itemtype'], $this->fields['items_id'], $this->fields['networkports_id'], $options);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Position')."</td>";
      echo "<td>";
      echo "<span id='show_asset_breadcrumb'>";
      if (!empty($this->fields['itemtype']) && !empty($this->fields['items_id'])) {
         if (method_exists($this->fields['itemtype'], 'getDcBreadcrumbSpecificValueToDisplay')) {
            echo $this->fields['itemtype']::getDcBreadcrumbSpecificValueToDisplay($this->fields['items_id']);
         }
      }
      echo "</span>";
      echo "</td><td></td><td></td>";
      echo "</tr>";

      $options['canedit'] = Session::haveRight($itemtype::$rightname, UPDATE);
      $this->showFormButtons($options);

      return true;

   }

   function prepareInputForAdd($input) {
      $input = $this->retrievedataFromNetworkPort($input);
      return $input;
   }


   function prepareInputForUpdate($input) {
      $input = $this->retrievedataFromNetworkPort($input);
      return $input;
   }

   function retrievedataFromNetworkPort($input) {
      //get position from networkport if needed
      if ((isset($input["networkports_id"]) && $input["networkports_id"] > 0 ) && $input["position"] == 'auto') {
         $networkport = new NetworkPort();
         $networkport->getFromDB($input["networkports_id"]);
         $input['position'] = $networkport->fields['logical_number'];
      }

      //get name from networkport if needed
      if ((isset($input["networkports_id"]) && $input["networkports_id"] > 0 ) && empty($input["name"])) {
         $networkport = new NetworkPort();
         $networkport->getFromDB($input["networkports_id"]);
         $input['name'] = $networkport->fields['name'];
      }

      return $input;
   }


   /**
    * Get possible itemtype
    * @return array Array of types
   **/
   static function getSocketLinkTypes() {
      global $CFG_GLPI;
      $values = [];
      foreach ($CFG_GLPI["socket_types"] as $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            $values[$itemtype] = $item->getTypeName();
         }
      }
      return $values;
   }


   /**
    * Dropdown of Wiring Side
    *
    * @param string $name   select name
    * @param array  $options possible options:
    *    - value       : integer / preselected value (default 0)
    *    - display
    * @return string ID of the select
   **/
   static function dropdownWiringSide($name, $options = []) {
      $params = [
         'value'     => 0,
         'display'   => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      return Dropdown::showFromArray($name, self::getSides(), $params);
   }


   /**
    * Get sides
    * @return array Array of types
   **/
   static function getSides() {
      return [
         self::REAR   => __('Endpoint A'),
         self::FRONT  => __('Endpoint B'),
      ];
   }


   function post_getEmpty() {
      $this->fields['itemtype'] = 'Computer';
      $this->fields['position'] = -1;
   }


   /**
    * Get wiring side name
    *
    * @since 0.84
    *
    * @param integer $value     status ID
   **/
   static function getWiringSideName($value) {
      $tab  = static::getSides();
      // Return $value if not defined
      return (isset($tab[$value]) ? $tab[$value] : $value);
   }


   static function getTypeName($nb = 0) {
      return _n('Socket', 'Sockets', $nb);
   }


   function rawSearchOptions() {
      $tab  = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '5',
         'table'              => Socket::getTable(),
         'field'              => 'position',
         'name'               => __('Position'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'name'               => SocketModel::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => Socket::getTable(),
         'field'              => 'itemtype',
         'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'socket_types',
         'additionalfields'   => ['itemtype'],
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'items_id',
         'name'               => __('Associated item ID'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'additionalfields'   => ['itemtype']
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => Socket::getTable(),
         'field'              => 'wiring_side',
         'name'               => __('Wiring side'),
         'searchtype'         => 'equals',
         'datatype'           => 'specific'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      foreach ($tab as &$t) {
         if ($t['id'] == 3) {
            $t['datatype']      = 'itemlink';
            break;
         }
      }

      return $tab;
   }


   static public function rawSearchOptionsToAdd() {
      $tab = [];

      $tab[] = [
         'id'                 => 'socket',
         'name'               => Socket::getTypeName(0)
      ];

      $tab[] = [
         'id'                 => '1310',
         'table'              => Socket::getTable(),
         'field'              => 'id',
         'name'               => Socket::getTypeName(0),
         'searchtype'         => 'equals',
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
         ],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '1311',
         'table'              => SocketModel::getTable(),
         'field'              => 'name',
         'name'               => SocketModel::getTypeName(0),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'searchtype'         => 'equals',
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => self::getTable(),
               'joinparams'         => [
                  'jointype'           => 'itemtype_item'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '1312',
         'table'              => Socket::getTable(),
         'field'              => 'wiring_side',
         'name'               => __('Wiring side'),
         'searchtype'         => 'equals',
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
         ],
         'datatype'           => 'specific'
      ];

      return $tab;
   }

   /**
    * @since 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            }
            break;

         case 'wiring_side' :
            return self::dropdownWiringSide($name, $options);
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype'])) {
               if ($values[$field] > 0) {
                  $item = new $values['itemtype'];
                  $item->getFromDB($values[$field]);
                  return "<a href='" . $item->getLinkURL(). "'>".$item->fields['name']."</a>";
               }
            }
            return ' ';
            break;
         case 'wiring_side' :
            return self::getWiringSideName($values[$field]);
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * check if a socket already exists (before import)
    *
    * @param $input array of value to import (name, locations_id, entities_id)
    *
    * @return integer the ID of the new (or -1 if not found)
   **/
   function findID(array &$input) {
      global $DB;

      if (!empty($input["name"])) {
         $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => $this->getTable(),
            'WHERE'  => [
               'name'         => $input['name'],
               'locations_id' => $input["locations_id"] ?? 0
            ] + getEntitiesRestrictCriteria($this->getTable(), $input['entities_id'], $this->maybeRecursive())
         ]);

         // Check twin :
         if (count($iterator)) {
            $result = $iterator->next();
            return $result['id'];
         }
      }
      return -1;
   }


   function post_addItem() {
      $parent = $this->fields['locations_id'];
      if ($parent) {
         $changes[0] = '0';
         $changes[1] = '';
         $changes[2] = addslashes($this->getNameID());
         Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_ADD_SUBITEM);
      }

      $this->cleanIfStealNetworkPort();
   }

   function post_updateItem($history = 1) {
      $this->cleanIfStealNetworkPort();
   }

   function cleanIfStealNetworkPort() {
      global $DB;
      //find other socket with same networkport and reset it
      if ($this->fields['networkports_id'] > 0) {
         $iter = $DB->request(['SELECT' => 'id',
         'FROM'  => getTableForItemType(Socket::getType()),
         'WHERE' => [
            'networkports_id' => $this->fields['networkports_id'],
            ['NOT' => ['id' => $this->fields['id']]]]]);

         foreach (Socket::getFromIter($iter) as $socket) {
            $socket->fields['networkports_id'] = 0;
            $socket->update($socket->fields);
         }
      }
   }


   function post_deleteFromDB() {
      $parent = $this->fields['locations_id'];
      if ($parent) {
         $changes[0] = '0';
         $changes[1] = addslashes($this->getNameID());
         $changes[2] = '';
         Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $CFG_GLPI;
      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Location' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb =  countElementsInTable($this->getTable(),
                                              ['locations_id' => $item->getID()]);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            default:
               if (in_array($item->getType(), $CFG_GLPI['socket_types'])) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb =  countElementsInTable($this->getTable(),
                                                 ['itemtype' => $item->getType(),
                                                  'items_id' => $item->getID()]);
                  }
                  return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
               }
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;
      if ($item->getType() == 'Location') {
         self::showForLocation($item);
      } else if (in_array($item->getType(), $CFG_GLPI['socket_types'])) {
         self::showListForItem($item);
      }
      return true;
   }


   /**
    * Print the HTML array of the Socket associated to a Location
    *
    * @param $item Location
    *
    * @return void
   **/
   static function showListForItem($item) {

      global $DB;

      $canedit = self::canUpdate();
      $rand = mt_rand();

      if (!Session::haveRight(self::$rightname, READ)) {
         return false;
      }

      if ($item->isNewID($item->getID())) {
         return false;
      }

      // Link to open a new socket
      if ($item->getID() && self::canCreate()) {
         echo "<div class='firstbloc'>";
         Html::showSimpleForm(
            Socket::getFormURL(),
            '_add_fromitem',
            __('New socket for this item...'),
            [
               '_from_itemtype' => $item->getType(),
               '_from_items_id' => $item->getID(),
            ]
         );
         echo "</div>";
      }

      $iterator = $DB->request([
         'FROM'   => Socket::getTable(),
         'WHERE'  => [
            'itemtype'   => $item->getType(),
            'items_id'   => $item->getID(),
         ]
      ]);
      $numrows = count($iterator);

      if ($canedit) {
         Html::openMassiveActionsForm('mass'.get_called_class().$rand);
         $massiveactionparams
            = ['num_displayed'
                        => min($_SESSION['glpilist_limit'], $numrows),
               'specific_actions'
                        => ['update' => _x('button', 'Update'),
                            'purge'  => _x('button', 'Delete permanently')]];

         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>" . __('Name') . "</th>";
      $header_end .= "<th>" . __('Position') . "</th>";
      $header_end .= "<th>" . SocketModel::getTypeName(0) . "</th>";
      $header_end .= "<th>" . __('Wiring side') . "</th>";
      $header_end .= "<th>" .  _n('Network port', 'Network ports', Session::getPluralNumber()) . "</th>";
      $header_end .= "<th>" .  Cable::getTypeName(0) . "</th>";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      Session::initNavigateListItems("Socket",
                           //TRANS: %1$s is the itemtype name,
                           //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'),
                                             $item->getTypeName(1), $item->getName()));

      while ($data = $iterator->next()) {
         $socket = new Socket();
         $socket->getFromDB($data['id']);
         Session::addToNavigateListItems(get_class($socket), $socket->fields["id"]);
         echo "<tr class='tab_bg_1'>";

         if ($canedit) {
            echo "<td width='10'>";
            Html::showMassiveActionCheckBox(__CLASS__, $socket->fields["id"]);
            echo "</td>";
            echo "<td><a href='".$socket->getFormURLWithID($socket->fields["id"])
                                . "&amp;onglet=1'>" .$socket->fields["name"] ."</a></td>";

         } else {
            echo "<td>" . $socket->fields["name"] . "</td>";
         }

         echo "<td>" . $socket->fields["position"] . "</td>";
         echo "<td>" . Dropdown::getDropdownName(SocketModel::getTable(), $socket->fields["socketmodels_id"]) . "</td>";
         echo "<td>" . self::getWiringSideName($socket->fields["wiring_side"]) . "</td>";

         $networkport = new NetworkPort();
         if ($networkport->getFromDB($socket->fields["networkports_id"])) {
            echo "<td><a href='" . $networkport->getLinkURL(). "'>".$networkport->fields['name']."</a></td>";
         } else {
            echo "<td></td>";
         }

         $cable = new Cable();
         if ($cable->getFromDBByCrit(['OR' => ['sockets_id_endpoint_a' => $socket->fields["id"],
                                               'sockets_id_endpoint_b' => $socket->fields["id"]
                                              ]])) {
            echo "<td><a href='" . $cable->getLinkURL(). "'>".$cable->getName()."</a></td>";
         } else {
            echo "<td></td>";
         }

         echo "</tr>\n";
      }
      echo $header_begin.$header_bottom.$header_end;
      echo "</table>\n";

      if ($canedit) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
   }


   /**
    * Print the HTML array of the Socket associated to a Location
    *
    * @param $item Location
    *
    * @return void
   **/
   static function showForLocation($item) {
      global $DB;

      $ID       = $item->getField('id');
      $socket = new self();
      $item->check($ID, READ);
      $canedit  = $item->canEdit($ID);

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }
      $number = countElementsInTable('glpi_sockets', ['locations_id' => $ID ]);

      if ($canedit) {
         echo "<div class='first-bloc'>";
         // Minimal form for quick input.
         echo "<form action='".$socket->getFormURL()."' method='post'>";
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td class='b'>"._n('Network socket', 'Network sockets', 1)."</td>";
         echo "<td>".__('Name')."</td><td>";
         Html::autocompletionTextField($item, "name", ['value' => '']);
         echo "</td>";
         echo "<td>".SocketModel::getTypeName(1)."</td><td>";
         SocketModel::dropdown("socketmodels_id", []);
         echo "</td>";
         echo "<td>".__('Wiring side')."</td><td>";
         Socket::dropdownWiringSide("wiring_side", []);
         echo "</td>";
         echo "<td>".__('Itemtype')."</td><td>";
         Dropdown::showFromArray('itemtype', self::getSocketLinkTypes(), []);
         echo "</td>";

         echo "<td>";
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'>";
         echo "<input type='submit' name='execute_single' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td>";
         echo "</tr>\n";
         echo "</table>\n";
         Html::closeForm();

         // Minimal form for massive input.
         echo "<form action='".$socket->getFormURL()."' method='post'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td class='b'>"._n('Network socket', 'Network sockets', Session::getPluralNumber())."</td>";
         echo "<td>".__('Name')."</td><td>";
         echo "<input type='text' maxlength='100' size='10' name='_before'>&nbsp;";
         Dropdown::showNumber('_from', ['value' => 0,
                                             'min'   => 0,
                                             'max'   => 400]);
         echo "&nbsp;-->&nbsp;";
         Dropdown::showNumber('_to', ['value' => 0,
                                           'min'   => 0,
                                           'max'   => 400]);

         echo "&nbsp;<input type='text' maxlength='100' size='10' name='_after'><br>";
         echo "</td>";
         echo "<td>".SocketModel::getTypeName(1)."</td><td>";
         SocketModel::dropdown("socketmodels_id", []);
         echo "</td>";
         echo "<td>".__('Wiring side')."</td><td>";
         Socket::dropdownWiringSide("wiring_side", []);
         echo "</td>";
         echo "<td>".__('Itemtype')."</td><td>";
         Dropdown::showFromArray('itemtype', self::getSocketLinkTypes(), []);
         echo "</td>";

         echo "<td>";
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'>";
         echo "<input type='submit' name='execute_multi' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td>";
         echo "</tr>\n";
         echo "</table>\n";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".self::getTypeName(1)."</th>";
         echo "<th>".__('No item found')."</th></tr>";
         echo "</table>\n";
      } else {
         Html::printAjaxPager(sprintf(__('Network sockets for %s'), $item->getTreeLink()),
                              $start, $number);

         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $number),
                       'container'
                           => 'mass'.__CLASS__.$rand,
                       'specific_actions'
                           => ['purge' => _x('button', 'Delete permanently')]];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixe'><tr>";

         if ($canedit) {
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
         }

         echo "<th>".__('Name')."</th>"; // Name
         echo "<th>".__('Socket Model')."</th>"; // socket Model
         echo "<th>".__('Assets')."</th>"; // Asset
         echo "<th>".__('NetworkPort')."</th>"; // NetworkPort
         echo "<th>".__('Wiring side')."</th>"; // Wiring side
         echo "<th>".__('Comments')."</th>"; // Comment
         echo "</tr>\n";

         $crit = ['locations_id' => $ID,
                       'ORDER'        => 'name',
                       'START'        => $start,
                       'LIMIT'        => $_SESSION['glpilist_limit']];

         Session::initNavigateListItems('Socket',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($DB->request('glpi_sockets', $crit) as $data) {
            Session::addToNavigateListItems('Socket', $data["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
            }
            echo "<td><a href='".$socket->getFormURL();
            echo '?id='.$data['id']."'>".$data['name']."</a></td>";

            $socketmodel = new SocketModel();
            $socketmodel->getFromDB($data['socketmodels_id']);
            echo "<td>".$socketmodel->getLink()."</td>";

            $asset = new $data['itemtype']();
            $asset->getFromDB($data['items_id']);
            echo "<td>".$asset->getLink()."</td>";

            $networkport = new NetworkPort();
            $networkport->getFromDB($data['networkports_id']);
            echo "<td>".$networkport->getLink()."</td>";

            echo "<td>".self::getSides()[$data['wiring_side']]."</td>";
            echo "<td>".$data['comment']."</td>";
            echo "</tr>\n";
         }

         echo "</table>\n";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         Html::printAjaxPager(sprintf(__('Network sockets for %s'), $item->getTreeLink()),
                              $start, $number);

      }

      echo "</div>\n";
   }

   /**
    * Handled Multi add item
    *
    * @since 0.83 (before addMulti)
    *
    * @param $input array of values
   **/
   function executeAddMulti(array $input) {

      $this->check(-1, CREATE, $input);
      for ($i=$input["_from"]; $i<=$input["_to"]; $i++) {
         $input["name"] = $input["_before"].$i.$input["_after"];
         $this->add($input);
      }
      Event::log(0, "dropdown", 5, "setup",
               sprintf(__('%1$s adds several sockets'), $_SESSION["glpiname"]));
   }

   /**
    * @since 0.84
    *
    * @param $itemtype
    * @param $base            HTMLTableBase object
    * @param $super           HTMLTableSuperHeader object (default NULL
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $base->addHeader($column_name, _n('Network socket', 'Network sockets', 1), $super, $father);

   }


   /**
    * @since 0.84
    *
    * @param $row             HTMLTableRow object (default NULL)
    * @param $item            CommonDBTM object (default NULL)
    * @param $father          HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                            HTMLTableCell $father = null, $options = []) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $row->addCell($row->getHeaderByName($column_name),
                    Dropdown::getDropdownName("glpi_sockets", $item->fields["sockets_id"]),
                    $father);
   }

}
