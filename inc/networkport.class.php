<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// NetworkPort class
class NetworkPort extends CommonDBChild {

   // From CommonDBChild
   public $itemtype  = 'itemtype';
   public $items_id  = 'items_id';
   public $dohistory = true;


   static function getTypeName() {
      global $LANG;

      return $LANG['networking'][4];
   }


   function canCreate() {

      if (isset($this->fields['itemtype'])) {
         $item = new $this->fields['itemtype']();
         return $item->canCreate();
      }

      return false;
   }


   function canView() {

      if (isset($this->fields['itemtype'])) {
         $item = new $this->fields['itemtype']();
         return $item->canView();
      }

      return false;
   }


   function post_updateItem($history=1) {

      // Only netpoint updates : ip and mac may be different.
      $tomatch = array("netpoints_id");
      $updates = array_intersect($this->updates, $tomatch);

      if (count($updates)) {
         $save_ID = $this->fields["id"];
         $n       = new NetworkPort_NetworkPort;

         if ($this->fields["id"]=$n->getOppositeContact($save_ID)) {
            $this->updateInDB($updates);
         }

         $this->fields["id"] = $save_ID;
      }
   }


   function prepareInputForUpdate($input) {

      // Is a preselected mac adress selected ?
      if (isset($input['pre_mac']) && !empty($input['pre_mac'])) {
         $input['mac'] = $input['pre_mac'];
         unset($input['pre_mac']);
      }
      return $input;
   }


   function prepareInputForAdd($input) {

      // Not attached to itemtype -> not added
      if (!isset($input['itemtype'])
          || empty($input['itemtype'])
          || !class_exists($input['itemtype'])
          || !isset($input['items_id'])
          || $input['items_id'] <= 0) {
         return false;
      }

      if (isset($input["logical_number"]) && strlen($input["logical_number"])==0) {
         unset($input["logical_number"]);
      }

      $item = new $input['itemtype']();
      if ($item->getFromDB($input['items_id'])) {
         $input['entities_id']  = $item->getEntityID();
         $input['is_recursive'] = intval($item->isRecursive());
         return $input;
      }
      // Item not found
      return false;
   }


   function pre_deleteItem() {

      $nn = new NetworkPort_NetworkPort();
      if ($nn->getFromDBForNetworkPort($this->fields["id"])) {
         $nn->delete($nn->fields);
      }
      return true;
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '".$this->fields['id']."'
                      OR `networkports_id_2` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }


   /**
    * Get port opposite port ID if linked item
    *
    * @param $ID networking port ID
    *
    * @return ID of the NetworkPort found, false if not found
   **/
   function getContact($ID) {

      $wire = new NetworkPort_NetworkPort;
      if ($contact_id = $wire->getOppositeContact($ID)) {
         return $contact_id;
      }
      return false;
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong[1]  = $LANG['title'][26];
      $ong[12] = $LANG['title'][38];

      return $ong;
   }


   /**
    * Delete All connection of the given network port
    *
    * @param $ID ID of the port
    *
    * @return true on success
   **/
   function resetConnections($ID) {
   }


   /**
    * Make a select box for  connected port
    *
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is networkports_id)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *
    * @param $ID ID of the current port to connect
    * @param $options possible options
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownConnect($ID,$options=array()) {
      global $LANG, $CFG_GLPI;

      $p['name']        = 'networkports_id';
      $p['comments']    = 1;
      $p['entity']      = -1;
      $p['entity_sons'] = false;

     if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      // Manage entity_sons
      if (!($p['entity']<0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            echo "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities', $p['entity']);
         }
      }

      $rand = mt_rand();
      echo "<select name='itemtype[$ID]' id='itemtype$rand'>";
      echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";

      foreach ($CFG_GLPI["netport_types"] as $key => $itemtype) {
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            echo "<option value='".$itemtype."'>".$item->getTypeName()."</option>";
         } else {
            unset($CFG_GLPI["netport_types"][$key]);
         }
      }
      echo "</select>";

      $params = array('itemtype'        => '__VALUE__',
                      'entity_restrict' => $p['entity'],
                      'current'         => $ID,
                      'comments'        => $p['comments'],
                      'myname'          => $p['name']);

      ajaxUpdateItemOnSelectEvent("itemtype$rand", "show_".$p['name']."$rand",
                                  $CFG_GLPI["root_doc"]."/ajax/dropdownConnectPortDeviceType.php",
                                  $params);

      echo "<span id='show_".$p['name']."$rand'>&nbsp;</span>\n";

      return $rand;
   }


   /**
    * Show ports for an item
    *
    * @param $itemtype integer : item type
    * @param $ID integer : item ID
    * @param $withtemplate integer : withtemplate param
   **/
   static function showForItem($itemtype, $ID, $withtemplate = '') {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();

      if (!class_exists($itemtype)) {
         return false;
      }

      $item = new $itemtype();
      if (!haveRight('networking','r') || !$item->can($ID, 'r')) {
         return false;
      }

      $canedit = $item->can($ID, 'w');

      // Show Add Form
      if ($canedit
          && (empty($withtemplate) || $withtemplate !=2)) {
         echo "\n<div class='firstbloc'><table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] .
               "/front/networkport.form.php?items_id=$ID&amp;itemtype=$itemtype'><strong>".
               $LANG['networking'][19]."</strong></a></td>\n";
         echo "<td class='tab_bg_2 center' width='50%'>";
         echo "<a href='" . $CFG_GLPI["root_doc"] .
               "/front/networkport.form.php?items_id=$ID&amp;itemtype=$itemtype&amp;several=1'>
               <strong>".$LANG['networking'][46]."</strong></a></td>\n";
         echo "</tr></table></div>\n";
      }

      initNavigateListItems('NetworkPort', $item->getTypeName()." = ".$item->getName());

      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '$itemtype'
                ORDER BY `name`,
                         `logical_number`";

      if ($result = $DB->query($query)) {
         echo "<div class='spaced'>";

         if ($DB->numrows($result) != 0) {
            $colspan = 9;

            if ($withtemplate != 2) {
               if ($canedit) {
                  $colspan++;
                  echo "\n<form id='networking_ports$rand' name='networking_ports$rand' method='post'
                        action='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php'>\n";
               }
            }

            echo "<table class='tab_cadre_fixe'>\n";

            echo "<tr><th colspan='$colspan'>\n";
            if ($DB->numrows($result)==1) {
               echo $LANG['networking'][12];
            } else {
               echo $LANG['networking'][11];
            }
            echo "&nbsp;:&nbsp;".$DB->numrows($result)."</th></tr>\n";

            echo "<tr>";
            if ($withtemplate != 2 && $canedit) {
               echo "<th>&nbsp;</th>\n";
            }
            echo "<th>#</th>\n";
            echo "<th>" . $LANG['common'][16] . "</th>\n";
            echo "<th>" . $LANG['networking'][51] . "</th>\n";
            echo "<th>" . $LANG['networking'][14] . "<br>" . $LANG['networking'][15] . "</th>\n";
            echo "<th>" . $LANG['networking'][60] . "&nbsp;/&nbsp;" . $LANG['networking'][61]."<br>"
                        . $LANG['networking'][59] . "</th>\n";
            echo "<th>" . $LANG['networking'][56] . "</th>\n";
            echo "<th>" . $LANG['common'][65] . "</th>\n";
            echo "<th>" . $LANG['networking'][17] . "&nbsp;:</th>\n";
            echo "<th>" . $LANG['networking'][14] . "<br>" . $LANG['networking'][15] . "</th></tr>\n";

            $i = 0;
            $netport = new NetworkPort();

            while ($devid = $DB->fetch_row($result)) {
               $netport->getFromDB(current($devid));
               addToNavigateListItems('NetworkPort', $netport->fields["id"]);

               echo "<tr class='tab_bg_1'>\n";
               if ($withtemplate != 2 && $canedit) {
                  echo "<td class='center' width='20'>";
                  echo "<input type='checkbox' name='del_port[".$netport->fields["id"]."]' value='1'>";
                  echo "</td>\n";
               }
               echo "<td class='center'><strong>";
               if ($canedit && $withtemplate != 2) {
                  echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?id=" .
                        $netport->fields["id"] . "\">";
               }
               echo $netport->fields["logical_number"];
               if ($canedit && $withtemplate != 2) {
                  echo "</a>";
               }
               echo "</strong>";
               showToolTip($netport->fields['comment']);
               echo "</td>\n";
               echo "<td>" . $netport->fields["name"] . "</td>\n";
               echo "<td>".Dropdown::getDropdownName("glpi_netpoints",
                                                     $netport->fields["netpoints_id"])."</td>\n";
               echo "<td>" .$netport->fields["ip"]. "<br>" .$netport->fields["mac"] . "</td>\n";
               echo "<td>" .$netport->fields["netmask"]. "&nbsp;/&nbsp;".
                            $netport->fields["subnet"]."<br>".$netport->fields["gateway"]."</td>\n";
               // VLANs
               echo "<td>";
               NetworkPort_Vlan::showForNetworkPort($netport->fields["id"], $canedit, $withtemplate);
               echo "</td>\n";
               echo "<td>".Dropdown::getDropdownName("glpi_networkinterfaces",
                                                     $netport->fields["networkinterfaces_id"])."</td>\n";
               echo "<td width='300' class='tab_bg_2'>";
               self::showConnection($item, $netport, $withtemplate);
               echo "</td>\n";
               echo "<td class='tab_bg_2'>";
               if ($netport->getContact($netport->fields["id"])) {
                  echo $netport->fields["ip"] . "<br>";
                  echo $netport->fields["mac"];
               }
               echo "</td></tr>\n";
            }
            echo "</table>\n";

            if ($canedit && $withtemplate != 2) {
               openArrowMassive("networking_ports$rand", true);
               Dropdown::showForMassiveAction('NetworkPort');
               closeArrowMassive();
            }

            if ($canedit && $withtemplate != 2) {
               echo "</form>";
            }

         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['networking'][10]."</th></tr>";
            echo "</table>";
         }
         echo "</div>";
      }
   }


   /**
    * Display a connection of a networking port
    *
    * @param $device1 the device of the port
    * @param $netport to be displayed
    * @param $withtemplate
   **/
   static function showConnection(& $device1, & $netport, $withtemplate = '') {
      global $CFG_GLPI, $LANG;

      if (!$device1->can($device1->fields["id"], 'r')) {
         return false;
      }

      $contact = new NetworkPort_NetworkPort;
      $canedit = $device1->can($device1->fields["id"], 'w');
      $ID      = $netport->fields["id"];

      if ($contact_id = $contact->getOppositeContact($ID)) {
         $netport->getFromDB($contact_id);

         if (class_exists($netport->fields["itemtype"])) {
            $device2 = new $netport->fields["itemtype"]();

            if ($device2->getFromDB($netport->fields["items_id"])) {
               echo "\n<table width='100%'>\n";
               echo "<tr " . ($device2->fields["is_deleted"] ? "class='tab_bg_2_2'" : "") . ">";
               echo "<td><strong>";

               if ($device2->can($device2->fields["id"], 'r')) {
                  echo $netport->getLink();
                  echo "</strong>\n";
                  showToolTip($netport->fields['comment']);
                  echo "&nbsp;".$LANG['networking'][25] . " <strong>";
                  echo $device2->getLink();
                  echo "</strong>";

                  if ($device1->fields["entities_id"] != $device2->fields["entities_id"]) {
                     echo "<br>(". Dropdown::getDropdownName("glpi_entities",
                                                            $device2->getEntityID()) .")";
                  }

                  // 'w' on dev1 + 'r' on dev2 OR 'r' on dev1 + 'w' on dev2
                  if ($canedit || $device2->can($device2->fields["id"], 'w')) {
                     echo "</td>\n<td class='right'><strong>";

                     if ($withtemplate != 2) {
                        echo "<a href=\"".$netport->getFormURL()."?disconnect=".
                              "disconnect&amp;id=".$contact->fields['id']."\">" .
                              $LANG['buttons'][10] . "</a>";
                     } else {
                        "&nbsp;";
                     }

                     echo "</strong>";
                  }

               } else {
                  if (rtrim($netport->fields["name"]) != "") {
                     echo $netport->fields["name"];
                  } else {
                     echo $LANG['common'][0];
                  }
                  echo "</strong> " . $LANG['networking'][25] . " <strong>";
                  echo $device2->getName();
                  echo "</strong><br>(" .Dropdown::getDropdownName("glpi_entities",
                                                                   $device2->getEntityID()) .")";
               }

               echo "</td></tr></table>\n";
            }
         }

      } else {
         echo "\n<table width='100%'><tr>";

         if ($canedit) {
            echo "<td class='left'>";

            if ($withtemplate != 2 && $withtemplate != 1) {
               NetworkPort::dropdownConnect($ID,
                                            array('name'        => 'dport',
                                                  'entity'      => $device1->fields["entities_id"],
                                                  'entity_sons' => $device1->isRecursive()));
            } else {
               echo "&nbsp;";
            }

            echo "</td>\n";
         }

         echo "<td><div id='not_connected_display$ID'>" . $LANG['connect'][1] . "</div></td>";
         echo "</tr></table>\n";
      }
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!isset($options['several'])) {
         $options['several'] = false;
      }

      if (!haveRight("networking", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $input = array('itemtype' => $options["itemtype"],
                        'items_id' => $options["items_id"]);
         // Create item
         $this->check(-1, 'w', $input);
      }

      $type = $this->fields['itemtype'];
      $link = NOT_AVAILABLE;

      if (class_exists($this->fields['itemtype'])) {
         $item = new $this->fields['itemtype']();
         $type = $item->getTypeName();

         if ($item->getFromDB($this->fields["items_id"])) {
            $link = $item->getLink();
         } else {
            return false;
         }

      } else {
         // item is mandatory (for entity)
         return false;
      }

      // Ajout des infos deja remplies
      if (isset ($_POST) && !empty ($_POST)) {
         foreach ($netport->fields as $key => $val) {
            if ($key!='id' && isset($_POST[$key])) {
               $netport->fields[$key] = $_POST[$key];
            }
         }
      }
      $this->showTabs($ID);

      $options['entities_id'] = $item->getField('entities_id');
      $this->showFormHeader($options);

      $show_computer_mac = false;
      if ((!empty($this->fields['itemtype']) || !$options['several'])
          && $this->fields['itemtype'] == 'Computer') {
         $show_computer_mac = true;
      }

      echo "<tr class='tab_bg_1'><td>$type&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
      }

      echo $link. "</td>\n";
      $colspan = 9;

      if ($show_computer_mac) {
         $colspan += 2;
      }

      if (!$options['several']) {
         $colspan ++;
      }
      echo "<td rowspan='$colspan'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='$colspan' class='middle'>";
      echo "<textarea cols='45' rows='11' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
         echo "<td>";
         autocompletionTextField($this,"logical_number", array('size' => 5));
         echo "</td></tr>\n";

      } else {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
         echo "<td>";
         echo "<input type='hidden' name='several' value='yes'>";
         echo "<input type='hidden' name='logical_number' value=''>\n";
         echo $LANG['networking'][47] . "&nbsp;:&nbsp;";
         Dropdown::showInteger('from_logical_number', 0, 0, 100);
         echo "&nbsp;".$LANG['networking'][48] . "&nbsp;:&nbsp;";
         Dropdown::showInteger('to_logical_number', 0, 0, 100);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][65] . "&nbsp;:</td>\n<td>";
      Dropdown::show('NetworkInterface', array('value' => $this->fields["networkinterfaces_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][14] . "&nbsp;:</td>\n<td>";
      autocompletionTextField($this, "ip");
      echo "</td></tr>\n";

      // Show device MAC adresses
      if ($show_computer_mac) {

         $comp = new Computer();
         $comp->getFromDB($this->fields['items_id']);
         $macs = Computer_Device::getMacAddr($comp);

         if (count($macs) > 0) {
            echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][15] . "&nbsp;:</td>\n<td>";
            echo "<select name='pre_mac'>\n";
            echo "<option value=''>".DROPDOWN_EMPTY_VALUE."</option>\n";

            foreach ($macs as $key => $val) {
               echo "<option value='" . $val . "' >$val</option>\n";
            }

            echo "</select></td></tr>\n";

            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='center'>" . $LANG['networking'][57];
            echo "</td></tr>\n";
         }
      }

      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][15] . "&nbsp;:</td>\n<td>";
      autocompletionTextField($this, "mac");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][60] . "&nbsp;:</td>\n<td>";
      autocompletionTextField($this, "netmask");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][59] . "&nbsp;:</td>\n<td>";
      autocompletionTextField($this, "gateway");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][61] . "&nbsp;:</td>\n<td>";
      autocompletionTextField($this, "subnet");
      echo "</td></tr>\n";

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][51] . "&nbsp;:</td>\n";
         echo "<td>";
         Netpoint::dropdownNetpoint("netpoints_id", $this->fields["netpoints_id"],
                                    $item->fields['locations_id'], 1, $item->getEntityID(),
                                    $this->fields["itemtype"]);
         echo "</td></tr>\n";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();
   }


   /**
    * Get an Object ID by his IP address (only if one result is found in the entity)
    *
    * @param $value the ip address
    * @param $type type to search : MAC or IP
    * @param $entity the entity to look for
    *
    * @return an array containing the object ID
    *         or an empty array is no value of serverals ID where found
   **/
   static function getUniqueObjectIDByIPAddressOrMac($value, $type = 'IP', $entity) {
      global $DB;

      switch ($type) {
         case "MAC" :
            $field = "mac";
            break;

         default :
            $field = "ip";
      }

      //Try to get all the object (not deleted, and not template)
      //with a network port having the specified IP, in a given entity
      $query = "SELECT `gnp`.`items_id`,
                       `gnp`.`id` AS portID,
                       `gnp`.`itemtype` AS itemtype
                FROM `glpi_networkports` AS gnp
                LEFT JOIN `glpi_computers` AS gc
                     ON (`gnp`.`items_id` = `gc`.`id`
                         AND `gc`.`entities_id` = '$entity'
                         AND `gc`.`is_deleted` = '0'
                         AND `gc`.`is_template` = '0'
                         AND `itemtype` = 'Computer')
                LEFT JOIN `glpi_printers` AS gp
                     ON (`gnp`.`items_id` = `gp`.`id`
                         AND `gp`.`entities_id` = '$entity'
                         AND `gp`.`is_deleted` = '0'
                         AND `gp`.`is_template` = '0'
                         AND `itemtype` = 'Printer')
                LEFT JOIN `glpi_networkequipments` AS gn
                     ON (`gnp`.`items_id` = `gn`.`id`
                         AND `gn`.`entities_id` = '$entity'
                         AND `gn`.`is_deleted` = '0'
                         AND `gn`.`is_template` = '0'
                         AND `itemtype` = 'NetworkEquipment')
                LEFT JOIN `glpi_phones` AS gph
                     ON (`gnp`.`items_id` = `gph`.`id`
                         AND `gph`.`entities_id` = '$entity'
                         AND `gph`.`is_deleted` = '0'
                         AND `gph`.`is_template` = '0'
                         AND `itemtype` = 'Phone')
                LEFT JOIN `glpi_peripherals` AS gpe
                     ON (`gnp`.`items_id` = `gpe`.`id`
                         AND `gpe`.`entities_id` = '$entity'
                         AND `gpe`.`is_deleted` = '0'
                         AND `gpe`.`is_template` = '0'
                         AND `itemtype` = 'Peripheral')
                WHERE `gnp`.`$field` = '" . $value . "'";

      $result = $DB->query($query);

      //3 possibilities :
      //0 found : no object with a network port have this ip.
                  //Look into networkings object to see if,maybe, one have it
      //1 found : one object have a network port with the ip -> good, possible to link
      //2 found : one object have a network port with this ip, and the port is link to another one
                  //-> get the object by removing the port connected to a network device
      switch ($DB->numrows($result)) {
         case 0 :
            //No result found with the previous request.
            //Try to look for IP in the glpi_networkequipments table directly
            $query = "SELECT `id`
                      FROM `glpi_networkequipments`
                      WHERE UPPER(`$field`) = UPPER('$value')
                            AND `entities_id` = '$entity'";
            $result = $DB->query($query);
            if ($DB->numrows($result) == 1) {
               return array ("id"       => $DB->result($result, 0, "id"),
                             "itemtype" => 'NetworkEquipment');
            }
            return array ();

         case 1 :
            $port = $DB->fetch_array($result);
            return array ("id"       => $port["items_id"],
                          "itemtype" => $port["itemtype"]);

         case 2 :
            //2 ports found with the same IP
            //We can face different configurations :
            //the 2 ports aren't linked -> can do nothing (how to know which one is the good one)
            //the 2 ports are linked but no ports are connected on a network device
            //(for example 2 computers connected)-> can do nothin (how to know which one is the good one)
            //the 2 ports are linked and one port in connected on a network device
            //-> use the port not connected on the network device as the good one
            $port1 = $DB->fetch_array($result);
            $port2 = $DB->fetch_array($result);
            //Get the 2 ports informations and try to see if one port is connected on a network device
            $network_port = -1;
            if ($port1["itemtype"] == 'NetworkEquipment') {
               $network_port = 1;
            } else if ($port2["itemtype"] == 'NetworkEquipment') {
               $network_port = 2;
            }
            //If one port is connected on a network device
            if ($network_port != -1) {
               //If the 2 ports are linked each others
               $query = "SELECT `id`
                         FROM `glpi_networkports_networkports`
                         WHERE (`networkports_id_1` = '".$port1["portID"]."'
                                AND `networkports_id_2` = '".$port2["portID"]."')
                               OR (`networkports_id_1` = '".$port2["portID"]."'
                                   AND `networkports_id_2` = '".$port1["portID"]."')";
               $query = $DB->query($query);
               if ($DB->numrows($query) == 1) {
                  return array ("id"       => ($network_port == 1 ? $port2["items_id"]
                                                                  : $port1["items_id"]),
                                "itemtype" => ($network_port == 1 ? $port2["itemtype"]
                                                                  : $port1["itemtype"]));
               }
            }
            return array ();

         default :
            return array ();
      }
   }


   /**
    * Look for a computer or a network device with a fully qualified domain name in an entity
    *
    * @param fqdn fully qualified domain name
    * @param entity the entity
    *
    * @return an array with the ID and itemtype or an empty array if no unique object is found
   **/
   static function getUniqueObjectIDByFQDN($fqdn, $entity) {

      $types = array('Computer', 'NetworkEquipment', 'Printer');

      foreach ($types as $itemtype) {
         $result = $this->getUniqueObjectByFDQNAndType($fqdn, $itemtype, $entity);

         if (!empty ($result)) {
            return $result;
         }
      }

      return array ();
   }


   /**
    * Look for a specific type of device with a fully qualified domain name in an entity
    *
    * @param fqdn fully qualified domain name
    * @param $itemtype the type of object to look for
    * @param entity the entity
    *
    * @return an array with the ID and itemtype or an empty array if no unique object is found
   **/
   static function getUniqueObjectByFDQNAndType($fqdn, $itemtype, $entity) {
      global $DB;

      if (class_exists($itemtype)) {
         $item = new $itemtype();

         $query = "SELECT `obj.id`
                   FROM " . $item->getTable() . " AS obj,
                        `glpi_domains` AS gdd
                   WHERE `obj.entities_id` = '$entity'
                         AND `obj`.`domains_id` = `gdd`.`id`
                         AND LOWER('$fqdn') = (CONCAT(LOWER(`obj`.`name`), '.' ,
                                                      LOWER(`gdd`.`name`)))";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $datas = $DB->fetch_array($result);
            return array ("id"       => $datas["id"],
                          "itemtype" => $itemtype);
         }
      }
      return array ();
   }


   static function getSearchOptionsToAdd ($itemtype) {
      global $LANG;

      $tab = array();

      $tab['network'] = $LANG['setup'][88];

      $joinparams=array('jointype' => 'itemtype_item');
      if ($itemtype=='Computer') {
         $joinparams['addjoin'] = array('table' => 'glpi_computers_devicenetworkcards',
                                                      'joinparams' => array('jointype' => 'child'));
      }

      $tab[20]['table']         = 'glpi_networkports';
      $tab[20]['field']         = 'ip';
      $tab[20]['name']          = $LANG['networking'][14];
      $tab[20]['forcegroupby']  = true;
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = $joinparams;

      $tab[21]['table']         = 'glpi_networkports';
      $tab[21]['field']         = 'mac';
      $tab[21]['name']          = $LANG['networking'][15];
      $tab[21]['forcegroupby']  = true;
      $tab[21]['massiveaction'] = false;
      $tab[21]['joinparams']    = $joinparams;

      $tab[83]['table']         = 'glpi_networkports';
      $tab[83]['field']         = 'netmask';
      $tab[83]['name']          = $LANG['networking'][60];
      $tab[83]['forcegroupby']  = true;
      $tab[83]['massiveaction'] = false;
      $tab[83]['joinparams']    = $joinparams;


      $tab[84]['table']         = 'glpi_networkports';
      $tab[84]['field']         = 'subnet';
      $tab[84]['name']          = $LANG['networking'][61];
      $tab[84]['forcegroupby']  = true;
      $tab[84]['massiveaction'] = false;
      $tab[84]['joinparams']    = $joinparams;

      $tab[85]['table']         = 'glpi_networkports';
      $tab[85]['field']         = 'gateway';
      $tab[85]['name']          = $LANG['networking'][59];
      $tab[85]['forcegroupby']  = true;
      $tab[85]['massiveaction'] = false;
      $tab[85]['joinparams']    = $joinparams;

      $tab[22]['table']         = 'glpi_netpoints';
      $tab[22]['field']         = 'name';
      $tab[22]['name']          = $LANG['networking'][51];
      $tab[22]['forcegroupby']  = true;
      $tab[22]['massiveaction'] = false;
      $tab[22]['joinparams']    = array('beforejoin' => array('table' => 'glpi_networkports',
                                                              'joinparams' => $joinparams));

      $tab[87]['table']         = 'glpi_networkinterfaces';
      $tab[87]['field']         = 'name';
      $tab[87]['name']          = $LANG['common'][65];
      $tab[87]['forcegroupby']  = true;
      $tab[87]['massiveaction'] = false;
      $tab[87]['joinparams']    = array('beforejoin' => array('table' => 'glpi_networkports',
                                                              'joinparams' => $joinparams));

      $tab[88]['table']         = 'glpi_vlans';
      $tab[88]['field']         = 'name';
      $tab[88]['name']          = $LANG['networking'][56];
      $tab[88]['forcegroupby']  = true;
      $tab[88]['massiveaction'] = false;

      return $tab;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG["common"][16];
      $tab[1]['type']          = 'text';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'logical_number';
      $tab[3]['name']     = $LANG["networking"][21];
      $tab[3]['datatype'] = 'integer';

      $tab[4]['table'] = $this->getTable();
      $tab[4]['field'] = 'mac';
      $tab[4]['name']  = $LANG["device_iface"][2];

      $tab[5]['table'] = $this->getTable();
      $tab[5]['field'] = 'ip';
      $tab[5]['name']  = $LANG["networking"][14];

      $tab[6]['table'] = $this->getTable();
      $tab[6]['field'] = 'netmask';
      $tab[6]['name']  = $LANG["networking"][60];

      $tab[7]['table'] = $this->getTable();
      $tab[7]['field'] = 'subnet';
      $tab[7]['name']  = $LANG["networking"][61];

      $tab[8]['table'] = $this->getTable();
      $tab[8]['field'] = 'gateway';
      $tab[8]['name']  = $LANG["networking"][59];

      $tab[9]['table'] = 'glpi_netpoints';
      $tab[9]['field'] = 'name';
      $tab[9]['name']  = $LANG["networking"][51];

      $tab[10]['table'] = 'glpi_networkinterfaces';
      $tab[10]['field'] = 'name';
      $tab[10]['name']  = $LANG['setup'][9];

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[20]['table']        = $this->getTable();
      $tab[20]['field']        = 'itemtype';
      $tab[20]['name']         = $LANG['common'][17];
      $tab[20]['datatype']     = 'itemtype';
      $tab[20]['massiveation'] = false;

      $tab[21]['table']        = $this->getTable();
      $tab[21]['field']        = 'items_id';
      $tab[21]['name']         = 'id';
      $tab[21]['datatype']     = 'integer';
      $tab[21]['massiveation'] = false;

      return $tab;
   }

}

?>
