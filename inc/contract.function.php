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
 * Cron action on contracts : alert depending of the config : on notice and expire
 *
 * @param $task for log, if NULL display
 *
 **/
function cron_contract($task=NULL) {
   global $DB,$CFG_GLPI,$LANG;

   if (!$CFG_GLPI["use_mailing"]) {
      return false;
   }

   loadLanguage($CFG_GLPI["language"]);

   $message=array();
   $items_notice=array();
   $items_end=array();

   // Check notice
   $query="SELECT `glpi_contracts`.*
           FROM `glpi_contracts`
           LEFT JOIN `glpi_alerts` ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                                       AND `glpi_alerts`.`itemtype`='".CONTRACT_TYPE."'
                                       AND `glpi_alerts`.`type`='".ALERT_NOTICE."')
           WHERE (`glpi_contracts`.`alert` & ".pow(2,ALERT_NOTICE).") >'0'
                 AND `glpi_contracts`.`is_deleted` = '0'
                 AND `glpi_contracts`.`begin_date` IS NOT NULL
                 AND `glpi_contracts`.`duration` <> '0'
                 AND `glpi_contracts`.`notice` <> '0'
                 AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                      `glpi_contracts`.`duration` MONTH),CURDATE()) > '0'
                 AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                      (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                      MONTH),CURDATE()) < '0'
                 AND `glpi_alerts`.`date` IS NULL";

   $result=$DB->query($query);
   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         if (!isset($message[$data["entities_id"]])) {
            $message[$data["entities_id"]]="";
         }
         if (!isset($items_notice[$data["entities_id"]])) {
            $items_notice[$data["entities_id"]]=array();
         }
         // define message alert
         $message[$data["entities_id"]].=$LANG['mailing'][37]." ".$data["name"].": ".getWarrantyExpir($data["begin_date"],$data["duration"],$data["notice"])."<br>\n";
         $items_notice[$data["entities_id"]][]=$data["id"];
      }
   }
   // Check end
   $query="SELECT `glpi_contracts`.*
           FROM `glpi_contracts`
           LEFT JOIN `glpi_alerts` ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                                       AND `glpi_alerts`.`itemtype`='".CONTRACT_TYPE."'
                                       AND `glpi_alerts`.`type`='".ALERT_END."')
           WHERE (`glpi_contracts`.`alert` & ".pow(2,ALERT_END).") > '0'
                 AND `glpi_contracts`.`is_deleted` = '0'
                 AND `glpi_contracts`.`begin_date` IS NOT NULL
                 AND `glpi_contracts`.`duration` <> '0'
                 AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                      (`glpi_contracts`.`duration`) MONTH),CURDATE()) < '0'
                 AND `glpi_alerts`.`date` IS NULL";

   $result=$DB->query($query);
   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetch_array($result)) {
         if (!isset($message[$data["entities_id"]])) {
            $message[$data["entities_id"]]="";
         }
         if (!isset($items_end[$data["entities_id"]])) {
            $items_end[$data["entities_id"]]=array();
         }
         // define message alert
         $message[$data["entities_id"]].=$LANG['mailing'][38]." ".$data["name"].": ".
                                         getWarrantyExpir($data["begin_date"],$data["duration"])."<br>\n";
         $items_end[$data["entities_id"]][]=$data["id"];
      }
   }

   if (count($message)>0) {
      foreach ($message as $entity => $msg) {
         $mail=new MailingAlert("alertcontract",$msg,$entity);
         if ($mail->send()) {
            if ($task) {
               $task->log(getDropdownName("glpi_entities",$entity).":  $msg\n");
               $task->addVolume(1);
            } else {
               addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  $msg");
            }

            // Mark alert as done
            $alert=new Alert();
            $input["itemtype"]=CONTRACT_TYPE;
            $input["type"]=ALERT_NOTICE;
            if (isset($items_notice[$entity])) {
               foreach ($items_notice[$entity] as $ID) {
                  $input["items_id"]=$ID;
                  $alert->add($input);
                  unset($alert->fields['id']);
               }
            }
            $input["type"]=ALERT_END;
            if (isset($items_end[$entity])) {
               foreach ($items_end[$entity] as $ID) {
                  $input["items_id"]=$ID;
                  $alert->add($input);
                  unset($alert->fields['id']);
               }
            }
         } else {
            if ($task) {
               $task->log(getDropdownName("glpi_entities",$entity).":  Send contract alert failed\n");
            } else {
               addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).
                                       ":  Send contract alert failed",false,ERROR);
            }
         }
      }
      return 1;
   }
   return 0;
}
?>
