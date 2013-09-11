<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * @since version 0.84
**/
class IPNetwork_Vlan extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'IPNetwork';
   static public $items_id_1          = 'ipnetworks_id';

   static public $itemtype_2          = 'Vlan';
   static public $items_id_2          = 'vlans_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.'update';
      return $forbidden;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();
      return $tab;
   }


  /**
    * @param $portID
    * @param $vlanID
   **/
   function unassignVlan($portID, $vlanID) {

      $this->getFromDBByQuery("WHERE `ipnetworks_id` = '$portID'
                                     AND `vlans_id` = '$vlanID'");

      return $this->delete($this->fields);
   }


   /**
    * @param $port
    * @param $vlan
   **/
   function assignVlan($port, $vlan) {

      $input = array('ipnetworks_id' => $port,
                     'vlans_id'      => $vlan);

      return $this->add($input);
   }


   /**
    * @param $port   IPNetwork object
   **/
   static function showForIPNetwork(IPNetwork $port) {
      global $DB, $CFG_GLPI;

      $ID = $port->getID();
      if (!$port->can($ID, READ)) {
         return false;
      }

      $canedit = $port->canEdit($ID);
      $rand    = mt_rand();

      $query = "SELECT `".self::getTable()."`.id as assocID,
                       `glpi_vlans`.*
                FROM `".self::getTable()."`
                LEFT JOIN `glpi_vlans`
                        ON (`".self::getTable()."`.`vlans_id` = `glpi_vlans`.`id`)
                WHERE `ipnetworks_id` = '$ID'";

      $result = $DB->query($query);
      $vlans  = array();
      $used   = array();
      if ($number = $DB->numrows($result)) {
         while ($line = $DB->fetch_assoc($result)) {
            $used[$line["id"]]       = $line["id"];
            $vlans[$line["assocID"]] = $line;
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>\n";
         echo "<form method='post' action='".static::getFormURL()."'>\n";
         echo "<table class='tab_cadre_fixe'>\n";
         echo "<tr><th>".__('Associate a VLAN')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='center'>";
         echo "<input type='hidden' name='ipnetworks_id' value='$ID'>";
         Vlan::dropdown(array('used' => $used));
         echo "&nbsp;<input type='submit' name='add' value='"._sx('button','Associate').
                      "' class='submit'>";
         echo "</td></tr>\n";

         echo "</table>\n";
         Html::closeForm();
         echo "</div>\n";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed' => $number,
                                      'container'     => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('ID TAG')."</th>";
      echo "</tr>";

      $used = array();
      foreach ($vlans as $data) {
         echo "<tr class='tab_bg_1'>";
         if ($canedit) {
            echo "<td>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
            echo "</td>";
         }
         $name = $data["name"];
         if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
            $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
         }
         echo "<td class='center b'>
               <a href='".$CFG_GLPI["root_doc"]."/front/vlan.form.php?id=".$data["id"]."'>".$name.
              "</a>";
         echo "</td>";
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data["entities_id"]);
         echo "<td class='numeric'>".$data["tag"]."</td>";
         echo "</tr>";
      }

      echo "</table>";
      if ($canedit && $number) {
         // TODO check because we switched from $paramsma to $massiveactionparams
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }


   /**
    * @param $portID
   **/
   static function getVlansForIPNetwork($portID) {
      global $DB;

      $vlans = array();
      $query = "SELECT `vlans_id`
                FROM `".self::getTable()."`
                WHERE `ipnetworks_id` = '$portID'";
      foreach ($DB->request($query) as $data) {
         $vlans[$data['vlans_id']] = $data['vlans_id'];
      }

      return $vlans;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'IPNetwork' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(Vlan::getTypeName(),
                                              countElementsInTable($this->getTable(),
                                                                   "ipnetworks_id
                                                                        = '".$item->getID()."'"));
               }
               return Vlan::getTypeName();
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='IPNetwork') {
         self::showForIPNetwork($item);
      }
      return true;
   }

}
?>
