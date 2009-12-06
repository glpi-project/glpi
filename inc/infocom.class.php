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

   /**
    * Dropdown for infocoms alert config
    *
    * @param $name select name
    * @param $value default value
    */
   static function dropdownAlert($name,$value=0) {
      global $LANG;

      echo "<select name=\"$name\">";
      echo "<option value='0'".($value==0?" selected ":"")." >-----</option>";
      echo "<option value=\"".pow(2,ALERT_END)."\" ".($value==pow(2,ALERT_END)?" selected ":"")." >".
             $LANG['financial'][80]." </option>";
      echo "</select>";
   }

   /**
    * Dropdown of amortissement type for infocoms
    *
    * @param $name select name
    * @param $value default value
    */
   static function dropdownAmortType($name,$value=0) {
      global $LANG;

      echo "<select name='$name'>";
      echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
      echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['financial'][47]."</option>";
      echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['financial'][48]."</option>";
      echo "</select>";
   }

   /**
    * Get amortissement type name for infocoms
    *
    * @param $value status ID
    */
   static function getAmortTypeName($value) {
      global $LANG;

      switch ($value) {
         case 2 :
            return $LANG['financial'][47];
            break;

         case 1 :
            return $LANG['financial'][48];
            break;

         case 0 :
            return "";
            break;
      }
   }

   /**
    * Calculate TCO and TCO by month for an item
    *
    *
    *@param $ticket_tco Tco part of tickets
    *@param $value
    *@param $date_achat
    *
    *@return float
    *
    **/
   static function showTco($ticket_tco, $value, $date_achat="") {
      global $CFG_GLPI;

      // Affiche le TCO ou le TCO mensuel pour un matériel
      $totalcost=$ticket_tco;

      if ($date_achat) { // on veut donc le TCO mensuel
         // just to avoid IDE warning
         $date_Y = $date_m = $date_d = 0;

         sscanf($date_achat, "%4s-%2s-%2s",$date_Y, $date_m, $date_d);

         $timestamp2 = mktime(0,0,0, $date_m, $date_d, $date_Y);
         $timestamp = mktime(0,0,0, date("m"), date("d"), date("Y"));

         $diff = floor(($timestamp - $timestamp2) / (MONTH_TIMESTAMP)); // Mois d'utilisation

         if ($diff) {
            return formatNumber((($totalcost+$value)/$diff)); // TCO mensuel
         }
         return "";
      }
      return formatNumber(($totalcost+$value)); // TCO
   }// fin showTCO

   /**
    * Show infocom link to display popup
    *
    *@param $itemtype integer: item type
    *@param $device_id integer:  item ID
    *@param $update integer:
    *
    *@return float
    **/
   static function showDisplayLink($itemtype,$device_id,$update=0) {
      global $DB,$CFG_GLPI,$LANG;

      if (!haveRight("infocom","r")) {
         return false;
      }

      $query="SELECT COUNT(*)
              FROM `glpi_infocoms`
              WHERE `items_id`='$device_id'
                    AND `itemtype`='$itemtype'";

      $add="add";
      $text=$LANG['buttons'][8];
      $result=$DB->query($query);
      if ($DB->result($result,0,0)>0) {
         $add="";
         $text=$LANG['buttons'][23];
      } else if (!haveRight("infocom","w")) {
         return false;
      }

      if (haveTypeRight($itemtype,"r")) {
         echo "<span onClick=\"window.open('".$CFG_GLPI["root_doc"].
               "/front/infocom.form.php?itemtype=$itemtype&amp;device_id=$device_id&amp;update=$update',
               'infocoms','location=infocoms,width=1000,height=400,scrollbars=no')\" style='cursor:pointer'>
               <img src=\"".$CFG_GLPI["root_doc"]."/pics/dollar$add.png\" alt=\"$text\" title=\"$text\">
               </span>";
      }
   }

   /**
    * Calculate amortissement for an item
    *
    *@param $type_amort type d'amortisssment "lineaire=2" ou "degressif=1"
    *@param $va valeur d'acquisition
    *@param $duree duree d'amortissement
    *@param $coef coefficient d'amortissement
    *@param $date_achat Date d'achat
    *@param $date_use Date d'utilisation
    *@param $date_tax date du debut de l'annee fiscale
    *@param $view  "n" pour l'annee en cours ou "all" pour le tableau complet
    *
    *@return float or array
    *
    **/
   static function Amort($type_amort,$va,$duree,$coef,$date_achat,$date_use,$date_tax,$view="n") {
      // By Jean-Mathieu Doleans qui s'est un peu pris le chou :p
      global $CFG_GLPI;

      // Attention date mise en service/dateachat ->amort lineaire  et $prorata en jour !!
      // amort degressif au prorata du nombre de mois.
      // Son point de depart est le 1er jour du mois d'acquisition et non date de mise en service

      if ($type_amort=="2") {
         if (!empty($date_use)) {
            $date_achat=$date_use;
         }
      }

      $prorata=0;
      $ecartfinmoiscourant=0;
      $ecartmoisexercice=0;
      $date_Y = $date_m = $date_d = $date_H = $date_i = $date_s=0;
      sscanf($date_achat, "%4s-%2s-%2s %2s:%2s:%2s",
             $date_Y, $date_m, $date_d,
             $date_H, $date_i, $date_s); // un traitement sur la date mysql pour recuperer l'annee

      // un traitement sur la date mysql pour les infos necessaires
      $date_Y2 = $date_m2 = $date_d2 = $date_H2 = $date_i2 = $date_s2=0;
      sscanf($date_tax, "%4s-%2s-%2s %2s:%2s:%2s",
             $date_Y2, $date_m2, $date_d2,
             $date_H2, $date_i2, $date_s2);
      $date_Y2=date("Y");

      switch ($type_amort) {
         case "2" :
            ########################### Calcul amortissement lineaire ###########################
            if ($va>0 && $duree>0 && !empty($date_achat)) {
               ## calcul du prorata temporis en jour ##
               $ecartfinmoiscourant=(30-$date_d); // calcul ecart entre jour date acquis
                                                  // ou mise en service et fin du mois courant
               // en lineaire on calcule en jour
               if ($date_d2<30) {
                  $ecartmoisexercice=(30-$date_d2);
               }
               if ($date_m>$date_m2) {
                  $date_m2=$date_m2+12;
               } // si l'annee fiscale debute au dela de l'annee courante
               $ecartmois=(($date_m2-$date_m)*30); // calcul ecart entre mois d'acquisition
                                                   // et debut annee fiscale
               $prorata=$ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
               ## calcul tableau d'amortissement ##
               $txlineaire = (100/$duree); // calcul du taux lineaire
               $annuite = ($va*$txlineaire)/100; // calcul de l'annuitee
               $mrt=$va; //
               // si prorata temporis la derniere annnuite cours sur la duree n+1
               if ($prorata>0) {
                  $duree=$duree+1;
               }
               for($i=1;$i<=$duree;$i++) {
                  $tab['annee'][$i]=$date_Y+$i-1;
                  $tab['annuite'][$i]=$annuite;
                  $tab['vcnetdeb'][$i]=$mrt; // Pour chaque annee on calcul la valeur comptable nette
                                             // de debut d'exercice
                  $tab['vcnetfin'][$i]=abs(($mrt - $annuite)); // Pour chaque annee on calcule la valeur
                                                               // comptable nette de fin d'exercice
                  // calcul de la premiere annuite si prorata temporis
                  if ($prorata>0) {
                     $tab['annuite'][1]=$annuite*($prorata/360);
                     $tab['vcnetfin'][1]=abs($va - $tab['annuite'][1]);
                  }
                  $mrt=$tab['vcnetfin'][$i];
               }
               // calcul de la derniere annuite si prorata temporis
               if ($prorata>0) {
                  $tab['annuite'][$duree]=$tab['vcnetdeb'][$duree];
                  $tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['annuite'][$duree];
               }
            } else {
               return "-";
               break;
            }
            break;

         case "1" :
            ########################### Calcul amortissement degressif ###########################
            if($va>0 && $duree>0 && $coef>1 && !empty($date_achat)) {
               ## calcul du prorata temporis en mois ##
               // si l'annee fiscale debute au dela de l'annee courante
               if ($date_m>$date_m2) {
                  $date_m2=$date_m2+12;
               }
               $ecartmois=($date_m2-$date_m)+1; // calcul ecart entre mois d'acquisition
                                                // et debut annee fiscale
               $prorata=$ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
               ## calcul tableau d'amortissement ##
               $txlineaire = (100/$duree); // calcul du taux lineaire virtuel
               $txdegressif=$txlineaire*$coef; // calcul du taux degressif
               $dureelineaire= (int) (100/$txdegressif); // calcul de la duree de l'amortissement
                                                         // en mode lineaire
               $dureedegressif=$duree-$dureelineaire; // calcul de la duree de l'amortissement
                                                      // en mode degressif
               $mrt=$va;
               // amortissement degressif pour les premieres annees
               for($i=1;$i<=$dureedegressif;$i++) {
                  $tab['annee'][$i]=$date_Y+$i-1;
                  $tab['vcnetdeb'][$i]=$mrt; // Pour chaque annee on calcule la valeur comptable nette
                                             // de debut d'exercice
                  $tab['annuite'][$i]=$tab['vcnetdeb'][$i]*$txdegressif/100;
                  $tab['vcnetfin'][$i]=$mrt - $tab['annuite'][$i]; //Pour chaque annee on calcule la valeur
                                                                   //comptable nette de fin d'exercice
                  // calcul de la premiere annuite si prorata temporis
                  if ($prorata>0) {
                     $tab['annuite'][1]=($va*$txdegressif/100)*($prorata/12);
                     $tab['vcnetfin'][1]=$va - $tab['annuite'][1];
                  }
                  $mrt=$tab['vcnetfin'][$i];
               }
               // amortissement en lineaire pour les derneres annees
               if ($dureelineaire!=0) {
                  $txlineaire = (100/$dureelineaire); // calcul du taux lineaire
               } else {
                  $txlineaire = 100;
               }
               $annuite = ($tab['vcnetfin'][$dureedegressif]*$txlineaire)/100; // calcul de l'annuite
               $mrt=$tab['vcnetfin'][$dureedegressif];
               for($i=$dureedegressif+1;$i<=$dureedegressif+$dureelineaire;$i++) {
                  $tab['annee'][$i]=$date_Y+$i-1;
                  $tab['annuite'][$i]=$annuite;
                  $tab['vcnetdeb'][$i]=$mrt; // Pour chaque annee on calcule la valeur comptable nette
                                             // de debut d'exercice
                  $tab['vcnetfin'][$i]=abs(($mrt - $annuite)); // Pour chaque annee on calcule la valeur
                                                               // comptable nette de fin d'exercice
                  $mrt=$tab['vcnetfin'][$i];
               }
               // calcul de la derniere annuite si prorata temporis
               if ($prorata>0) {
                  $tab['annuite'][$duree]=$tab['vcnetdeb'][$duree];
                  if (isset($tab['vcnetfin'][$duree-1])) {
                     $tab['vcnetfin'][$duree]=$tab['vcnetfin'][$duree-1]- $tab['annuite'][$duree];
                  } else {
                     $tab['vcnetfin'][$duree]=0;
                  }
               }
            } else {
               return "-";
               break;
            }
            break;

         default :
            return "-";
            break;
      }

      // le return
      if ($view=="all") {
         // on retourne le tableau complet
         return $tab;
      } else {
         // on retourne juste la valeur residuelle
         // si on ne trouve pas l'annee en cours dans le tableau d'amortissement dans le tableau,
         // le materiel est amorti
         if (!array_search(date("Y"),$tab["annee"])) {
            $vnc=0;
         } else if (mktime(0 , 0 , 0, $date_m2, $date_d2, date("Y"))
                    - mktime(0 , 0 , 0 , date("m") , date("d") , date("Y")) < 0 ) {
            // on a depasse la fin d'exercice de l'annee en cours
            //on prend la valeur residuelle de l'annee en cours
            $vnc= $tab["vcnetfin"][array_search(date("Y"),$tab["annee"])];
         } else {
            // on se situe avant la fin d'exercice
            // on prend la valeur residuelle de l'annee n-1
            $vnc=$tab["vcnetdeb"][array_search(date("Y"),$tab["annee"])];
         }
         return $vnc;
      }
   }
}

?>
