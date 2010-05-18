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
 *  Contract class
 */
class Contract extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['financial'][1];
   }

   function canCreate() {
      return haveRight('contract', 'w');
   }

   function canView() {
      return haveRight('contract', 'r');
   }

   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["alert"]=$CFG_GLPI["default_contract_alert"];
   }

   function cleanDBonPurge() {

      $cs = new Contract_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $ci = new Contract_Item();
      $ci->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }

   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      if ($this->fields['id'] > 0) {
         $ong[1]=$LANG['Menu'][23];
         $ong[2]=$LANG['common'][1];
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if (haveRight("link","r")) {
            $ong[7]=$LANG['title'][34];
         }
         if (haveRight("notes","r")) {
            $ong[10]=$LANG['title'][37];
         }
         $ong[12]=$LANG['title'][38];
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   function pre_updateInDB() {

      // Clean end alert if begin_date is after old one
      // Or if duration is greater than old one
      if ((isset($this->oldvalues['begin_date']) && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($this->oldvalues['duration']) && ($this->oldvalues['duration'] < $this->fields['duration']))) {
         $alert=new Alert();
         $alert->clear($this->getType(),$this->fields['id'],Alert::END);
      }

      // Clean notice alert if begin_date is after old one
      // Or if duration is greater than old one
      // Or if notice is lesser than old one
      if ((isset($this->oldvalues['begin_date']) && ($this->oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($this->oldvalues['duration']) && ($this->oldvalues['duration'] < $this->fields['duration']))
          || (isset($this->oldvalues['notice']) && ($this->oldvalues['notice'] > $this->fields['notice']))) {
         $alert=new Alert();
         $alert->clear($this->getType(),$this->fields['id'],Alert::NOTICE);
      }
   }

   /**
   * Print the contract form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   *@return boolean item found
   *
   **/
   function showForm ($ID,$options=array()) {
      global $CFG_GLPI,$LANG;
      // Show Contract or blank form

      if (!haveRight("contract","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $can_edit=$this->can($ID,'w');

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td><td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".$LANG['financial'][6]."&nbsp;:</td><td >";
      Dropdown::show('ContractType', array('value' => $this->fields["contracttypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][4]."&nbsp;:</td>";
      echo "<td><input type='text' name='num' value=\"".$this->fields["num"]."\" size='25'></td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][5]."&nbsp;:</td><td>";
      echo "<input type='text' name='cost' value=\"".
             formatNumber($this->fields["cost"],true)."\" size='14'>";
      echo "</td>";
      echo "<td>".$LANG['search'][8]."&nbsp;:</td>";
      echo "<td>";
      showDateFormItem("begin_date",$this->fields["begin_date"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][8]."&nbsp;:</td><td>";
      Dropdown::showInteger("duration",$this->fields["duration"],0,120);
      echo " ".$LANG['financial'][57];
      if (!empty($this->fields["begin_date"])) {
         echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"]);
      }
      echo "</td>";
      echo "<td>".$LANG['financial'][13]."&nbsp;:</td><td>";
      autocompletionTextField($this, "accounting_number");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][69]."&nbsp;:</td><td>";
      Dropdown::showInteger("periodicity",$this->fields["periodicity"],12,60,12,array(0 => "-----",
                                                                                      1 => "1",
                                                                                      2 => "2",
                                                                                      3 => "3",
                                                                                      6 => "6"));
      echo " ".$LANG['financial'][57];
      echo "</td>";
      echo "<td>".$LANG['financial'][10]."&nbsp;:</td><td>";
      Dropdown::showInteger("notice",$this->fields["notice"],0,120);
      echo " ".$LANG['financial'][57];
      if (!empty($this->fields["begin_date"]) && $this->fields["notice"]>0) {
         echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"],
                                      $this->fields["notice"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['financial'][107]."&nbsp;:</td><td>";
      Contract::dropdownContractRenewal("renewal",$this->fields["renewal"]);
      echo "</td>";
      echo "<td>".$LANG['financial'][11]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showInteger("billing",$this->fields["billing"],12,60,12,array(0=>"-----",
                                                                        1=>"1",
                                                                        2=>"2",
                                                                        3=>"3",
                                                                        6=>"6"));
      echo " ".$LANG['financial'][57];
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['financial'][83]."&nbsp;:</td><td>";
      Dropdown::showInteger("max_links_allowed",$this->fields["max_links_allowed"],0,200);
      echo "</td>";
      echo "<td>".$LANG['common'][41]."</td>";
      echo "<td>";
      Contract::dropdownAlert("alert",$this->fields["alert"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td valign='top'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center' colspan='3'>";
      echo "<textarea cols='50' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".$LANG['financial'][59]."&nbsp;:</td>";
      echo "<td colspan='3'>&nbsp;</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][60]."&nbsp;:</td>";
      echo "<td colspan='3'>". $LANG['buttons'][33]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("week_begin_hour",$this->fields["week_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("week_end_hour",$this->fields["week_end_hour"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][61]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      Dropdown::showYesNo("use_saturday",$this->fields["use_saturday"]);
      echo "&nbsp;".$LANG['buttons'][33]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("saturday_begin_hour",$this->fields["saturday_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("saturday_end_hour",$this->fields["saturday_end_hour"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][62]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      Dropdown::showYesNo("use_monday",$this->fields["use_monday"]);
      echo "&nbsp;".$LANG['buttons'][33]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("monday_begin_hour",$this->fields["monday_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      Dropdown::showHours("monday_end_hour",$this->fields["monday_end_hour"]);
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
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'num';
      $tab[3]['linkfield'] = 'num';
      $tab[3]['name']      = $LANG['financial'][4];

      $tab[4]['table']     = 'glpi_contracttypes';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'contracttypes_id';
      $tab[4]['name']      = $LANG['common'][17];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'begin_date';
      $tab[5]['linkfield'] = 'begin_date';
      $tab[5]['name']      = $LANG['search'][8];
      $tab[5]['datatype']  = 'date';

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'duration';
      $tab[6]['linkfield'] = 'duration';
      $tab[6]['name']      = $LANG['financial'][8];

      $tab[20]['table']         = $this->getTable();
      $tab[20]['field']         = 'end_date';
      $tab[20]['linkfield']     = '';
      $tab[20]['name']          = $LANG['search'][9];
      $tab[20]['datatype']      = 'date_delay';
      $tab[20]['datafields'][1] = 'begin_date';
      $tab[20]['datafields'][2] = 'duration';
      $tab[20]['searchunit']    = 'MONTH';
      $tab[20]['delayunit']     = 'MONTH';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'notice';
      $tab[7]['linkfield'] = 'notice';
      $tab[7]['name']      = $LANG['financial'][10];

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'cost';
      $tab[11]['linkfield'] = 'cost';
      $tab[11]['name']      = $LANG['financial'][5];
      $tab[11]['datatype']  = 'decimal';

      $tab[21]['table']     = $this->getTable();
      $tab[21]['field']     = 'periodicity';
      $tab[21]['linkfield'] = '';
      $tab[21]['name']      = $LANG['financial'][69];

      $tab[22]['table']     = $this->getTable();
      $tab[22]['field']     = 'billing';
      $tab[22]['linkfield'] = '';
      $tab[22]['name']      = $LANG['financial'][11];

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'accounting_number';
      $tab[10]['linkfield'] = 'accounting_number';
      $tab[10]['name']      = $LANG['financial'][13];

      $tab[23]['table']     = $this->getTable();
      $tab[23]['field']     = 'renewal';
      $tab[23]['linkfield'] = '';
      $tab[23]['name']      = $LANG['financial'][107];

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'expire';
      $tab[12]['linkfield']     = '';
      $tab[12]['name']          = $LANG['financial'][98];
      $tab[12]['datatype']     = 'date_delay';
      $tab[12]['datafields'][1] = 'begin_date';
      $tab[12]['datafields'][2] = 'duration';
      $tab[12]['searchunit']    = 'DAY';
      $tab[12]['delayunit']     = 'MONTH';

      $tab[13]['table']     = $this->getTable();
      $tab[13]['field']     = 'expire_notice';
      $tab[13]['linkfield'] = '';
      $tab[13]['name']      = $LANG['financial'][99];
      $tab[13]['datatype']     = 'date_delay';
      $tab[13]['datafields'][1] = 'begin_date';
      $tab[13]['datafields'][2] = 'duration';
      $tab[13]['datafields'][3] = 'notice';
      $tab[13]['searchunit']    = 'DAY';
      $tab[13]['delayunit']     = 'MONTH';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = $this->getTable();
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[59]['table']     = $this->getTable();
      $tab[59]['field']     = 'alert';
      $tab[59]['linkfield'] = 'alert';
      $tab[59]['name']      = $LANG['common'][41];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
    * Show central contract resume
    * HTML array
    *
    * @return Nothing (display)
    *
    **/
   static function showCentral() {
      global $DB,$CFG_GLPI, $LANG;

      if (!haveRight("contract","r")) {
         return false;
      }

      // No recursive contract, not in local management
      // contrats echus depuis moins de 30j
      $query = "SELECT count(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>-30
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'0'";
      $result = $DB->query($query);
      $contract0=$DB->result($result,0,0);

      // contrats  echeance j-7
      $query = "SELECT count(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<='7'";
      $result = $DB->query($query);
      $contract7= $DB->result($result,0,0);

      // contrats echeance j -30
      $query = "SELECT count(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'30'";
      $result = $DB->query($query);
      $contract30= $DB->result($result,0,0);

      // contrats avec préavis echeance j-7
      $query = "SELECT count(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' ".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<='7'";
      $result = $DB->query($query);
      $contractpre7= $DB->result($result,0,0);

      // contrats avec préavis echeance j -30
      $query = "SELECT count(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0'".
                      getEntitiesRestrictRequest("AND","glpi_contracts")."
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<'30'";
      $result = $DB->query($query);
      $contractpre30= $DB->result($result,0,0);

      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset\">".
             $LANG['financial'][1]."</a></th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset&amp;".
                 "glpisearchcount=2&amp;sort=12&amp;order=DESC&amp;start=0&amp;field[0]=12&amp;".
                 "field[1]=12&amp;link[1]=AND&amp;contains[0]=%3C0&amp;contains[1]=%3E-30".
                  "&amp;searchtype[0]=contains&amp;searchtype[1]=contains\">".
                 $LANG['financial'][93]."</a> </td>";
      echo "<td>$contract0</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset&amp;".
                 "glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;".
                 "contains%5B1%5D=%3C7&amp;field%5B1%5D=12&amp;sort=12&amp;is_deleted=0&amp;start=0".
                  "&amp;searchtype[0]=contains&amp;searchtype[1]=contains\">".
                 $LANG['financial'][94]."</a></td>";
      echo "<td>".$contract7."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset&amp;".
                 "glpisearchcount=2&amp;contains%5B0%5D=%3E6&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;".
                 "contains%5B1%5D=%3C30&amp;field%5B1%5D=12&amp;sort=12&amp;is_deleted=0".
                  "&amp;searchtype[0]=contains&amp;searchtype[1]=contains&amp;start=0\">".
                 $LANG['financial'][95]."</a></td>";
      echo "<td>".$contract30."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset&amp;".
                 "glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=13&amp;link%5B1%5D=AND&amp;".
                 "contains%5B1%5D=%3C7&amp;field%5B1%5D=13&amp;sort=12&amp;is_deleted=0".
                  "&amp;searchtype[0]=contains&amp;searchtype[1]=contains&amp;start=0\">".
                 $LANG['financial'][96]."</a></td>";
      echo "<td>".$contractpre7."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset&amp;".
                 "glpisearchcount=2&amp;sort=13&amp;order=DESC&amp;start=0&amp;field[0]=13&amp;".
                 "field[1]=13&amp;link[1]=AND&amp;contains[0]=%3E6&amp;contains[1]=%3C30".
                  "&amp;searchtype[0]=contains&amp;searchtype[1]=contains\">".
                 $LANG['financial'][97]."</a></td>";
      echo "<td>".$contractpre30."</td></tr>";
      echo "</table>";
   }

   /**
    * Print the HTML array Of suppliers for this contrach
    *
    *@return Nothing (HTML display)
    *
    **/
   function showSuppliers() {
      global $DB,$CFG_GLPI, $LANG,$CFG_GLPI;

      $instID = $this->fields['id'];

      if (!$this->can($instID,'r') || !haveRight("contact_enterprise","r")) {
         return false;
      }
      $canedit=$this->can($instID,'w');

      $query = "SELECT `glpi_contracts_suppliers`.`id`, `glpi_suppliers`.`id` AS entID,
                       `glpi_suppliers`.`name` AS name, `glpi_suppliers`.`website` AS website,
                       `glpi_suppliers`.`phonenumber` AS phone,
                       `glpi_suppliers`.`suppliertypes_id` AS type, `glpi_entities`.`id` AS entity
                FROM `glpi_contracts_suppliers`, `glpi_suppliers`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_suppliers`.`entities_id`)
                WHERE `glpi_contracts_suppliers`.`contracts_id` = '$instID'
                      AND `glpi_contracts_suppliers`.`suppliers_id`=`glpi_suppliers`.`id`".
                      getEntitiesRestrictRequest(" AND","glpi_suppliers",'','',true). "
                ORDER BY `glpi_entities`.`completename`, `name`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contract.form.php\">";
      echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='6'>".$LANG['financial'][65]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['financial'][26]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['financial'][79]."</th>";
      echo "<th>".$LANG['help'][35]."</th>";
      echo "<th>".$LANG['financial'][45]."</th>";
      echo "<th>&nbsp;</th></tr>";

      $used=array();
      while ($i < $number) {
         $ID=$DB->result($result, $i, "id");
         $website=$DB->result($result, $i, "glpi_suppliers.website");
         if (!empty($website)) {
            $website=$DB->result($result, $i, "website");
            if (!preg_match("?https*://?",$website)) {
               $website="http://".$website;
            }
            $website="<a target=_blank href='$website'>".$DB->result($result, $i, "website")."</a>";
         }
         $entID=$DB->result($result, $i, "entID");
         $entity=$DB->result($result, $i, "entity");
         $used[$entID]=$entID;
         $entname=Dropdown::getDropdownName("glpi_suppliers",$entID);
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=$entID'>".$entname;
         if ($_SESSION["glpiis_ids_visible"] || empty($entname)) {
            echo " ($entID)";
         }
         echo "</a></td>";
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$entity)."</td>";
         echo "<td class='center'>";
         echo Dropdown::getDropdownName("glpi_suppliertypes",$DB->result($result, $i, "type"))."</td>";
         echo "<td class='center'>".$DB->result($result, $i, "phone")."</td>";
         echo "<td class='center'>".$website."</td>";
         echo "<td class='tab_bg_2 center'>";
         if ($canedit) {
            echo "<a href='".$CFG_GLPI["root_doc"].
                  "/front/contract.form.php?deletecontractsupplier=1&amp;id=$ID&amp;contracts_id=".
                  $instID."'><img src='".$CFG_GLPI["root_doc"]."/pics/delete2.png' alt='".
                  $LANG['buttons'][6]."'></a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td></tr>";
         $i++;
      }
      if ($canedit) {
         if ($this->fields["is_recursive"]) {
            $nb=countElementsInTableForEntity("glpi_suppliers",getSonsOf("glpi_entities",
                                                                  $this->fields["entities_id"]));
         } else {
            $nb=countElementsInTableForEntity("glpi_suppliers",$this->fields["entities_id"]);
         }
         if ($nb>count($used)) {
            echo "<tr class='tab_bg_1'><td class='right' colspan='2'>";
            echo "<div class='software-instal'><input type='hidden' name='contracts_id' value='$instID'>";
            Dropdown::show('Supplier',
                     array('used'         => $used,
                           'entity'       => $this->fields["entities_id"],
                           'entity_sons'  => $this->fields["is_recursive"]));
            echo "</div></td><td class='center'>";
            echo "<input type='submit' name='addcontractsupplier' value=\"".
                   $LANG['buttons'][8]."\" class='submit'>";
            echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
            echo "</tr>";
         }
         }
      echo "</table></div></form>";
   }

   /**
    * Print the HTML array for Items linked to current contract
    *
    *@return Nothing (display)
    *
    **/
   function showItems() {
      global $DB,$CFG_GLPI, $LANG;

      $instID = $this->fields['id'];

      if (!$this->can($instID,'r')) {
         return false;
      }
      $canedit=$this->can($instID,'w');
      $rand=mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_contracts_items`
                WHERE `glpi_contracts_items`.`contracts_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      printPagerForm();
      echo "</th><th colspan='3'>".$LANG['document'][19].":</th></tr>";
      if ($canedit) {
         echo "</table></div>";

         echo "<form method='post' name='contract_form$rand' id='contract_form$rand' action=\"".
                $CFG_GLPI["root_doc"]."/front/contract.form.php\">";
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         // massive action checkbox
         echo "<tr><th>&nbsp;</th>";
      } else {
         echo "<tr>";
      }
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th></tr>";

      $totalnb=0;
      for ($i = 0 ; $i < $number; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {
            $itemtable=getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*, `glpi_contracts_items`.`id` AS IDD,
                           `glpi_entities`.`id` AS entity
                     FROM `glpi_contracts_items`, `$itemtable`";
            if ($itemtype != 'Entity') {
               $query .= " LEFT JOIN `glpi_entities`
                              ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
            }
            $query .= " WHERE `$itemtable`.`id` = `glpi_contracts_items`.`items_id`
                              AND `glpi_contracts_items`.`itemtype`='$itemtype'
                              AND `glpi_contracts_items`.`contracts_id` = '$instID'";

            if ($item->maybeTemplate()) {
               $query.=" AND `$itemtable`.`is_template`='0'";
            }
            $query .= getEntitiesRestrictRequest(" AND",$itemtable,'','',
                                                $item->maybeRecursive())."
                     ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

            $result_linked=$DB->query($query);
            $nb=$DB->numrows($result_linked);
            if ($nb>$_SESSION['glpilist_limit']) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>&nbsp;</td>";
               }
               echo "<td class='center'>".$item->getTypeName()."&nbsp;:&nbsp;$nb</td>";
               echo "<td class='center' colspan='2'>";
               echo "<a href='". getItemTypeSearchURL($itemtype) . "?" .
                     rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&amp;" .
                     rawurlencode("field[0]") . "=29&amp;sort=80&amp;order=ASC&amp;is_deleted=0".
                     "&amp;start=0". "'>" . $LANG['reports'][57]."</a></td>";
               echo "<td class='center'>-</td><td class='center'>-</td></tr>";
            } else if ($nb>0) {
               for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
                  $ID="";
                  if($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $ID= " (".$data["id"].")";
                  }
                  $link=getItemTypeFormURL($itemtype);
                  $name= "<a href=\"".$link."?id=".
                           $data["id"]."\">".$data["name"]."$ID</a>";

                  echo "<tr class='tab_bg_1'>";
                  if ($canedit) {
                     $sel="";
                     if (isset($_GET["select"]) && $_GET["select"]=="all") {
                        $sel="checked";
                     }
                     echo "<td width='10'>";
                     echo "<input type='checkbox' name='item[".$data["IDD"]."]' value='1' $sel></td>";
                  }
                  if ($prem) {
                     echo "<td class='center top' rowspan='$nb'>".$item->getTypeName().
                           ($nb>1?"&nbsp;:&nbsp;$nb</td>":"</td>");
                  }
                  echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data['entity'])."</td>";
                  echo "<td class='center";
                  echo (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                  echo ">".$name."</td>";
                  echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
                  echo "<td class='center'>".
                        (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                  echo "</tr>";
               }
            }
            $totalnb+=$nb;
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>".($totalnb>0? $LANG['common'][33].
             "&nbsp;=&nbsp;$totalnb</td>" : "&nbsp;</td>");
      echo "<td colspan='4'>&nbsp;</td></tr> ";

      if ($canedit) {
         if ($this->fields['max_links_allowed']==0
             || $this->fields['max_links_allowed'] > $totalnb) {

            echo "<tr class='tab_bg_1'><td colspan='4' class='right'>";
            echo "<div class='software-instal'>";
            Dropdown::showAllItems("items_id",0,0,($this->fields['is_recursive']?-1:
                             $this->fields['entities_id']),$CFG_GLPI["contract_types"]);
            echo "</div></td><td class='center'>";
            echo "<input type='submit' name='additem' value=\"".$LANG['buttons'][8]."\" class='submit'>";
            echo "</td><td>&nbsp;</td></tr>";
         }
         echo "</table></div>";

         openArrowMassive("contract_form$rand", true);
         echo "<input type='hidden' name='contracts_id' value='$instID'>";
         closeArrowMassive('deleteitem', $LANG['buttons'][6]);

      } else {
         echo "</table></div>";
      }
      echo "</form>";
   }


   /**
    * Get the entreprise name  for the contract
    *
    *@return string of names (HTML)
    **/
   function getSuppliersNames() {
      global $DB;

      $query = "SELECT `glpi_suppliers`.`id`
                FROM `glpi_contracts_suppliers`, `glpi_suppliers`
                WHERE `glpi_contracts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`
                      AND `glpi_contracts_suppliers`.`contracts_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
      $out="";
      while ($data=$DB->fetch_array($result)) {
         $out.= Dropdown::getDropdownName("glpi_suppliers",$data['id'])."<br>";
      }
      return $out;
   }

   /**
    * Print an HTML array of contract associated to an object
    *
    *
    *@param $item CommonDBTM : object wanted
    *@param $withtemplate='' not used (to be deleted)
    *
    *@return Nothing (display)
    *
    **/
   static function showAssociated(CommonDBTM $item, $withtemplate='') {
      global $DB,$CFG_GLPI, $LANG;

      $itemtype = $item->getType();
      $ID = $item->fields['id'];

      if (!haveRight("contract","r") || !$item->can($ID,"r")) {
         return false;
      }

      $canedit=$item->can($ID,"w");

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
         initNavigateListItems('Contract', $item->getTypeName()." = ".$item->getName());
      }
      $contracts=array();
      while ($i < $number) {
         $cID=$DB->result($result, $i, "contracts_id");
         addToNavigateListItems('Contract',$cID);
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
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$con->fields["entities_id"])."</td>";
         echo "<td class='center'>".$con->fields["num"]."</td>";
         echo "<td class='center'>".
                Dropdown::getDropdownName("glpi_contracttypes",$con->fields["contracttypes_id"])."</td>";
         echo "<td class='center'>".$con->getSuppliersNames()."</td>";
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
                                           $item->getEntityID(),true);;
      $result = $DB->query($q);
      $nb = $DB->numrows($result);

      if ($canedit) {
         if ($withtemplate!=2 && $nb>count($contracts)) {
            echo "<tr class='tab_bg_1'><td class='right' colspan='3'>";
            echo "<div class='software-instal'><input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            Contract::dropdown(array('entity' => $item->getEntityID(), 'used' => $contracts));
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

   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][5]);
   }

   /**
    * Cron action on contracts : alert depending of the config : on notice and expire
    *
    * @param $task for log, if NULL display
    *
    **/
   static function cronContract($task=NULL) {
      global $DB,$CFG_GLPI,$LANG;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message=array();
      $items_notice=array();
      $items_end=array();
      $cron_status = 0;


      $contract_infos[Alert::END] = array();
      $contract_infos[Alert::NOTICE] = array();
      $contract_messages = array();

      foreach (Entity::getEntitiesToNotify('use_contracts_alert') as $entity => $value) {
         $query_notice="SELECT `glpi_contracts`.*
                        FROM `glpi_contracts`
                        LEFT JOIN `glpi_alerts` ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                                                     AND `glpi_alerts`.`itemtype` = 'Contract'
                                                        AND `glpi_alerts`.`type`='".Alert::NOTICE."')
                       WHERE (`glpi_contracts`.`alert` & ".pow(2,Alert::NOTICE).") >'0'
                             AND `glpi_contracts`.`is_deleted` = '0'
                              AND `glpi_contracts`.`begin_date` IS NOT NULL
                                 AND `glpi_contracts`.`duration` <> '0'
                                    AND `glpi_contracts`.`notice` <> '0'
                                       AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                                  `glpi_contracts`.`duration` MONTH),CURDATE()) > '0'
                                          AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                                  (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                                  MONTH),CURDATE()) < '0'
                                             AND `glpi_alerts`.`date` IS NULL
                                                AND `glpi_contracts`.`entities_id`='".$entity."'";

         $query_end="SELECT `glpi_contracts`.*
                     FROM `glpi_contracts`
                     LEFT JOIN `glpi_alerts` ON (`glpi_contracts`.`id` = `glpi_alerts`.`items_id`
                                                 AND `glpi_alerts`.`itemtype` = 'Contract'
                                                   AND `glpi_alerts`.`type`='".Alert::END."')
                     WHERE (`glpi_contracts`.`alert` & ".pow(2,Alert::END).") > '0'
                           AND `glpi_contracts`.`is_deleted` = '0'
                              AND `glpi_contracts`.`begin_date` IS NOT NULL
                                 AND `glpi_contracts`.`duration` <> '0'
                                    AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                                (`glpi_contracts`.`duration`) MONTH),CURDATE()) < '0'
                                       AND `glpi_alerts`.`date` IS NULL
                                          AND `glpi_contracts`.`entities_id`='".$entity."'";

         $querys = array(Alert::NOTICE=>$query_notice, Alert::END=>$query_end);

         foreach ($querys as $type => $query) {
            foreach ($DB->request($query) as $data) {
               $entity = $data['entities_id'];
               $message = $data["name"].": ".
                           getWarrantyExpir($data["begin_date"],
                                            $data["duration"],
                                            $data["notice"])."<br>\n";
               $contract_infos[$type][$entity][$data['id']] = $data;

                if (!isset($contract_messages[$type][$entity])) {
                  $contract_messages[$type][$entity] = $LANG['mailing'][37]."<br />";
               }
               $contract_messages[$type][$entity] .= $message;
            }
         }
      }

      foreach (array(Alert::NOTICE=>"notice",Alert::END=>"end") as $type=>$event) {
         foreach ($contract_infos[$type] as $entity => $contracts) {
            if (NotificationEvent::raiseEvent($event,
                                              new Contract(),
                                              array('entities_id'=>$entity,
                                                    'contracts'=>$contracts))) {
               $message = $contract_messages[$type][$entity];
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
               $input["itemtype"] = 'Contract';
               $input["type"]=$type;
               foreach ($contracts as $id => $contract) {
                  $input["items_id"]=$id;

                  $alert->add($input);
                  unset($alert->fields['id']);
               }
            } else {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities",$entity).
                             ":  Send contract alert failed\n");
               } else {
                  addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",$entity).
                                          ":  Send contract alert failed",false,ERROR);
               }
            }
         }
      }

      return $cron_status;
   }

   /**
    * Print a select with contracts
    *
    * Print a select named $name with contracts options and selected value $value
    *
    *    - name : string / name of the select (default is contracts_id)
    *    - value : integer / preselected value (default 0)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - entity_sons : boolean / if entity restrict specified auto select its sons
    *                   only available if entity is a single value not an array (default false)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *    - nochecklimit : boolean / disable limit for nomber of device (for supplier, default false)
    *
    * @param $options possible options
    *
    *@return Nothing (display)
    *
    **/
   static function dropdown($options = array()) {
      global $DB;
      //$name,$entity_restrict=-1,$alreadyused=array(),$nochecklimit=false
      $p['name']           = 'contracts_id';
      $p['value']          = '';
      $p['entity']         = '';
      $p['entity_sons']    = false;
      $p['used']           = array();
      $p['nochecklimit']   = false;

      if (!($p['entity']<0) && $p['entity_sons']) {
         if (is_array($p['entity'])) {
            echo "entity_sons options is not available with array of entity";
         } else {
            $p['entity'] = getSonsOf('glpi_entities',$p['entity']);
         }
      }

      $entrest="";
      $idrest="";
      if ($p['entity']>=0) {
         $entrest=getEntitiesRestrictRequest("AND","glpi_contracts","entities_id",$p['entity'],true);
      }
      if (count($p['used'])) {
         $idrest=" AND `glpi_contracts`.`id` NOT IN(".implode("','",$p['used']).") ";
      }
      $query = "SELECT `glpi_contracts`.*
                FROM `glpi_contracts`
                LEFT JOIN `glpi_entities` ON (`glpi_contracts`.`entities_id` = `glpi_entities`.`id`)
                WHERE `glpi_contracts`.`is_deleted` = '0' $entrest $idrest
                ORDER BY `glpi_entities`.`completename`,
                           `glpi_contracts`.`name` ASC,
                           `glpi_contracts`.`begin_date` DESC";
      $result=$DB->query($query);
      echo "<select name='".$p['name']."'>";

      if ($p['value'] > 0) {
         $output=Dropdown::getDropdownName('glpi_contracts',$p['value']);
         if ($_SESSION["glpiis_ids_visible"]) {
            $output.=" (".$p['value'].")";
         }
         echo "<option selected value='".$p['value']."'>".$output."</option>";
      } else {
         echo "<option value='-1'>-----</option>";
      }
      $prev=-1;
      while ($data=$DB->fetch_array($result)) {
         if ($p['nochecklimit'] || $data["max_links_allowed"]==0
             || $data["max_links_allowed"]>countElementsInTable("glpi_contracts_items",
                                                               "contracts_id = '".$data['id']."'" )) {
            if ($data["entities_id"]!=$prev) {
               if ($prev>=0) {
                  echo "</optgroup>";
               }
               $prev=$data["entities_id"];
               echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
            }

            if ($_SESSION["glpiis_ids_visible"] || empty($output)) {
               $data["name"].=" (".$data["id"].")";
            }

            echo "<option  value='".$data["id"]."'>";
            echo utf8_substr($data["name"]." - #".$data["num"]." - ".
                             convDateTime($data["begin_date"]),0,$_SESSION["glpidropdown_chars_limit"]);
            echo "</option>";
         }
      }
      if ($prev>=0) {
         echo "</optgroup>";
      }
      echo "</select>";
   }

   /**
    * Print a select with contract renewal
    *
    * Print a select named $name with contract renewal options and selected value $value
    *
    *@param $name string : HTML select name
    *@param $value integer : HTML select selected value
    *
    *@return Nothing (display)
    *
    **/
   static function dropdownContractRenewal($name,$value=0) {
      global $LANG;

      echo "<select name='$name'>";
      echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
      echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['financial'][105]."</option>";
      echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['financial'][106]."</option>";
      echo "</select>";
   }

   /**
    * Get the renewal type name
    *
    *@param $value integer : HTML select selected value
    *
    *@return string
    *
    **/
   static function getContractRenewalName($value) {
      global $LANG;

      switch ($value) {
         case 1:
            return $LANG['financial'][105];
            break;

         case 2:
            return $LANG['financial'][106];
            break;

         default :
         return "";
      }
   }

   /**
    * Get renewal ID by name
    * @param $value the name of the renewal
    *
    * @return the ID of the renewal
    */
   static function getContractRenewalIDByName($value) {
      global $LANG;

      if (stristr($value,$LANG['financial'][105])) {
         return 1;
      } else if (stristr($value,$LANG['financial'][106])){
         return 2;
      }
      return 0;
   }

   /**
    * Dropdown for alerting of contracts
    *
   * @param $myname select name
    * @param $value default value
    */
   static function dropdownAlert($myname,$value) {
      global $LANG;

      echo "<select name='$myname'>";
      echo "<option value='0' ".($value==0?"selected":"")." >-------</option>";
      echo "<option value='".pow(2,Alert::END)."' ".($value==pow(2,Alert::END)?"selected":"")." >".
             $LANG['buttons'][32]."</option>";
      echo "<option value='".pow(2,Alert::NOTICE)."' ".($value==pow(2,Alert::NOTICE)?"selected":"")." >".
             $LANG['financial'][10]."</option>";
      echo "<option value='".(pow(2,Alert::END)+pow(2,Alert::NOTICE))."' ".
             ($value==(pow(2,Alert::END)+pow(2,Alert::NOTICE))?"selected":"")." >".
             $LANG['buttons'][32]." + ".$LANG['financial'][10]."</option>";
      echo "</select>";
   }
}

?>