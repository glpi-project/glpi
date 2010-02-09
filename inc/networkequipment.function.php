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

///// Manage NetworkEquipment /////

///// Manage Ports on Devices /////


function showPortVLAN($ID, $canedit, $withtemplate) {
   global $DB, $CFG_GLPI, $LANG;

   $used = array();

   $query = "SELECT *
             FROM `glpi_networkports_vlans`
             WHERE `networkports_id` = '$ID'";
   $result = $DB->query($query);
   if ($DB->numrows($result) > 0) {
      echo "\n<table>";
      while ($line = $DB->fetch_array($result)) {
         $used[]=$line["vlans_id"];
         echo "<tr><td>" . Dropdown::getDropdownName("glpi_vlans", $line["vlans_id"]);
         echo "</td>\n<td>";
         if ($canedit) {
            echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?unassign_vlan=".
                  "unassigned&amp;id=" . $line["id"] . "'>";
            echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete2.png\" alt='" .
                   $LANG['buttons'][6] . "' title='" . $LANG['buttons'][6] . "'></a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td></tr>\n";
      }
      echo "</table>";
   } else {
      echo "&nbsp;";
   }
   return $used;
}

function showPortVLANForm ($ID) {
   global $DB, $CFG_GLPI, $LANG;

   if ($ID) {
      echo "\n<div class='center'>";
      echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php'>";
      echo "<input type='hidden' name='id' value='$ID'>\n";

      echo "<table class='tab_cadre'>";
      echo "<tr><th>" . $LANG['setup'][90] . "</th></tr>\n";
      echo "<tr class='tab_bg_2'><td>";
      $used=showPortVLAN($ID, 0);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>";
      echo $LANG['networking'][55] . "&nbsp;:&nbsp;";
      Dropdown::show('Vlan', array('used' => $used));
      echo "&nbsp;<input type='submit' name='assign_vlan' value='" . $LANG['buttons'][3] .
                   "' class='submit'>";
      echo "</td></tr>\n";

      echo "</table></form></div>";
   }
}




function showNetportForm($target, $ID, $ondevice, $devtype, $several) {
   global $CFG_GLPI, $LANG;

   if (!haveRight("networking", "r")) {
      return false;
   }

   $netport = new NetworkPort;
   if ($ID) {
      $netport->getFromDB($ID);
      $netport->getDeviceData($ondevice=$netport->fields["items_id"],
                              $devtype=$netport->fields["itemtype"]);
   } else {
      $netport->getDeviceData($ondevice, $devtype);
      $netport->getEmpty();
   }

   // Ajout des infos deja remplies
   if (isset ($_POST) && !empty ($_POST)) {
      foreach ($netport->fields as $key => $val) {
         if ($key != 'id' && isset ($_POST[$key])) {
            $netport->fields[$key] = $_POST[$key];
         }
      }
   }
   $netport->showTabs($ID);

   echo "\n<form method='post' action=\"$target\"><div class='center' id='tabsbody'>\n";
   echo "<table class='tab_cadre_fixe'>\n<tr>";
   echo "<th colspan='2'>" . $LANG['networking'][20] . "&nbsp;:</th>";
   echo "</tr>\n";

   $type = $netport->itemtype;
   $link = NOT_AVAILABLE;
   if (class_exists($netport->itemtype)) {
      $item = new $netport->itemtype();
      $type = $item->getTypeName();
      if ($item->getFromDB($netport->device_ID)) {
         $link=$item->getLink();
      }
   }
   echo "<tr class='tab_bg_1'><td>$type:</td>\n<td>";
   echo $link. "</td></tr>\n";

   if ($several != "yes") {
      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($netport,"logical_number", array('size'=>5));
      echo "</td></tr>\n";
   } else {
      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
      echo "<input type='hidden' name='several' value='yes'>";
      echo "<input type='hidden' name='logical_number' value=''>\n";
      echo "<td>";
      echo $LANG['networking'][47] . "&nbsp;:&nbsp;";
      Dropdown::showInteger('from_logical_number', 0, 0, 100);
      echo $LANG['networking'][48] . "&nbsp;:&nbsp;";
      Dropdown::showInteger('to_logical_number', 0, 0, 100);
      echo "</td></tr>\n";
   }

   echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>\n";
   echo "<td>";
   autocompletionTextField($netport, "name");
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['common'][65] . "&nbsp;:</td>\n<td>";
   Dropdown::show('NetworkInterface', array('value'  => $netport->fields["networkinterfaces_id"]));
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][14] . "&nbsp;:</td>\n<td>";
   autocompletionTextField($netport, "ip");
   echo "</td></tr>\n";

   // Show device MAC adresses
   if ((!empty ($netport->itemtype) && $netport->itemtype == 'Computer')
       || ($several != "yes" && $devtype == 'Computer')) {

      $comp = new Computer();
      if (!empty ($netport->itemtype)) {
         $comp->getFromDB($netport->device_ID);
      } else {
         $comp->getFromDB($ondevice);
      }
      $macs = Computer_Device::getMacAddr($comp);

      if (count($macs) > 0) {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][15] . "&nbsp;:</td>\n<td>";
         echo "<select name='pre_mac'>\n";
         echo "<option value=''>------</option>\n";
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
   autocompletionTextField($netport, "mac");
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][60] . "&nbsp;:</td>\n<td>";
   autocompletionTextField($netport, "netmask");
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][59] . "&nbsp;:</td>\n<td>";
   autocompletionTextField($netport, "gateway");
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][61] . "&nbsp;:</td>\n<td>";
   autocompletionTextField($netport, "subnet");
   echo "</td></tr>\n";

   if ($several != "yes") {
      echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][51] . "&nbsp;:</td>\n";
      echo "<td>";
      Netpoint::dropdownNetpoint("netpoints_id", $netport->fields["netpoints_id"], $netport->locations_id, 1,
                       $netport->entities_id, ($ID ? $netport->fields["itemtype"] : $devtype));
      echo "</td></tr>\n";
   }
   if ($ID) {
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>";
      echo "<input type='submit' name='update' value=\"" . $LANG['buttons'][7] . "\" class='submit'>";
      echo "</td>\n";

      echo "<td class='center'>";
      echo "<input type='hidden' name='id' value=" . $netport->fields["id"] . ">\n";
      echo "<input type='submit' name='delete' value=\"" . $LANG['buttons'][6] . "\" class='submit' " .
             "OnClick='return window.confirm(\"" . $LANG['common'][50] . "\");'>";
      echo "</td></tr>\n";

   } else {
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='3'>";
      echo "<input type='hidden' name='items_id' value='$ondevice'>\n";
      echo "<input type='hidden' name='itemtype' value='$devtype'>\n";
      echo "<input type='submit' name='add' value=\"" . $LANG['buttons'][8] . "\" class='submit'>";
      echo "</td></tr>\n";
   }

   echo "</table></div></form>\n";

   echo "<div id='tabcontent'></div>";
   echo "<script type='text/javascript'>loadDefaultTab();</script>";
}




/**
 * Get an Object ID by his IP address (only if one result is found in the entity)
 * @param $value the ip address
 * @param $type type to search : MAC or IP
 * @param $entity the entity to look for
 * @return an array containing the object ID or an empty array is no value of serverals ID where found
 */
function getUniqueObjectIDByIPAddressOrMac($value, $type = 'IP', $entity) {
   global $DB;

   switch ($type) {
      case "MAC" :
         $field = "mac";
         break;

      default :
         $field = "ip";
         break;
   }

   //Try to get all the object (not deleted, and not template)
   //with a network port having the specified IP, in a given entity
   $query = "SELECT `gnp`.`items_id`, `gnp`.`id` AS portID, `gnp`.`itemtype` AS itemtype
             FROM `glpi_networkports` AS gnp
             LEFT JOIN `glpi_computers` AS gc ON (`gnp`.`items_id` = `gc`.`id`
                                                  AND `gc`.`entities_id` = '$entity'
                                                  AND `gc`.`is_deleted` = '0'
                                                  AND `gc`.`is_template` = '0'
                                                  AND `itemtype` = 'Computer')
             LEFT JOIN `glpi_printers` AS gp ON (`gnp`.`items_id` = `gp`.`id`
                                                 AND `gp`.`entities_id` = '$entity'
                                                 AND `gp`.`is_deleted` = '0'
                                                 AND `gp`.`is_template` = '0'
                                                 AND `itemtype` = 'Printer')
             LEFT JOIN `glpi_networkequipments` AS gn ON (`gnp`.`items_id` = `gn`.`id`
                                                          AND `gn`.`entities_id` = '$entity'
                                                          AND `gn`.`is_deleted` = '0'
                                                          AND `gn`.`is_template` = '0'
                                                          AND `itemtype` = 'NetworkEquipment')
             LEFT JOIN `glpi_phones` AS gph ON (`gnp`.`items_id` = `gph`.`id`
                                                AND `gph`.`entities_id` = '$entity'
                                                AND `gph`.`is_deleted` = '0'
                                                AND `gph`.`is_template` = '0'
                                                AND `itemtype` = 'Phone')
             LEFT JOIN `glpi_peripherals` AS gpe ON (`gnp`.`items_id` = `gpe`.`id`
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
            return array ("id" => $DB->result($result, 0, "id"),
                          "itemtype" => 'NetworkEquipment');
         } else {
            return array ();
         }

      case 1 :
         $port = $DB->fetch_array($result);
         return array ("id" => $port["items_id"],
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
               return array ("id" => ($network_port == 1 ? $port2["items_id"] : $port1["items_id"]),
                            "itemtype" => ($network_port == 1 ? $port2["itemtype"] : $port1["itemtype"]));
            }
         }
         return array ();

      default :
         return array ();
   }
}

/**
 * Look for a computer or a network device with a fully qualified domain name in an entity
 * @param fqdn fully qualified domain name
 * @param entity the entity
 * @return an array with the ID and itemtype or an empty array if no unique object is found
 */
function getUniqueObjectIDByFQDN($fqdn, $entity) {

   $types = array('Computer', 'NetworkEquipment', 'Printer');

   foreach ($types as $itemtype) {
      $result = getUniqueObjectByFDQNAndType($fqdn, $itemtype, $entity);
      if (!empty ($result)) {
         return $result;
      }
   }
   return array ();
}

/**
 * Look for a specific type of device with a fully qualified domain name in an entity
 * @param fqdn fully qualified domain name
 * @param $itemtype the type of object to look for
 * @param entity the entity
 * @return an array with the ID and itemtype or an empty array if no unique object is found
 */

function getUniqueObjectByFDQNAndType($fqdn, $itemtype, $entity) {
   global $DB;

   if (class_exists($itemtype)) {

      $item = new $itemtype();

      $query = "SELECT `obj.id`
               FROM " . $item->getTable() . " AS obj, `glpi_domains` AS gdd
               WHERE `obj.entities_id` = '$entity'
                     AND `obj`.`domains_id` = `gdd`.`id`
                     AND LOWER( '$fqdn' ) = (CONCAT(LOWER(`obj`.`name`) , '.', LOWER(`gdd`.`name`)))";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $datas = $DB->fetch_array($result);
         return array ("id" => $datas["id"],
                     "itemtype" => $itemtype);
      }
   }
   return array ();

}
?>
