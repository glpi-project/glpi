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

//!  ConsumableItem Class
/**
 * This class is used to manage the various types of consumables.
 * @see Consumable
 * @author Julien Dombre
 */
class ConsumableItem extends CommonDBTM {
   // From CommonDBTM
   protected $forward_entity_to=array('Infocom','Consumable');

   static function getTypeName() {
      global $LANG;

      return $LANG['consumables'][12];
   }


   function canCreate() {
      return haveRight('consumable', 'w');
   }


   function canView() {
      return haveRight('consumable', 'r');
   }


   /**
    * Get The Name + Ref of the Object
    *
    * @param $with_comment add comments to name
    *
    * @return String: name of the object in the current language
   **/
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

      // Delete cartridconsumablesges
      $query = "DELETE
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '".$this->fields['id']."')";
      $DB->query($query);
   }


   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["alarm_threshold"] = $CFG_GLPI["default_alarm_threshold"];
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      if ($this->fields['id'] > 0) {
         $ong[1] = $LANG['Menu'][32];

         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4] = $LANG['Menu'][26];
         }

         if (haveRight("document","r")) {
            $ong[5] = $LANG['Menu'][27];
         }

         if (haveRight("link","r")) {
            $ong[7] = $LANG['title'][34];
         }

         if (haveRight("notes","r")) {
            $ong[10] = $LANG['title'][37];
         }
         if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
            $ong[2] = $LANG['setup'][137];
         }

      } else { // New item
         $ong[1] = $LANG['title'][26];
      }

      return $ong;
   }


   /**
    * Print the consumable type form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
    *
    **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      // Show ConsumableItem or blank form
      if (!haveRight("consumable","r")) {
         return false;
      }

      if ($ID > 0){
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>";
      echo "<textarea cols='45' rows='9' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][2]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this, "ref");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::show('ConsumableItemType',
                     array('value' => $this->fields["consumableitemtypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'interface',
                           'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][36]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][38]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showInteger('alarm_threshold', $this->fields["alarm_threshold"], 0, 100, 1,
                            array('-1' => $LANG['setup'][307]));

      Alert::displayLastAlert('ConsumableItem', $ID);
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

      $tab[34]['table'] = $this->getTable();
      $tab[34]['field'] = 'ref';
      $tab[34]['name']  = $LANG['consumables'][2];

      $tab[4]['table'] = 'glpi_consumableitemtypes';
      $tab[4]['field'] = 'name';
      $tab[4]['name']  = $LANG['common'][17];

      $tab[23]['table'] = 'glpi_manufacturers';
      $tab[23]['field'] = 'name';
      $tab[23]['name']  = $LANG['common'][5];

      $tab+=Location::getSearchOptionsToAdd();

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

      return $tab;
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][3]);
   }


   /**
    * Cron action on consumables : alert if a stock is behind the threshold
    *
    * @param $task to log, if NULL display
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronConsumable($task=NULL) {
      global $DB,$CFG_GLPI,$LANG;

      $cron_status = 1;

      if ($CFG_GLPI["use_mailing"]) {
         $message = array();
         $items   = array();
         $alert   = new Alert();

         foreach (Entity::getEntitiesToNotify('consumables_alert_repeat') as $entity => $repeat) {

            $query_alert = "SELECT `glpi_consumableitems`.`id` AS consID,
                                   `glpi_consumableitems`.`entities_id` AS entity,
                                   `glpi_consumableitems`.`ref` AS consref,
                                   `glpi_consumableitems`.`name` AS consname,
                                   `glpi_consumableitems`.`alarm_threshold` AS threshold,
                                   `glpi_alerts`.`id` AS alertID,
                                   `glpi_alerts`.`date`
                            FROM `glpi_consumableitems`
                            LEFT JOIN `glpi_alerts`
                                 ON (`glpi_consumableitems`.`id` = `glpi_alerts`.`items_id`
                                     AND `glpi_alerts`.`itemtype`='ConsumableItem')
                            WHERE `glpi_consumableitems`.`is_deleted` = '0'
                                  AND `glpi_consumableitems`.`alarm_threshold` >= '0'
                                  AND `glpi_consumableitems`.`entities_id` = '".$entity."'
                                  AND (`glpi_alerts`.`date` IS NULL
                                       OR (`glpi_alerts`.date+$repeat) < CURRENT_TIMESTAMP());";
            $message = "";
            $items   = array();

            foreach ($DB->request($query_alert) as $consumable) {
               if (($unused=Consumable::getUnusedNumber($consumable["consID"]))
                              <=$consumable["threshold"]) {
                  // define message alert
                  $message .= $LANG['mailing'][35]." ".$consumable["consname"]." - ".
                              $LANG['consumables'][2]."&nbsp;: ".$consumable["consref"]." - ".
                              $LANG['software'][20]."&nbsp;: ".$unused."<br>";
                  $items[$consumable["consID"]] = $consumable;

                  // if alert exists -> delete
                  if (!empty($consumable["alertID"])) {
                     $alert->delete(array("id" => $consumable["alertID"]));
                  }
               }
            }

            if (!empty($items)) {
               $options['entities_id'] = $entity;
               $options['consumables'] = $items;

               if (NotificationEvent::raiseEvent('alert', new Consumable(), $options)) {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities",
                                                          $entity)." :  $message\n");
                     $task->addVolume(1);
                  } else {
                     addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                       $entity)." :  $message");
                  }

                  $input["type"]     = Alert::THRESHOLD;
                  $input["itemtype"] = 'ConsumableItem';

                  // add alerts
                  foreach ($items as $ID=>$consumable) {
                     $input["items_id"] = $ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }

               } else {
                  if ($task) {
                     $task->log(Dropdown::getDropdownName("glpi_entities", $entity).
                                " : Send consumable alert failed\n");
                  } else {
                     addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity).
                                             " : Send consumable alert failed",false,ERROR);
                  }
               }
            }
          }
      }
      return $cron_status;
   }


   function getEvents() {
      global $LANG;

      return array ('alert' => $LANG['crontask'][3]);
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      // see query_alert in cronConsumable()
      $item = array('consID'    => $this->fields['id'],
                    'entity'    => $this->fields['entities_id'],
                    'consref'   => $this->fields['ref'],
                    'consname'  => $this->fields['name'],
                    'threshold' => $this->fields['alarm_threshold']);

      $options = array();
      $options['entities_id'] = $this->getEntityID();
      $options['consumables']  = array($item);
      NotificationEvent::debugEvent(new Consumable(), $options);
   }
}

?>
