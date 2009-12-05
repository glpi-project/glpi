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
 * InfoCom class
 */
class InfoCom extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_infocoms';
   public $type = INFOCOM_TYPE;
   public $dohistory=true;
   public $auto_message_on_action=true;

   static function getTypeName() {
      global $LANG;

      return $LANG['financial'][3];
   }

   function post_getEmpty() {
      global $CFG_GLPI;

   $this->fields["alert"]=$CFG_GLPI["default_infocom_alert"];
   }

   /**
    * Retrieve an item from the database for a device
    *
    *@param $ID ID of the device to retrieve infocom
    *@param $itemtype type of the device to retrieve infocom
    *@return true if succeed else false
   **/
   function getFromDBforDevice ($itemtype,$ID) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->table."`
                WHERE `items_id` = '$ID'
                      AND `itemtype`='$itemtype'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $data = $DB->fetch_assoc($result);
            foreach ($data as $key => $val) {
               $this->fields[$key] = $val;
            }
            return true;
         } else {
            $this->getEmpty();
            $this->fields["items_id"]=$ID;
            $this->fields["itemtype"]=$itemtype;
            return false;
         }
      } else {
         return false;
      }
   }

   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      if (!$this->getFromDBforDevice($input['itemtype'],$input['items_id'])) {
         $input['alert']=$CFG_GLPI["default_infocom_alert"];
         return $input;
      }
      return false;
   }

   function prepareInputForUpdate($input) {

      if (isset($input["id"])) {
         $this->getFromDB($input["id"]);
      } else {
         if (!$this->getFromDBforDevice($input["itemtype"],$input["items_id"])) {
            $input2["items_id"]=$input["items_id"];
            $input2["itemtype"]=$input["itemtype"];
            $this->add($input2);
            $this->getFromDBforDevice($input["itemtype"],$input["items_id"]);
         }
         $input["id"]=$this->fields["id"];
      }

      if (isset($input['warranty_duration'])) {
         $input['_warranty_duration']=$this->fields['warranty_duration'];
      }
      return $input;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Clean end alert if buy_date is after old one
      // Or if duration is greater than old one
      if ((isset($oldvalues['buy_date']) && ($oldvalues['buy_date'] < $this->fields['buy_date']))
          || (isset($oldvalues['warranty_duration'])
          && ($oldvalues['warranty_duration'] < $this->fields['warranty_duration']))) {

         $alert=new Alert();
         $alert->clear($this->type,$this->fields['id'],ALERT_END);
      }
      return array($input,$updates);
   }

   /**
    * Is the object assigned to an entity
    *
    * @return boolean
   **/
   function isEntityAssign() {

      $ci=new CommonItem();
      $ci->setType($this->fields["itemtype"], true);

      return $ci->obj->isEntityAssign();
   }

   /**
    * Get the ID of entity assigned to the object
    *
    * @return ID of the entity
   **/
   function getEntityID () {

      $ci=new CommonItem();
      $ci->getFromDB($this->fields["itemtype"], $this->fields["items_id"]);

      return $ci->obj->getEntityID();
   }

   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive() {

      $ci=new CommonItem();
      $ci->setType($this->fields["itemtype"], true);

      return $ci->obj->maybeRecursive();
   }

   /**
    * Is the object recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return integer (0/1)
   **/
   function isRecursive() {

      $ci=new CommonItem();
      $ci->getFromDB($this->fields["itemtype"], $this->fields["items_id"]);

      return $ci->obj->isRecursive();
   }

   /**
    * Cron action on infocom : alert on expired warranty
    *
    * @param $task to log, if NULL use display
    *
    * @return 0 : nothing to do 1 : done with success
    **/
   static function cron_infocom($task=NULL) {
      global $DB,$CFG_GLPI,$LANG;

      if (!$CFG_GLPI["use_mailing"]) {
         return false;
      }

      loadLanguage($CFG_GLPI["language"]);

      $message=array();
      $items=array();

      // Check notice
      $query="SELECT `glpi_infocoms`.*
              FROM `glpi_infocoms`
              LEFT JOIN `glpi_alerts` ON (`glpi_infocoms`.`id` = `glpi_alerts`.`items_id`
                                          AND `glpi_alerts`.`itemtype`='InfoCom'
                                          AND `glpi_alerts`.`type`='".ALERT_END."')
              WHERE (`glpi_infocoms`.`alert` & ".pow(2,ALERT_END).") >'0'
                    AND `glpi_infocoms`.`warranty_duration`>'0'
                    AND `glpi_infocoms`.`buy_date` IS NOT NULL
                    AND DATEDIFF(ADDDATE(`glpi_infocoms`.`buy_date`, INTERVAL
                                         (`glpi_infocoms`.`warranty_duration`) MONTH),CURDATE() )<'0'
                    AND `glpi_alerts`.`date` IS NULL";

      $result=$DB->query($query);
      if ($DB->numrows($result)>0) {

         // TODO : remove this when autoload ready
         $needed=array("computer",
                       "device",
                       "printer",
                       "networking",
                       "peripheral",
                       "monitor",
                       "software",
                       "infocom",
                       "phone",
                       "state",
                       "tracking",
                       "enterprise");
         foreach ($needed as $item) {
            if (file_exists(GLPI_ROOT . "/inc/$item.class.php")) {
               include_once (GLPI_ROOT . "/inc/$item.class.php");
            }
            if (file_exists(GLPI_ROOT . "/inc/$item.function.php")) {
               include_once (GLPI_ROOT . "/inc/$item.function.php");
            }
         }

         while ($data=$DB->fetch_array($result)) {
            if (!class_exists($data["itemtype"])) {
               continue;
            }
            $item = new $data["itemtype"]();
            if ($item->getFromDB($data["items_id"])) {
               $entity = $item->getEntityID();
               if (!isset($message[$entity])) {
                  $message[$entity]="";
               }
               if (!isset($items[$entity])) {
                  $items[$entity]=array();
               }

               // define message alert / Not for template items
               if (!$item->getField('is_template')) {
                  $message[$entity].=$LANG['mailing'][40]." ".
                                     $item->getTypeName()." - ".$item->getName()." : ".
                                     getWarrantyExpir($data["buy_date"],$data["warranty_duration"])."<br>";
                  $items[$entity][]=$data["id"];
               }
            }
         }
         if (count($message)>0) {
            // Mark alert as done
            $alert=new Alert();

            foreach ($message as $entity => $msg) {
               $mail=new MailingAlert("alertinfocom",$msg,$entity);
               if ($mail->send()) {
                  if ($task) {
                     $task->log(getDropdownName("glpi_entities",$entity).": $msg\n");
                     $task->addVolume(1);
                  } else {
                     addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).": $msg");
                  }

                  $input["type"] = ALERT_END;
                  $input["itemtype"] = 'InfoCom';

                  //// add alerts
                  foreach ($items[$entity] as $ID) {
                     $input["items_id"]=$ID;
                     $alert->add($input);
                     unset($alert->fields['id']);
                  }
               } else {
                  if ($task) {
                     $task->log(getDropdownName("glpi_entities",$entity).": Send infocom alert failed\n");
                  } else {
                     addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":
                                             Send infocom alert failed",false,ERROR);
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
