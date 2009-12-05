<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
   public $table = 'glpi_cartridgeitems';
   public $type = CARTRIDGEITEM_TYPE;
   public $entity_assign = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['cartridges'][12];
   }

   /**
    * Get The Name + Ref of the Object
    *
    * @param $with_comment add comments to name
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {
      $toadd="";
      if ($with_comment) {
         $toadd="&nbsp;".$this->getComments();
      }

      if (isset($this->fields["name"]) && !empty($this->fields["name"])) {
         $name = $this->fields["name"];

         if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
            $name .= " - ".$this->fields["ref"];
         }
         return $name.$toadd;
      }
      return "N/A";
   }

   function cleanDBonPurge($ID) {
      global $DB;
      // Delete cartridges
      $query = "DELETE
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '$ID'";
      $DB->query($query);
      // Delete all cartridge assoc
      $query2 = "DELETE
                 FROM `glpi_cartridges_printermodels`
                 WHERE `cartridgeitems_id` = '$ID'";
      $result2 = $DB->query($query2);
   }

   function post_getEmpty () {
      global $CFG_GLPI;
      $this->fields["alarm_threshold"]=$CFG_GLPI["default_alarm_threshold"];
   }

   function defineTabs($ID,$withtemplate){
      global $LANG;

      $ong[1]=$LANG['Menu'][21];
      if ($ID>0) {
         if (haveRight("contract","r") || haveRight("infocom","r")) {
         	$ong[4]=$LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if(empty($withtemplate)) {
            if (haveRight("link","r")) {
               $ong[7]=$LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10]=$LANG['title'][37];
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
   *
   **/
   function countCartridges() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `cartridgeitems_id` = '".$this->fields["id"]."'";
      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      } else {
         return false;
      }
   }

   /**Add a compatible printer type for a cartridge type
   *
   * Add the compatible printer $type type for the cartridge type $tID
   *
   *@param $cartridgeitems_id integer: cartridge type identifier
   *@param printermodels_id integer: printer type identifier
   *
   *@return boolean : true for success
   *
   **/
   function addCompatibleType($cartridgeitems_id,$printermodels_id) {
      global $DB;

      if ($cartridgeitems_id>0 && $printermodels_id>0) {
         $query="INSERT
                 INTO `glpi_cartridges_printermodels`
                      (`cartridgeitems_id`, `printermodels_id`)
                 VALUES ('$cartridgeitems_id','$printermodels_id');";
         if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            return true;
         }
      }
      return false;
   }

   /**
   * delete a compatible printer associated to a cartridge
   *
   * Delete a compatible printer associated to a cartridge with assoc identifier $ID
   *
   *@param $ID integer: glpi_cartridge_assoc identifier.
   *
   *@return boolean : true for success
   *
   **/
   function deleteCompatibleType($ID) {
      global $DB;

      $query="DELETE
              FROM `glpi_cartridges_printermodels`
              WHERE `id`= '$ID';";
      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }

   /**
   * Print the cartridge type form
   *
   * Print general cartridge type form
   *
   *@param $target filename : where to go when done.
   *@param $ID Integer : Id of the cartridge type
   *@param $withtemplate='' boolean : template or basic item
   *
   *@return Nothing (display)
   *
   **/
   function showForm ($target,$ID,$withtemplate='') {
   // Show CartridgeItem or blank form

      global $CFG_GLPI,$LANG;

      if (!haveRight("cartridge","r")) {
        return false;
      }

      if ($ID > 0){
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }


      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",
         $this->fields["name"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25].
      "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>.<textarea cols='45' rows='9' name='comment' >"
         .$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][2]." : </td>";
      echo "<td>";
      autocompletionTextField("ref",$this->table,"ref",
         $this->fields["ref"],40,$this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]." : </td>";
      echo "<td>";
      dropdownValue("glpi_cartridgeitemtypes","cartridgeitemtypes_id",
         $this->fields["cartridgeitemtypes_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]." : </td>";
      echo "<td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",
         $this->fields["manufacturers_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]." : </td>";
      echo "<td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],
         "interface",1,$this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][36]." : </td>";
      echo "<td>";
      dropdownValue("glpi_locations","locations_id",
         $this->fields["locations_id"],1,$this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][38]." : </td>";
      echo "<td>";
      dropdownInteger('alarm_threshold',$this->fields["alarm_threshold"],-1,100);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_cartridgeitems';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = CARTRIDGEITEM_TYPE;

      $tab[2]['table']     = 'glpi_cartridgeitems';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[34]['table']     = 'glpi_cartridgeitems';
      $tab[34]['field']     = 'ref';
      $tab[34]['linkfield'] = 'ref';
      $tab[34]['name']      = $LANG['consumables'][2];

      $tab[4]['table']     = 'glpi_cartridgeitemtypes';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'cartridgeitemtypes_id';
      $tab[4]['name']      = $LANG['common'][17];

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['linkfield'] = 'manufacturers_id';
      $tab[23]['name']      = $LANG['common'][5];

      $tab[3]['table']     = 'glpi_locations';
      $tab[3]['field']     = 'completename';
      $tab[3]['linkfield'] = 'locations_id';
      $tab[3]['name']      = $LANG['consumables'][36];

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[8]['table']     = 'glpi_cartridgeitems';
      $tab[8]['field']     = 'alarm_threshold';
      $tab[8]['linkfield'] = 'alarm_threshold';
      $tab[8]['name']      = $LANG['consumables'][38];
      $tab[8]['datatype']  = 'number';

      $tab[16]['table']     = 'glpi_cartridgeitems';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = 'glpi_cartridgeitems';
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }

   /**
    * Cron action on cartridges : alert if a stock is behind the threshold
    * @param $task for log, display informations if NULL?
    * @return 0 : nothing to do 1 : done with success
    *
    **/
   static function cron_cartridge($task=NULL) {
      global $DB,$CFG_GLPI,$LANG;

      if (!$CFG_GLPI["use_mailing"] || !$CFG_GLPI["cartridges_alert_repeat"]) {
         return false;
      }

      loadLanguage($CFG_GLPI["language"]);

      // Get cartridges type with alarm activated and last warning > X days depending on config
      $query="SELECT `glpi_cartridgeitems`.`id` AS cartID,
                     `glpi_cartridgeitems`.`entities_id` as entity,
                     `glpi_cartridgeitems`.`ref` as cartref, `glpi_cartridgeitems`.`name` AS cartname,
                     `glpi_cartridgeitems`.`alarm_threshold` AS threshold,
                     `glpi_alerts`.`id` AS alertID, `glpi_alerts`.`date`
              FROM `glpi_cartridgeitems`
              LEFT JOIN `glpi_alerts`
                        ON (`glpi_cartridgeitems`.`id` = `glpi_alerts`.`items_id`
                            AND `glpi_alerts`.`itemtype` = 'CartridgeItem')
              WHERE `glpi_cartridgeitems`.`is_deleted` = '0'
                    AND `glpi_cartridgeitems`.`alarm_threshold` >= '0'
                    AND (`glpi_alerts`.`date` IS NULL
                         OR (`glpi_alerts`.date+".$CFG_GLPI["cartridges_alert_repeat"].")
                             < CURRENT_TIMESTAMP())
             ORDER BY `cartname`;";

      $result=$DB->query($query);

      $message=array();
      $items=array();
      $alert=new Alert();

      if ($DB->numrows($result)>0) {
         while ($data=$DB->fetch_array($result)) {
            if (($unused=getUnusedCartridgesNumber($data["cartID"]))<=$data["threshold"]) {
               if (!isset($message[$data["entity"]])) {
                  $message[$data["entity"]]="";
               }
               if (!isset($items[$data["entity"]])) {
                  $items[$data["entity"]]=array();
               }

               // define message alert
               $message[$data["entity"]].=$LANG['mailing'][34]." ".$data["cartname"]." - ".
                                          $LANG['consumables'][2].": ".$data["cartref"]." - ".
                                          $LANG['software'][20].": ".$unused."<br>\n";
               $items[$data["entity"]][]=$data["cartID"];

               // if alert exists -> delete
               if (!empty($data["alertID"])) {
                  $alert->delete(array("id"=>$data["alertID"]));
               }
            }
         }

         if (count($message)>0) {
            foreach ($message as $entity => $msg) {
               $mail=new MailingAlert("alertcartridge",$msg,$entity);

               if ($mail->send()) {
                  if ($task) {
                     $task->log(getDropdownName("glpi_entities",$entity).":  $msg\n");
                     $task->addVolume(1);
                  } else {
                     addMessageAfterRedirect(getDropdownName("glpi_entities",$entity)."&nbsp;:  $msg");
                  }

                  $input["type"] = ALERT_THRESHOLD;
                  $input["itemtype"] = 'CartridgeItem';

                  //// add alerts
                  foreach ($items[$entity] as $ID) {
                     $input["items_id"]=$ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }
               } else {
                  if ($task) {
                     $task->log(getDropdownName("glpi_entities",$entity).
                            "&nbsp;: Send cartdridge alert failed");
                  } else {
                     addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).
                                             "&nbsp;: Send cartridge alert failed",false,ERROR);
                  }
               }
            }
            return 1;
         }
      }
      return 0;
   }
}
?>