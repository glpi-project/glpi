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
 * Link a contract to a device
 *
 * Link the contract $conID to the device $ID witch intem type is $itemtype.
 *
 *@param $conID integer : contract identifier.
 *@param $type integer : device type identifier.
 *@param $ID integer : device identifier.
 *
 *@return Nothing ()
 *
 **/
function addDeviceContract($conID,$itemtype,$ID) {
   global $DB;

   // TODO : to remove this function (stil used when cloning a template)
   if ($ID>0&&$conID>0){
      $contractitem=new ContractItem();
      $contractitem->add(array('contracts_id' => $conID,
                               'itemtype' => $itemtype,
                               'items_id' => $ID));
   }
}

/**
 * Get the entreprise identifier from a contract
 *
 * Get the entreprise identifier for the contract $ID
 *
 *@param $ID integer : Contract entreprise identifier
 *
 *@return integer enterprise identifier
 *
 **/
function getContractSuppliers($ID) {
   global $DB;

   $query = "SELECT `glpi_suppliers`.*
             FROM `glpi_contracts_suppliers`, `glpi_suppliers`
             WHERE `glpi_contracts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`
                   AND `glpi_contracts_suppliers`.`contracts_id` = '$ID'";
   $result = $DB->query($query);
   $out="";
   while ($data=$DB->fetch_array($result)) {
      $out.= getDropdownName("glpi_suppliers",$data['id'])."<br>";
   }
   return $out;
}

/**
 * Print an HTML array with contracts associated to a device
 *
 * Print an HTML array with contracts associated to the device identified by $ID from item type $itemtype
 *
 *@param $itemtype string : HTML select name
 *@param $ID integer device ID
 *@param $withtemplate='' not used (to be deleted)
 *
 *@return Nothing (display)
 *
 **/
function showContractAssociated($itemtype,$ID,$withtemplate='') {
   global $DB,$CFG_GLPI, $LANG;

   if (!haveRight("contract","r") || !haveTypeRight($itemtype,"r")) {
      return false;
   }

   $ci=new CommonItem();
   $ci->getFromDB($itemtype,$ID);
   $canedit=$ci->obj->can($ID,"w");

   $query = "SELECT `glpi_contracts_items`.*
             FROM `glpi_contracts_items`, `glpi_contracts`
             LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id`=`glpi_entities`.`id`)
             WHERE `glpi_contracts`.`id`=`glpi_contracts_items`.`contracts_id`
                   AND `glpi_contracts_items`.`items_id` = '$ID'
                   AND `glpi_contracts_items`.`itemtype` = '$itemtype'".
                       getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true)."
             ORDER BY `glpi_contracts`.`name`";

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = 0;

   if ($withtemplate!=2) {
      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contract.form.php\">";
   }
   echo "<div class='center'><br><table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='8'>".$LANG['financial'][66]."&nbsp;:</th></tr>";
   echo "<tr><th>".$LANG['common'][16]."</th>";
   echo "<th>".$LANG['entity'][0]."</th>";
   echo "<th>".$LANG['financial'][4]."</th>";
   echo "<th>".$LANG['financial'][6]."</th>";
   echo "<th>".$LANG['financial'][26]."</th>";
   echo "<th>".$LANG['search'][8]."</th>";
   echo "<th>".$LANG['financial'][8]."</th>";
   if ($withtemplate!=2) {
      echo "<th>&nbsp;</th>";
   }
   echo "</tr>";

   if ($number>0) {
      initNavigateListItems(CONTRACT_TYPE,$ci->getType()." = ".$ci->getName());
   }
   $contracts=array();
   while ($i < $number) {
      $cID=$DB->result($result, $i, "contracts_id");
      addToNavigateListItems(CONTRACT_TYPE,$cID);
      $contracts[]=$cID;
      $assocID=$DB->result($result, $i, "id");
      $con=new Contract;
      $con->getFromDB($cID);
      echo "<tr class='tab_bg_1".($con->fields["is_deleted"]?"_2":"")."'>";
      echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?id=$cID'>";
      echo "<strong>".$con->fields["name"];
      if ($_SESSION["glpiis_ids_visible"] || empty($con->fields["name"])) {
         echo " (".$con->fields["id"].")";
      }
      echo "</strong></a></td>";
      echo "<td class='center'>".getDropdownName("glpi_entities",$con->fields["entities_id"])."</td>";
      echo "<td class='center'>".$con->fields["num"]."</td>";
      echo "<td class='center'>".
             getDropdownName("glpi_contractstypes",$con->fields["contractstypes_id"])."</td>";
      echo "<td class='center'>".getContractSuppliers($cID)."</td>";
      echo "<td class='center'>".convDate($con->fields["begin_date"])."</td>";
      echo "<td class='center'>".$con->fields["duration"]." ".$LANG['financial'][57];
      if ($con->fields["begin_date"]!='' && !empty($con->fields["begin_date"])) {
         echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
      }
      echo "</td>";

      if ($withtemplate!=2) {
         echo "<td class='tab_bg_2 center'>";
         if ($canedit) {
            echo "<a href='".$CFG_GLPI["root_doc"].
                  "/front/contract.form.php?deleteitem=deleteitem&amp;id=$assocID&amp;contracts_id=$cID'>";
            echo "<img src='".$CFG_GLPI["root_doc"]."/pics/delete2.png' alt='".$LANG['buttons'][6].
                  "'></a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td>";
      }
      echo "</tr>";
      $i++;
   }
   $q="SELECT *
       FROM `glpi_contracts`
       WHERE `is_deleted`='0' ".
             getEntitiesRestrictRequest("AND","glpi_contracts","entities_id",
                                        $ci->obj->getEntityID(),true);;
   $result = $DB->query($q);
   $nb = $DB->numrows($result);

   if ($canedit) {
      if ($withtemplate!=2 && $nb>count($contracts)) {
         echo "<tr class='tab_bg_1'><td class='right' colspan='3'>";
         echo "<div class='software-instal'><input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         dropdownContracts("contracts_id",$ci->obj->getEntityID(),$contracts);
         echo "</div></td><td class='center'>";
         echo "<input type='submit' name='additem' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td>";
         echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
      }
   }
   echo "</table></div>";

   if ($withtemplate!=2) {
      echo "</form>";
   }
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
