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
 * Infocom class
 */
class Infocom extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   public $auto_message_on_action = false; // Link in message can't work'

   static function getTypeName() {
      global $LANG;

      return $LANG['financial'][3];
   }

   function canCreate() {
      return haveRight('infocom', 'w');
   }

   function canView() {
      return haveRight('infocom', 'r');
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
                FROM `".$this->getTable()."`
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
         if (class_exists($input['itemtype'])) {
            $item = new $input['itemtype']();
            if ($item->getFromDB($input['items_id'])) {
               $input['entities_id']=$item->getEntityID();
               $input['is_recursive']=intval($item->isRecursive());
               return $input;
            }
         }
      }
      return false;
   }

   function prepareInputForUpdate($input) {
      // No more use : need id to update
      /*
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
      */
      if (isset($input['warranty_duration'])) {
         $input['_warranty_duration']=$this->fields['warranty_duration'];
      }
      return $input;
   }

   function pre_updateInDB() {

      // Clean end alert if buy_date is after old one
      // Or if duration is greater than old one
      if ((isset($this->oldvalues['buy_date']) && ($this->oldvalues['buy_date'] < $this->fields['buy_date']))
          || (isset($this->oldvalues['warranty_duration'])
          && ($this->oldvalues['warranty_duration'] < $this->fields['warranty_duration']))) {

         $alert=new Alert();
         $alert->clear($this->getType(),$this->fields['id'],Alert::END);
      }
   }


   /**
    * Is the object assigned to an entity
    *
    * @return boolean
   **/
//    function isEntityAssign() {
//
//       if (isset($this->fields["itemtype"]) && class_exists($this->fields["itemtype"])) {
//          $item = new $this->fields["itemtype"]();
//          return $item->isEntityAssign();
//       }
//
//       return false;
//    }

   /**
    * Get the ID of entity assigned to the object
    *
    * @return ID of the entity
   **/
//    function getEntityID () {
//
//       if (class_exists($this->fields["itemtype"])) {
//          $item = new $this->fields["itemtype"]();
//          if ($item->getFromDB($this->fields["items_id"])) {
//             return $item->getEntityID();
//          }
//       }
//       return -1;
//    }

   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
//    function maybeRecursive() {
//
//       if (class_exists($this->fields["itemtype"])) {
//          $item = new $this->fields["itemtype"]();
//          return $item->maybeRecursive();
//       }
//
//       return false;
//    }

   /**
    * Is the object recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return integer (0/1)
   **/
//    function isRecursive() {
//
//       if (class_exists($this->fields["itemtype"])) {
//          $item = new $this->fields["itemtype"]();
//          return $item->isRecursive();
//       }
//
//       return false;
//    }

   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][6]);
   }

   /**
    * Cron action on infocom : alert on expired warranty
    *
    * @param $task to log, if NULL use display
    *
    * @return 0 : nothing to do 1 : done with success
    **/
   static function cronInfocom($task=NULL) {
      global $DB,$CFG_GLPI,$LANG;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message=array();
      $cron_status = 0;
      $items_infos = array();
      $items_messages = array();

      foreach (Entity::getEntitiesToNotify('use_infocoms_alert') as $entity => $value) {

         $query_end = "SELECT `glpi_infocoms`.*
                       FROM `glpi_infocoms`
                       LEFT JOIN `glpi_alerts` ON (`glpi_infocoms`.`id` = `glpi_alerts`.`items_id`
                                                     AND `glpi_alerts`.`itemtype` = 'Infocom'
                                                     AND `glpi_alerts`.`type`='".Alert::END."')
                       WHERE (`glpi_infocoms`.`alert` & ".pow(2,Alert::END).") >'0'
                        AND `glpi_infocoms`.`entities_id`='".$entity."'
                           AND `glpi_infocoms`.`warranty_duration`>'0'
                              AND `glpi_infocoms`.`buy_date` IS NOT NULL
                                 AND DATEDIFF(ADDDATE(`glpi_infocoms`.`buy_date`, INTERVAL
                                            (`glpi_infocoms`.`warranty_duration`) MONTH),CURDATE() )<'0'
                                    AND `glpi_alerts`.`date` IS NULL";

         foreach ($DB->request($query_end) as $data) {
            $item_infocom = new $data["itemtype"]();
            if ($item_infocom->getFromDB($data["items_id"])) {
               $entity = $data['entities_id'];
               $warranty = getWarrantyExpir($data["buy_date"],
                                                      $data["warranty_duration"]);
               $message = $LANG['mailing'][40]." ".
                              $item_infocom->getTypeName()." - ".$item_infocom->getName()." : ".
                              $warranty."<br>";
               $data['warrantyexpiration'] = $warranty;
               $data['item_name'] = $item_infocom->getName();
               $items_infos[$entity][$data['id']] = $data;

               if (!isset($items_messages[$entity])) {
                  $items_messages[$entity] = $LANG['mailing'][40]."<br />";
               }
               $items_messages[$entity] .= $message;
            }
         }
      }

      foreach ($items_infos as $entity => $items) {

         if (NotificationEvent::raiseEvent("alert",new Infocom(),
                                           array('entities_id'=>$entity,'items'=>$items))) {
            $message = $items_messages[$entity];
            $cron_status = 1;
            if ($task) {
               $task->log(Dropdown::getDropdownName("glpi_entities",
                                                      $entity).":  $message\n");
               $task->addVolume(1);
            } else {
               addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                 $entity).":  $message");
            }

            $alert=new Alert();
            $input["itemtype"] = 'Infocom';
            $input["type"]=Alert::END;
            foreach ($items as $id => $item) {
               $input["items_id"]=$id;
               $alert->add($input);
               unset($alert->fields['id']);
            }
         } else {
            if ($task) {
               $task->log(Dropdown::getDropdownName("glpi_entities",$entity).
                          ":  Send infocom alert failed\n");
            } else {
               addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",$entity).
                                       ":  Send infocom alert failed",false,ERROR);
            }
         }
      }
      return $cron_status;
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
      echo "<option value=\"".pow(2,Alert::END)."\" ".($value==pow(2,Alert::END)?" selected ":"")." >".
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

      if (!haveRight("infocom","r") || !class_exists($itemtype)) {
         return false;
      }
      $item = new $itemtype();

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

      if ($item->canView()) {
         echo "<span onClick=\"window.open('".$CFG_GLPI["root_doc"].
               "/front/infocom.form.php?itemtype=$itemtype&amp;items_id=$device_id&amp;update=$update',
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

   /**
    * Show Infocom form for an item (not a standard showForm)
    *
    * @param $item CommonDBTM object
    * @param $show_immo boolean: show immobilisation infos
    * @param $withtemplate integer: template or basic item
    **/
   static function showForItem(CommonDBTM $item, $show_immo=true, $withtemplate='') {
      global $CFG_GLPI,$LANG;

      // Show Infocom or blank form
      if (!haveRight("infocom","r")) {
         return false;
      }


      if (!$item) {
         echo "<div class='center'><br><br>".$LANG['financial'][85]."</div>";
      } else {
         $date_tax=$CFG_GLPI["date_tax"];
         $dev_ID = $item->getField('id');
         $ic = new Infocom;
         $option="";
         if ($withtemplate==2) {
            $option=" readonly ";
         }

         if (!strpos($_SERVER['PHP_SELF'],"infocoms-show")
            && in_array($item->getType(),array('Software','CartridgeItem','ConsumableItem'))) {
            echo "<div class='center'>".$LANG['financial'][84]."</div>";
         }
         if (!$ic->getFromDBforDevice($item->getType(),$dev_ID)) {
            //$input=array('entities_id'=>$entity);
            $input=array('itemtype' => $item->getType(),
                        'items_id' => $dev_ID,
                        'entities_id' => $item->getEntityID());

            if ($ic->can(-1,"w",$input) && $withtemplate!=2) {
               echo "<div class='center b'><br><br>".$item->getTypeName()." - ".$item->getName()."</div>";

               echo "<table class='tab_cadre'><tr><th>";
               echo "<a href='".$CFG_GLPI["root_doc"]."/front/infocom.form.php?itemtype=".$item->getType()."&amp;items_id=$dev_ID&amp;add=add'>".
                     $LANG['financial'][68]."</a></th></tr></table>";
            }
         } else { // getFromDBforDevice
            echo "<div class='center b'><br><br>".$item->getTypeName()." - ".$item->getName()."</div>";
            $canedit = ($ic->can($ic->fields['id'], "w") && $withtemplate!=2);
            if ($canedit) {
               echo "<form name='form_ic' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/infocom.form.php\">";
            }
            echo "<div class='center'>";
            echo "<table class='tab_cadre".(!strpos($_SERVER['PHP_SELF'],"infocoms-show")?"_fixe":"")."'>";

            echo "<tr><th colspan='4'>".$LANG['financial'][3]."</th></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][26]."&nbsp;:</td>";
            echo "<td>";
            if ($withtemplate==2) {
               echo Dropdown::getDropdownName("glpi_suppliers",$ic->fields["suppliers_id"]);
            } else {
               Dropdown::show('Supplier',
                              array('value'  => $ic->fields["suppliers_id"],
                                    'entity' => $item->getEntityID()));
            }
            echo "</td>";
            echo "<td>".$LANG['financial'][82]."&nbsp;:</td>";
            echo "<td >";
            autocompletionTextField($ic, "bill",array('option' => $option));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][18]."&nbsp;:</td>";
            echo "<td >";
            autocompletionTextField($ic, "order_number",array('option' => $option));
            echo "</td>";
            echo "<td>".$LANG['financial'][19]."&nbsp;:</td><td>";
            autocompletionTextField($ic, "delivery_number",array('option' => $option));
            echo "</td></tr>";

            // Can edit calendar ?
            $editcalendar=($withtemplate!=2);

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][14]."&nbsp;:</td><td>";
            showDateFormItem("buy_date",$ic->fields["buy_date"],true,$editcalendar);
            echo "</td>";
            echo "<td>".$LANG['financial'][76]."&nbsp;:</td><td>";
            showDateFormItem("use_date",$ic->fields["use_date"],true,$editcalendar);
            echo "</td></tr>";

            if ($show_immo) {
               echo "<tr class='tab_bg_1'><td>".$LANG['financial'][15]."&nbsp;:</td><td>";
               if ($withtemplate==2) {
                  // -1 = life
                  if ($ic->fields["warranty_duration"]==-1) {
                     echo $LANG['financial'][2];
                  } else {
                     echo $ic->fields["warranty_duration"];
                  }
               } else {
                  Dropdown::showInteger("warranty_duration",$ic->fields["warranty_duration"],0,120,1,
                                 array(-1=>$LANG['financial'][2]));
               }
               if ($ic->fields["warranty_duration"]>=0) {
                  echo " ".$LANG['financial'][57];
               }
               echo "&nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;".$LANG['financial'][88]."&nbsp;";
               echo getWarrantyExpir($ic->fields["buy_date"],$ic->fields["warranty_duration"]);
               echo "</td>";

               if (haveRight("budget","r")) {
                  echo "<td>".$LANG['financial'][87]."&nbsp;:</td><td >";
                  Dropdown::show('Budget',
                                 array('value'     => $ic->fields["budgets_id"],
                                       'entity'    => $item->getEntityID(),
                                       'comments'  => 0));
                  echo "</td></tr>";
               } else {
                  echo "<td colspan='2'></td></tr>";
               }

               echo "<tr class='tab_bg_1'><td>".$LANG['financial'][78]."&nbsp;:</td>";
               echo "<td><input type='text' $option name='warranty_value' value=\"".
                        formatNumber($ic->fields["warranty_value"],true)."\" size='14'></td>";
               echo "<td>".$LANG['financial'][16]."&nbsp;:</td>";
               echo "<td >";
               autocompletionTextField($ic, "warranty_info",array('option' => $option));
               echo "</td></tr>";
            }

            echo "<tr class='tab_bg_1'><td>".$LANG['financial'][21]."&nbsp;:</td>";
            echo "<td ".($show_immo?"":" colspan='3'").">
                  <input type='text' name='value' $option value=\"".
                  formatNumber($ic->fields["value"],true)."\" size='14'></td>";
            if ($show_immo) {
               echo "<td>".$LANG['financial'][81]."&nbsp;:</td><td>";
               echo formatNumber(Infocom::Amort($ic->fields["sink_type"],$ic->fields["value"],
                     $ic->fields["sink_time"],$ic->fields["sink_coeff"],$ic->fields["buy_date"],
                     $ic->fields["use_date"],$date_tax,"n"));
               echo "</td>";
            }
            echo "</tr>";

            if ($show_immo) {
               echo "<tr class='tab_bg_1'><td>".$LANG['financial'][20]."*&nbsp;:</td>";
               echo "<td >";
               $objectName = autoName($ic->fields["immo_number"], "immo_number", ($withtemplate==2),
                                    'Infocom',$item->getEntityID());
               autocompletionTextField($ic, "immo_number",array('value' => $objectName,
                                                               'option' => $option));
               echo "</td>";
               echo "<td>".$LANG['financial'][22]."&nbsp;:</td><td >";
               if ($withtemplate==2) {
                  echo Infocom::getAmortTypeName($ic->fields["sink_type"]);
               } else {
                  Infocom::dropdownAmortType("sink_type",$ic->fields["sink_type"]);
               }
               echo "</td></tr>";

               echo "<tr class='tab_bg_1'><td>".$LANG['financial'][23]."&nbsp;:</td><td>";
               if ($withtemplate==2) {
                  echo $ic->fields["sink_time"];
               } else {
                  Dropdown::showInteger("sink_time",$ic->fields["sink_time"],0,15);
               }
               echo " ".$LANG['financial'][9];
               echo "</td>";
               echo "<td>".$LANG['financial'][77]."&nbsp;:</td>";
               echo "<td >";
               autocompletionTextField($ic, "sink_coeff",array('size'=>14,'option' => $option));
               echo "</td></tr>";
            }
            //TCO
            if (!in_array($item->getType(), array('Software','CartridgeItem','ConsumableItem',
                                             'Consumable', 'Cartridge', 'SoftwareLicense'))) {

               echo "<tr class='tab_bg_1'><td>";
               echo $LANG['financial'][89]."&nbsp;:</td><td>";
               echo Infocom::showTco($item->getField('ticket_tco'),$ic->fields["value"]);
               echo "</td><td>".$LANG['financial'][90]."&nbsp;:</td><td>";
               echo Infocom::showTco($item->getField('ticket_tco'),$ic->fields["value"],$ic->fields["buy_date"]);
               echo "</td></tr>";
            }

            if ($CFG_GLPI['use_mailing']) {
               echo "<tr class='tab_bg_1'><td>".$LANG['setup'][247]."&nbsp;:</td>";
               echo "<td>";
               echo Infocom::dropdownAlert("alert",$ic->fields["alert"]);
               echo "</td>";
               echo "<td>&nbsp;</td>";
               echo "<td >&nbsp;</td></tr>";
            }
            // commment
            echo "<tr class='tab_bg_1'><td class='middle'>";
            echo $LANG['common'][25]."&nbsp;:</td>";
            echo "<td class='left' colspan='3'>
                  <textarea cols='116' $option rows='2' name='comment' >".$ic->fields["comment"];
            echo "</textarea>";
            echo "</td></tr>";

            if ($canedit) {
               echo "<tr>";
               echo "<td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='hidden' name='id' value=\"".$ic->fields['id']."\">";
               echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
               echo "</td>";
               echo "<td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
               echo "</td></tr>";
               echo "</table></div></form>";
            } else {
               echo "</table></div>";
            }
         }
      }
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']       = $LANG['common'][32];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'link';
      $tab[3]['linkfield'] = 'link';
      $tab[3]['name']      = $LANG['links'][1];

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'buy_date';
      $tab[4]['linkfield'] = 'buy_date';
      $tab[4]['name']      = $LANG["financial"][14];
      $tab[4]['datatype']  = 'date';

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'use_date';
      $tab[5]['linkfield'] = 'use_date';
      $tab[5]['name']      = $LANG["financial"][76];
      $tab[5]['datatype']  = 'date';

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'warranty_duration';
      $tab[6]['linkfield'] = 'warranty_duration';
      $tab[6]['name']      = $LANG["financial"][15];
      $tab[6]['datatype']  = 'integer';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'warranty_info';
      $tab[7]['linkfield'] = 'warranty_info';
      $tab[7]['name']      = $LANG["financial"][16];
      $tab[7]['datatype']  = 'text';

      $tab[8]['table']      = $this->getTable();
      $tab[8]['field']      = 'warranty_value';
      $tab[8]['linkfield']  = 'warranty_value';
      $tab[8]['name']       = $LANG["financial"][78];
      $tab[8]['datatype']   = 'decimal';

      $tab[9]['table']      = 'glpi_suppliers';
      $tab[9]['field']      = 'name';
      $tab[9]['linkfield']  = 'suppliers_id';
      $tab[9]['name']       = $LANG["financial"][26];
      $tab[9]['datatype']   = 'text';
      $tab[9]['table_type'] = 'single';

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'order_number';
      $tab[10]['linkfield'] = 'order_number';
      $tab[10]['name']      = $LANG["financial"][18];
      $tab[10]['datatype']  = 'text';

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'delivry_number';
      $tab[11]['linkfield'] = 'delivry_number';
      $tab[11]['name']      = $LANG["financial"][19];
      $tab[11]['datatype']  = 'text';

      $tab[12]['table']     = $this->getTable();
      $tab[12]['field']     = 'immo_number';
      $tab[12]['linkfield'] = 'order_number';
      $tab[12]['name']      = $LANG["financial"][20];
      $tab[12]['datatype']  = 'text';

      $tab[13]['table']     = $this->getTable();
      $tab[13]['field']     = 'value';
      $tab[13]['linkfield'] = 'value';
      $tab[13]['name']      = $LANG["financial"][21];
      $tab[13]['datatype']  = 'decimal';

      $tab[14]['table']     = $this->getTable();
      $tab[14]['field']     = 'sink_time';
      $tab[14]['linkfield'] = 'sink_time';
      $tab[14]['name']      = $LANG["financial"][23];
      $tab[14]['datatype']  = 'integer';

      $tab[15]['table']     = $this->getTable();
      $tab[15]['field']     = 'sink_type';
      $tab[15]['linkfield'] = 'sink_type';
      $tab[15]['name']      = $LANG["financial"][22];
      $tab[15]['datatype']  = 'integer';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[17]['table']     = $this->getTable();
      $tab[17]['field']     = 'sink_coeff';
      $tab[17]['linkfield'] = 'sink_coeff';
      $tab[17]['name']      = $LANG["financial"][77];
      $tab[17]['datatype']  = 'decimal';

      $tab[18]['table']     = $this->getTable();
      $tab[18]['field']     = 'bill';
      $tab[18]['linkfield'] = 'bill';
      $tab[18]['name']      = $LANG["financial"][82];
      $tab[18]['itemtype']  = 'text';

      $tab[19]['table']     = 'glpi_budgets';
      $tab[19]['field']     = 'name';
      $tab[19]['linkfield'] = 'budgets_id';
      $tab[19]['name']      = $LANG["financial"][87];
      $tab[19]['datatype']  = 'text';
      $tab[19]['datatype']  = 'itemlink';

      $tab[20]['table']     = $this->getTable();
      $tab[20]['field']     = 'alert';
      $tab[20]['linkfield'] = 'alert';
      $tab[20]['name']      = $LANG["setup"][247];
      $tab[20]['datatype']  = 'integer';

      $tab[20]['table']     = $this->getTable();
      $tab[20]['field']     = 'itemtype';
      $tab[20]['linkfield'] = '';
      $tab[20]['name']      = $LANG['common'][17];
      $tab[20]['datatype']  = 'itemtype';

      $tab[21]['table']     = $this->getTable();
      $tab[21]['field']     = 'items_id';
      $tab[21]['linkfield'] = '';
      $tab[21]['name']      = 'ID';
      $tab[21]['datatype']  = 'integer';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }
}

?>
