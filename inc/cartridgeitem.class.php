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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


/**
 * CartridgeItem Class
 * This class is used to manage the various types of cartridges.
 * \see Cartridge
 */
class CartridgeItem extends CommonDBTM {
   // From CommonDBTM
   protected $forward_entity_to = array('Infocom', 'Cartridge');

   static function getTypeName() {
      global $LANG;

      return $LANG['cartridges'][12];
   }


   function canCreate() {
      return haveRight('cartridge', 'w');
   }


   function canView() {
      return haveRight('cartridge', 'r');
   }


   /**
    * Get The Name + Ref of the Object
    *
    * @param $with_comment add comments to name
    *
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {

      $toadd = "";
      if ($with_comment) {
         $toadd = "&nbsp;".$this->getComments();
      }

      if (isset($this->fields["name"]) && !empty($this->fields["name"])) {
         $name = $this->fields["name"];

         if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
            $name .= " - ".$this->fields["ref"];
         }
         return $name.$toadd;
      }
      return NOT_AVAILABLE;
   }


   function cleanDBonPurge() {
      global $DB;

      // Delete cartridges
      $query = "DELETE
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields['id']."'";
      $DB->query($query);
      // Delete all cartridge assoc
      $query2 = "DELETE
                 FROM `glpi_cartridgeitems_printermodels`
                 WHERE `cartridgeitems_id` = '".$this->fields['id']."'";
      $result2 = $DB->query($query2);
   }


   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["alarm_threshold"] = $CFG_GLPI["default_alarm_threshold"];
   }


   function defineTabs($options=array()){
      global $LANG;

      $ong[1] = $LANG['Menu'][21];
      if ($this->fields['id'] > 0) {
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4] = $LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5] = $LANG['Menu'][27];
         }
         if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            if (haveRight("link","r")) {
               $ong[7] = $LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10] = $LANG['title'][37];
            }
         }
      }
      return $ong;
   }


   ///// SPECIFIC FUNCTIONS

   /**
   * Count cartridge of the cartridge type
   *
   *@return number of cartridges
   **/
   static function getCount() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields["id"]."'";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      }
      return false;
   }


   /**Add a compatible printer type for a cartridge type
   *
   * Add the compatible printer $type type for the cartridge type $tID
   *
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param printermodels_id integer: printer type identifier
   *
   *@return boolean : true for success
   **/
   function addCompatibleType($cartridgeitems_id, $printermodels_id) {
      global $DB;

      if ($cartridgeitems_id>0 && $printermodels_id>0) {
         $query = "INSERT INTO `glpi_cartridgeitems_printermodels`
                     (`cartridgeitems_id`, `printermodels_id`)
                     VALUES ('$cartridgeitems_id', '$printermodels_id');";

         if ($result = $DB->query($query) && $DB->affected_rows()>0) {
            return true;
         }
      }
      return false;
   }


   /**
   * Delete a compatible printer associated to a cartridge with assoc identifier $ID
   *
   *@param $ID integer: glpi_cartridge_assoc identifier.
   *
   *@return boolean : true for success
   *
   **/
   function deleteCompatibleType($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_cartridgeitems_printermodels`
                WHERE `id` = '$ID';";

      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }


   /**
   * Print the cartridge type form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *     - withtemplate : 1 for newtemplate, 2 for newobject from template
   *
   * @return Nothing (display)
   *
   **/
   function showForm ($ID, $options=array()) {
      global $LANG;

   // Show CartridgeItem or blank form
      if (!haveRight("cartridge", "r")) {
        return false;
      }

      if ($ID > 0){
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;: </td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>";
      echo "<textarea cols='45' rows='9' name='comment'>".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][2]."&nbsp;: </td>";
      echo "<td>";
      autocompletionTextField($this, "ref");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('CartridgeItemType', array('value' => $this->fields["cartridgeitemtypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;: </td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'interface',
                           'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][36]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][38]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::showInteger('alarm_threshold', $this->fields["alarm_threshold"], -1, 100);
      Alert::displayLastAlert('CartridgeItem', $ID);
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[34]['table']     = $this->getTable();
      $tab[34]['field']     = 'ref';
      $tab[34]['name']      = $LANG['consumables'][2];

      $tab[4]['table']     = 'glpi_cartridgeitemtypes';
      $tab[4]['field']     = 'name';
      $tab[4]['name']      = $LANG['common'][17];

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['name']      = $LANG['common'][5];

      $tab += Location::getSearchOptionsToAdd();

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'alarm_threshold';
      $tab[8]['name']      = $LANG['consumables'][38];
      $tab[8]['datatype']  = 'number';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = $LANG['title'][37];
      $tab[90]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[40]['table']     = 'glpi_printermodels';
      $tab[40]['field']     = 'name';
      $tab[40]['name']      = $LANG['setup'][96];
      $tab[40]['forcegroupby'] = true;
      return $tab;
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][2]);
   }


   /**
    * Cron action on cartridges : alert if a stock is behind the threshold
    *
    * @param $task for log, display informations if NULL?
    *
    * @return 0 : nothing to do 1 : done with success
    *
    **/
   static function cronCartridge($task=NULL) {
      global $DB, $CFG_GLPI, $LANG;

      $cron_status = 1;
      if ($CFG_GLPI["use_mailing"]) {
         $message = array();
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('cartridges_alert_repeat') as $entity => $repeat) {
            // if you change this query, please don't forget to also change in showDebug()
            $query_alert = "SELECT `glpi_cartridgeitems`.`id` AS cartID,
                                   `glpi_cartridgeitems`.`entities_id` AS entity,
                                   `glpi_cartridgeitems`.`ref` AS cartref,
                                   `glpi_cartridgeitems`.`name` AS cartname,
                                   `glpi_cartridgeitems`.`alarm_threshold` AS threshold,
                                   `glpi_alerts`.`id` AS alertID,
                                   `glpi_alerts`.`date`
                            FROM `glpi_cartridgeitems`
                            LEFT JOIN `glpi_alerts`
                                 ON (`glpi_cartridgeitems`.`id` = `glpi_alerts`.`items_id`
                                     AND `glpi_alerts`.`itemtype` = 'CartridgeItem')
                            WHERE `glpi_cartridgeitems`.`is_deleted` = '0'
                                  AND `glpi_cartridgeitems`.`alarm_threshold` >= '0'
                                  AND `glpi_cartridgeitems`.`entities_id` = '".$entity."'
                                  AND (`glpi_alerts`.`date` IS NULL
                                       OR (`glpi_alerts`.date+$repeat) < CURRENT_TIMESTAMP());";
            $message = "";
            $items   = array();

            foreach ($DB->request($query_alert) as $cartridge) {
               if (($unused=Cartridge::getUnusedNumber($cartridge["cartID"]))<=$cartridge["threshold"]) {
                  // define message alert
                  $message .= $LANG['mailing'][35]." ".$cartridge["cartname"]." - ".
                              $LANG['consumables'][2]."&nbsp;: ".$cartridge["cartref"]." - ".
                              $LANG['software'][20]."&nbsp;: ".$unused."<br>";
                  $items[$cartridge["cartID"]] = $cartridge;

                  // if alert exists -> delete
                  if (!empty($cartridge["alertID"])) {
                     $alert->delete(array("id" => $cartridge["alertID"]));
                  }
               }
            }

            if (!empty($items)) {
               $options['entities_id'] = $entity;
               $options['cartridges']  = $items;
               if (NotificationEvent::raiseEvent('alert', new Cartridge(), $options)) {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities", $entity)
                               ."&nbsp;:  $message\n");
                     $task->addVolume(1);
                  } else {
                     addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity)
                                            ."&nbsp;:  $message");
                  }

                  $input["type"]     = Alert::THRESHOLD;
                  $input["itemtype"] = 'CartridgeItem';

                  // add alerts
                  foreach ($items as $ID=>$consumable) {
                     $input["items_id"] = $ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities", $entity)
                               ."&nbsp;: Send cartidge alert failed\n");
                  } else {
                     addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity)
                                            ."&nbsp;: Send cartidge alert failed", false, ERROR);
                  }
               }
            }
          }
      }
   }


   /**
    * Print a select with compatible cartridge
    *
    *@param $printer object Printer
    *
    *@return nothing (display)
    **/
   static function dropdownForPrinter(Printer $printer) {
      global $DB, $LANG;

      $query = "SELECT COUNT(*) AS cpt,
                       `glpi_locations`.`completename` AS location,
                       `glpi_cartridgeitems`.`ref` AS ref,
                       `glpi_cartridgeitems`.`name` AS name,
                       `glpi_cartridgeitems`.`id` AS tID
                FROM `glpi_cartridgeitems`
                INNER JOIN `glpi_cartridgeitems_printermodels`
                     ON (`glpi_cartridgeitems`.`id`
                         = `glpi_cartridgeitems_printermodels`.`cartridgeitems_id`)
                INNER JOIN `glpi_cartridges`
                     ON (`glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`
                         AND `glpi_cartridges`.`date_use` IS NULL)
                LEFT JOIN `glpi_locations`
                     ON (`glpi_locations`.`id` = `glpi_cartridgeitems`.`locations_id`)
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = '".$printer->fields["printermodels_id"]."'
                      AND `glpi_cartridgeitems`.`entities_id` ='".$printer->fields["entities_id"]."'
                GROUP BY tID
                ORDER BY `name`, `ref`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<select name='tID' size=1>";
            while ($data= $DB->fetch_assoc($result)) {
               echo "<option value='".$data["tID"]."'>".$data["name"]." - ".$data["ref"]."
                     (".$data["cpt"]." ".$LANG['cartridges'][13].") - ".$data["location"]."</option>";
            }
            echo "</select>";
            return true;
         }
      }
      return false;
   }


   /**
    * Show the printer types that are compatible with a cartridge type
    *
    *@return nothing (display)
    **/
   function showCompatiblePrinters() {
      global $DB, $CFG_GLPI, $LANG;

      $instID = $this->getField('id');
      if (!$this->can($instID, 'r')) {
         return false;
      }

      $query = "SELECT `glpi_cartridgeitems_printermodels`.`id`,
                       `glpi_printermodels`.`name` AS `type`,
                       `glpi_printermodels`.`id` AS `pmid`
                FROM `glpi_cartridgeitems_printermodels`,
                     `glpi_printermodels`
                WHERE `glpi_cartridgeitems_printermodels`.`printermodels_id`
                           = `glpi_printermodels`.`id`
                      AND `glpi_cartridgeitems_printermodels`.`cartridgeitems_id` = '$instID'
                ORDER BY `glpi_printermodels`.`name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<div class='spaced'>";
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/cartridgeitem.form.php\">";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='3'>".$LANG['cartridges'][32]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['common'][22]."</th><th>&nbsp;</th></tr>";

      $used = array();
      while ($i < $number) {
         $ID   = $DB->result($result, $i, "id");
         $type = $DB->result($result, $i, "type");
         $pmid = $DB->result($result, $i, "pmid");
         echo "<tr class='tab_bg_1'><td class='center'>$ID</td>";
         echo "<td class='center'>$type</td>";
         echo "<td class='tab_bg_2 center'>";
         echo "<a href='".$CFG_GLPI['root_doc'].
                "/front/cartridgeitem.form.php?deletetype=deletetype&amp;id=$ID&amp;tID=$instID'>";
         echo "<strong>".$LANG['buttons'][6]."</strong></a></td></tr>";
         $used[] = $pmid;
         $i++;
      }
      if (haveRight("cartridge", "w")) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
         echo "<input type='hidden' name='tID' value='$instID'>";
         Dropdown::show('PrinterModel', array('used' => $used));
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='addtype' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</td></tr>";
      }
      echo "</table></div></form>";
   }


  function getEvents() {
      global $LANG;

      return array ('alert' => $LANG['crontask'][2]);
   }


   /**
    * Display debug information for current object
    *
   **/
   function showDebug() {

      // see query_alert in cronCartridge()
      $item = array('cartID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'cartref'   => $this->fields['ref'],
                    'cartname'  => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']);

      $options = array();
      $options['entities_id'] = $this->getEntityID();
      $options['cartridges']  = array($item);
      NotificationEvent::debugEvent(new Cartridge(), $options);
   }
}
?>
