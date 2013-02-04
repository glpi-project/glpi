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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES Ocslink
class Ocslink extends CommonDBTM {

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['ocsng'][53];
      }
      return $LANG['ocsng'][58];
   }


   function canCreate() {
      return Session::haveRight('ocsng', 'w');
   }


   function canView() {
      return Session::haveRight('ocsng', 'r');
   }


   /**
   * Show OcsLink of an item
   *
   * @param $item CommonDBTM object
   * @param $withtemplate integer : withtemplate param
   * @return nothing
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $LANG;

      if (in_array($item->getType(),array('Computer'))) {
         $items_id = $item->getField('id');

         $query = "SELECT `glpi_ocslinks`.`tag` AS tag
                   FROM `glpi_ocslinks`
                   WHERE `glpi_ocslinks`.`computers_id` = '$items_id' ".
                         getEntitiesRestrictRequest("AND","glpi_ocslinks");

         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $data = $DB->fetch_assoc($result);
            $data = Toolbox::clean_cross_side_scripting_deep(Toolbox::addslashes_deep($data));

            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . $LANG['ocsng'][0] . "</th>";
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>".$LANG['ocsconfig'][39]."&nbsp;: ".$data['tag']."</td></tr>";
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      if (!$withtemplate && $CFG_GLPI["use_ocs_mode"]) {
         switch ($item->getType()) {
            case 'Computer' :
               if (Session::haveRight('sync_ocsng', 'w') || Session::haveRight('computer', 'w')) {
                  return $LANG['ocsconfig'][0];
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Computer') {
         self::showForItem($item);
         self::editLock($item);
      }
      return true;
   }


   static function editLock(Computer $comp) {
      global $DB, $LANG;

      $ID     = $comp->getID();
      $target = Toolbox::getItemTypeFormURL(__CLASS__);

      if (!Session::haveRight("computer","w")) {
         return false;
      }
      $query = "SELECT *
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $data = $DB->fetch_assoc($result);
         if (Session::haveRight("sync_ocsng","w")) {
            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='id' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".$LANG['ocsng'][0]."</th></tr>";

            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
            echo "<input class=submit type='submit' name='force_ocs_resynch' value=\"" .
                   $LANG['ldap'][11] . "\">";
            Html::closeForm();
            echo "</td><tr>";
         }

         echo "</table></div>";

         $header = false;
         echo "<div width='50%'>";
         echo "<form method='post' action=\"$target\">";
         echo "<input type='hidden' name='id' value='$ID'>\n";
         echo "<table class='tab_cadre_fixe'>";

         // Print lock fields for OCSNG
         $lockable_fields = OcsServer::getLockableFields();
         $locked          = importArrayFromDB($data["computer_update"]);

         if (!in_array(OcsServer::IMPORT_TAG_078,$locked)) {
            $locked = OcsServer::migrateComputerUpdates($ID, $locked);
         }

         if (count($locked)>0) {
            foreach ($locked as $key => $val) {
               if (!isset($lockable_fields[$val])) {
                  unset($locked[$key]);
               }
            }
         }

         if (count($locked)) {
            $header = true;
            echo "<tr><th colspan='2'>" . $LANG['ocsng'][16] . "&nbsp;:</th></tr>\n";

            foreach ($locked as $key => $val) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='right' width='50%'>" . $lockable_fields[$val] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockfield[" . $key . "]'></td></tr>\n";
            }
         }

         //Search locked monitors
         $locked_monitor = importArrayFromDB($data["import_monitor"]);
         $first          = true;

         foreach ($locked_monitor as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedMonitor = "SELECT `items_id`
                                            FROM `glpi_computers_items`
                                            WHERE `id` = '$key'";
               $resultSearchMonitor = $DB->query($querySearchLockedMonitor);

               if ($DB->numrows($resultSearchMonitor) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>" . $LANG['ocsng'][30] . "&nbsp;: </th></tr>\n";
                     $first = false;
                  }

                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockmonitor[" . $key . "]'></td></tr>\n";
               }
            }
         }

         //Search locked printers
         $locked_printer = importArrayFromDB($data["import_printer"]);
         $first          = true;

         foreach ($locked_printer as $key => $val) {
            $querySearchLockedPrinter = "SELECT `items_id`
                                         FROM `glpi_computers_items`
                                         WHERE `id` = '$key'";
            $resultSearchPrinter = $DB->query($querySearchLockedPrinter);

            if ($DB->numrows($resultSearchPrinter) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][34] . "</th></tr>\n";
                  $first = false;
               }

               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockprinter[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked peripherals
         $locked_periph = importArrayFromDB($data["import_peripheral"]);
         $first         = true;

         foreach ($locked_periph as $key => $val) {
            $querySearchLockedPeriph = "SELECT `items_id`
                                        FROM `glpi_computers_items`
                                        WHERE `id` = '$key'";
            $resultSearchPeriph = $DB->query($querySearchLockedPeriph);

            if ($DB->numrows($resultSearchPeriph) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][32] . "</th></tr>\n";
                  $first = false;
               }

               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockperiph[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked IP
         $locked_ip = importArrayFromDB($data["import_ip"]);

         if (!in_array(OcsServer::IMPORT_TAG_072,$locked_ip)) {
            $locked_ip = OcsServer::migrateImportIP($ID,$locked_ip);
         }
         $first = true;

         foreach ($locked_ip as $key => $val) {
            if ($key>0) {
               $tmp = explode(OcsServer::FIELD_SEPARATOR,$val);
               $querySearchLockedIP = "SELECT *
                                       FROM `glpi_networkports`
                                       WHERE `items_id` = '$ID'
                                             AND `itemtype` = 'Computer'
                                             AND `ip` = '".$tmp[0]."'
                                             AND `mac` = '".$tmp[1]."'";
               $resultSearchIP = $DB->query($querySearchLockedIP);

               if ($DB->numrows($resultSearchIP) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>" . $LANG['ocsng'][50] . "</th></tr>\n";
                     $first = false;
                  }
                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . str_replace(OcsServer::FIELD_SEPARATOR, ' / ', $val) . "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockip[" . $key . "]'></td></tr>\n";
               }
            }
         }

         // Search locked softwares
         $locked_software = importArrayFromDB($data["import_software"]);
         $first           = true;

         foreach ($locked_software as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedSoft = "SELECT `id`
                                         FROM `glpi_computers_softwareversions`
                                         WHERE `id` = '$key'";
               $resultSearchSoft = $DB->query($querySearchLockedSoft);

               if ($DB->numrows($resultSearchSoft) == 0) {
                  $header = true;
                  if ($first) {
                     echo "<tr><th colspan='2'>" . $LANG['ocsng'][52] . "</th></tr>\n";
                     $first = false;
                  }
                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='right'width='50%'>" . str_replace('$$$$$',' v. ',$val) . "</td>";
                  echo "<td class='left'width='50%'>";
                  echo "<input type='checkbox' name='locksoft[" . $key . "]'></td></tr>";
               }
            }
         }

         // Search locked computerdisks
         $locked_disk = importArrayFromDB($data["import_disk"]);
         $first       = true;

         foreach ($locked_disk as $key => $val) {
            $querySearchLockedDisk = "SELECT `id`
                                       FROM `glpi_computerdisks`
                                       WHERE `id` = '$key'";
            $resultSearchDisk = $DB->query($querySearchLockedDisk);

            if ($DB->numrows($resultSearchDisk) == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][55] . "</th></tr>\n";
                  $first = false;
               }
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockdisk[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked computervirtualmachines
         $locked_vm = importArrayFromDB($data["import_vm"]);
         $first     = true;

         foreach ($locked_vm as $key => $val) {
            $nb = countElementsInTable('glpi_computervirtualmachines', "`id`='$key'");
            if ($nb == 0) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" . $LANG['computers'][57] . "</th></tr>\n";
                  $first = false;
               }
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockvm[" . $key . "]'></td></tr>\n";
            }
         }

         // Search for locked devices
         $locked_dev = importArrayFromDB($data["import_device"]);
         if (!in_array(OcsServer::IMPORT_TAG_078, $locked_dev)) {
            $locked_dev = OcsServer::migrateImportDevice($ID, $locked_dev);
         }
         $types = Computer_Device::getDeviceTypes();
         $first = true;
         foreach ($locked_dev as $key => $val) {
            if (!$key) { // OcsServer::IMPORT_TAG_078
               continue;
            }
            list($type, $nomdev) = explode(OcsServer::FIELD_SEPARATOR, $val);
            list($type, $iddev)  = explode(OcsServer::FIELD_SEPARATOR, $key);
            if (!isset($types[$type])) { // should never happen
               continue;
            }
            $compdev = new Computer_Device($types[$type]);
            if (!$compdev->getFromDB($iddev)) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][56] . "</th></tr>\n";
                  $first = false;
               }
               $device = new $types[$type]();
               echo "<tr class='tab_bg_1'><td align='right' width='50%'>";
               echo $device->getTypeName()."&nbsp;: $nomdev</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockdevice[" . $key . "]'></td></tr>\n";
            }
         }

         if ($header) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input class='submit' type='submit' name='unlock' value='" .
                  $LANG['buttons'][38] . "'></td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo $LANG['ocsng'][15]."</td></tr>";
         }

         echo "</table>";
         Html::closeForm();
         echo "</div>\n";
      }
   }


}

?>