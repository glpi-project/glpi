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
 *  Contract class
 */
class Contract extends CommonDBTM {

    /**
     * Constructor
    **/
   function __construct () {
      $this->table="glpi_contracts";
      $this->type=CONTRACT_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["alert"]=$CFG_GLPI["default_contract_alert"];
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $cs = new ContractSupplier();
      $cs->cleanDBonItemDelete($this->type,$ID);

      $ci = new ContractItem();
      $ci->cleanDBonItemDelete($this->type,$ID);
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID > 0) {
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
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Clean end alert if begin_date is after old one
      // Or if duration is greater than old one
      if ((isset($oldvalues['begin_date']) && ($oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($oldvalues['duration']) && ($oldvalues['duration'] < $this->fields['duration']))) {
         $alert=new Alert();
         $alert->clear($this->type,$this->fields['id'],ALERT_END);
      }

      // Clean notice alert if begin_date is after old one
      // Or if duration is greater than old one
      // Or if notice is lesser than old one
      if ((isset($oldvalues['begin_date']) && ($oldvalues['begin_date'] < $this->fields['begin_date']))
          || (isset($oldvalues['duration']) && ($oldvalues['duration'] < $this->fields['duration']))
          || (isset($oldvalues['notice']) && ($oldvalues['notice'] > $this->fields['notice']))) {
         $alert=new Alert();
         $alert->clear($this->type,$this->fields['id'],ALERT_NOTICE);
      }
      return array($input,$updates);
   }

   /**
    * Print the contract form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *@param $withtemplate integer template or basic item
    *
     *@return boolean item found
    **/
   function showForm ($target,$ID,$withtemplate='') {
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
         $this->getEmpty();
      }

      $can_edit=$this->can($ID,'w');

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td><td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['financial'][6]."&nbsp;:</td><td >";
      dropdownValue("glpi_contractstypes","contractstypes_id",$this->fields["contractstypes_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][4]."&nbsp:</td>";
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
      dropdownInteger("duration",$this->fields["duration"],0,120);
      echo " ".$LANG['financial'][57];
      if (!empty($this->fields["begin_date"])) {
         echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"]);
      }
      echo "</td>";
      echo "<td>".$LANG['financial'][13]."&nbsp;:</td><td>";
      autocompletionTextField("accounting_number",$this->table,"accounting_number",
                              $this->fields["accounting_number"],40,$this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][69]."&nbsp;:</td><td>";
      dropdownInteger("periodicity",$this->fields["periodicity"],12,60,12,array(0=>"-----",
                                                                                1=>"1",
                                                                                2=>"2",
                                                                                3=>"3",
                                                                                6=>"6"));
      echo " ".$LANG['financial'][57];
      echo "</td>";
      echo "<td>".$LANG['financial'][10]."&nbsp;:</td><td>";
      dropdownInteger("notice",$this->fields["notice"],0,120);
      echo " ".$LANG['financial'][57];
      if (!empty($this->fields["begin_date"]) && $this->fields["notice"]>0) {
         echo " -> ".getWarrantyExpir($this->fields["begin_date"],$this->fields["duration"],
                                      $this->fields["notice"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['financial'][107]."&nbsp;:</td><td>";
      dropdownContractRenewal("renewal",$this->fields["renewal"]);
      echo "</td>";
      echo "<td>".$LANG['financial'][11]."&nbsp;:</td>";
      echo "<td>";
      dropdownInteger("billing",$this->fields["billing"],12,60,12,array(0=>"-----",
                                                                        1=>"1",
                                                                        2=>"2",
                                                                        3=>"3",
                                                                        6=>"6"));
      echo " ".$LANG['financial'][57];
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['financial'][83]."&nbsp;:</td><td>";
      dropdownInteger("max_links_allowed",$this->fields["max_links_allowed"],0,200);
      echo "</td>";
      echo "<td>".$LANG['common'][41]."</td>";
      echo "<td>";
      dropdownContractAlerting("alert",$this->fields["alert"]);
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
      dropdownHours("week_begin_hour",$this->fields["week_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      dropdownHours("week_end_hour",$this->fields["week_end_hour"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][61]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      dropdownYesNo("use_saturday",$this->fields["use_saturday"]);
      echo "&nbsp;".$LANG['buttons'][33]."&nbsp;:&nbsp;&nbsp;";
      dropdownHours("saturday_begin_hour",$this->fields["saturday_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      dropdownHours("saturday_end_hour",$this->fields["saturday_end_hour"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][62]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      dropdownYesNo("use_monday",$this->fields["use_monday"]);
      echo "&nbsp;".$LANG['buttons'][33]."&nbsp;:&nbsp;&nbsp;";
      dropdownHours("monday_begin_hour",$this->fields["monday_begin_hour"]);
      echo "&nbsp;&nbsp;&nbsp;".$LANG['buttons'][32]."&nbsp;:&nbsp;&nbsp;";
      dropdownHours("monday_end_hour",$this->fields["monday_end_hour"]);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }
}

// Relation between Contracts and Items
class ContractItem extends CommonDBRelation{

   /**
    * Constructor
    **/
   function __construct () {
      $this->table = 'glpi_contracts_items';
      $this->type = CONTRACTITEM_TYPE;

      $this->itemtype_1 = CONTRACT_TYPE;
      $this->items_id_1 = 'contracts_id';

      $this->itemtype_2 = 'itemtype';
      $this->items_id_2 = 'items_id';
   }

   /**
    * Check right on an contract - overloaded to check max_links_allowed
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      if ($ID<0) {
         // Ajout
         $contract = new Contract();

         if (!$contract->getFromDB($input['contracts_id'])) {
            return false;
         }
         if ($contract->fields['max_links_allowed'] > 0
             && countElementsInTable($this->table,
                                     "`contracts_id`='".$input['contracts_id']."'") >=
                                          $contract->fields['max_links_allowed']) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }

}

// Relation between Contracts and Suppliers
class ContractSupplier extends CommonDBRelation {

   /**
    * Constructor
    **/
   function __construct () {
      $this->table = 'glpi_contracts_suppliers';
      $this->type = CONTRACTSUPPLIER_TYPE;

      $this->itemtype_1 = CONTRACT_TYPE;
      $this->items_id_1 = 'contracts_id';

      $this->itemtype_2 = ENTERPRISE_TYPE;
      $this->items_id_2 = 'suppliers_id';
   }

}
?>