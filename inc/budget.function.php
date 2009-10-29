<?php
/*
 * @version $Id: contract.function.php 8498 2009-07-25 19:11:33Z moyo $
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
 * Print the HTML array of Items on a budget
 *
 *@param $budgets_id array : Budget identifier.
 *
 *@return Nothing (display)
 *
 **/
function showDeviceBudget($budgets_id) {
   global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE,$SEARCH_PAGES;

   if (!haveRight("budget","r")) {
      return false;
   }

   $query = "SELECT DISTINCT `itemtype`
             FROM `glpi_infocoms`
             WHERE `budgets_id` = '$budgets_id'
             ORDER BY `itemtype`";

   $result = $DB->query($query);
   $number = $DB->numrows($result);
   $i = 0;

   echo "<br><br><div class='center'><table class='tab_cadrehov'>";
   echo "<tr><th colspan='2'>";
   printPagerForm();
   echo "</th><th colspan='3'>".$LANG['document'][19]."&nbsp;:</th></tr>";
   echo "<tr><th>".$LANG['common'][17]."</th>";
   echo "<th>".$LANG['entity'][0]."</th>";
   echo "<th>".$LANG['common'][16]."</th>";
   echo "<th>".$LANG['common'][19]."</th>";
   echo "<th>".$LANG['common'][20]."</th>";
   echo "</tr>";
   $ci=new CommonItem;
   $num=0;
   while ($i < $number) {
      $itemtype=$DB->result($result, $i, "itemtype");
      if (haveTypeRight($itemtype,"r") && $itemtype!=CONSUMABLEITEM_TYPE
          && $itemtype!=CARTRIDGEITEM_TYPE && $itemtype!=SOFTWARE_TYPE) {
         $query = "SELECT ".$LINK_ID_TABLE[$itemtype].".*
                   FROM `glpi_infocoms`
                   INNER JOIN ".$LINK_ID_TABLE[$itemtype]."
                              ON (".$LINK_ID_TABLE[$itemtype].".`id` = `glpi_infocoms`.`items_id`)
                   WHERE `glpi_infocoms`.`itemtype`='$itemtype'
                         AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                           getEntitiesRestrictRequest(" AND",$LINK_ID_TABLE[$itemtype])."
                   ORDER BY `entities_id`, ".$LINK_ID_TABLE[$itemtype].".`name`";

         $result_linked=$DB->query($query);
         $nb=$DB->numrows($result_linked);
         $ci->setType($itemtype);
         if ($nb>$_SESSION['glpilist_limit'] && isset($SEARCH_PAGES[$itemtype])) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center'>".$ci->getType()."<br />$nb</td>";
            echo "<td class='center' colspan='2'>";
            echo "<a href='". $CFG_GLPI["root_doc"]."/".$SEARCH_PAGES[$itemtype] . "?" .
                   rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$budgets_id) . "&" .
                   rawurlencode("field[0]") . "=50&sort=80&order=ASC&is_deleted=0&start=0". "'>" .
                   $LANG['reports'][57]."</a></td>";
            echo "<td class='center'>-</td><td class='center'>-</td></tr>";
         } else if ($nb) {
            for ($prem=true;$data=$DB->fetch_assoc($result_linked);$prem=false) {
               $ID="";
               if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                  $ID= " (".$data["id"].")";
               }
               $name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$itemtype].
                        "?id=".$data["id"]."\">".$data["name"]."$ID</a>";
               echo "<tr class='tab_bg_1'>";
               if ($prem) {
                  echo "<td class='center top' rowspan='$nb'>".$ci->getType()
                        .($nb>1?"<br />$nb</td>":"</td>");
               }
               echo "<td class='center'>".getDropdownName("glpi_entities",$data["entities_id"]);
               echo "</td><td class='center";
               echo (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$name."</td>";
               echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-");
               echo "</td><td class='center'>".
                           (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
               echo "</tr>";
            }
         }
         $num+=$nb;
      }
      $i++;
   }
   echo "<tr class='tab_bg_2'><td class='center'>$num</td><td colspan='4'>&nbsp;</td></tr> ";
   echo "</table></div>";
}

/**
 * Print the HTML array of value consumed for a budget
 *
 *@param $budgets_id array : Budget identifier.
 *
 *@return Nothing (display)
 *
 **/
function showDeviceBudgetValue($budgets_id) {
   global $DB,$LANG,$CFG_GLPI,$LINK_ID_TABLE;

   if (!haveRight("budget","r")) {
      return false;
   }

   $query = "SELECT DISTINCT `itemtype`
             FROM `glpi_infocoms`
             WHERE `budgets_id` = '$budgets_id'
             GROUP BY `itemtype`";

   $result = $DB->query($query);
   $total = 0;

   $entities_values = array();

   // Type for which infocom are only template
   $ignore = array(CARTRIDGEITEM_TYPE, CONSUMABLEITEM_TYPE, SOFTWARE_TYPE);

   if ( $DB->numrows($result) ) {
      while ($types = $DB->fetch_array($result)) {
        if (in_array($types['itemtype'], $ignore)) {
           continue;
        }
        $table = $LINK_ID_TABLE[$types['itemtype']];
        $query_infos = "SELECT SUM(`glpi_infocoms`.`value`) AS `sumvalue`,
                               `$table`.`entities_id`
                        FROM `$table`
                        INNER JOIN `glpi_infocoms`
                           ON (`glpi_infocoms`.`items_id` = `$table`.`id`
                               AND `glpi_infocoms`.`itemtype` = '".$types['itemtype']."')
                        LEFT JOIN `glpi_entities` ON (`$table`.`entities_id` = `glpi_entities`.`id`)
                        WHERE `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                              getEntitiesRestrictRequest(" AND",$table,"entities_id");
         if (in_array($table,$CFG_GLPI["template_tables"])) {
            $query_infos .= " AND `$table`.`is_template`='0' ";
         }
         $query_infos .= "GROUP BY `$table`.`entities_id`
                          ORDER BY `glpi_entities`.`completename` ASC";

         $result_infos = $DB->query($query_infos);

         //Store, for each entity, the budget spent
         while ($values = $DB->fetch_array($result_infos)) {
            if (!isset($entities_values[$values['entities_id']])) {
               $entities_values[$values['entities_id']] = 0;
            }
            $entities_values[$values['entities_id']] += $values['sumvalue'];
         }
      }

      $ci=new CommonItem;
      $budget = new Budget();
      $budget->getFromDB($budgets_id);

      echo "<br><br><div class='center'><table class='tab_cadre'>";
      echo "<tr>";
      echo "<th colspan='2'>".$LANG['financial'][108]."</th></tr>";
      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['financial'][21]."</th>";
      echo "</tr>";

      foreach ($entities_values as $entity => $value) {
         echo "<tr class='tab_bg_1'><td>".getDropdownName('glpi_entities',$entity)."</th>";
         echo "<td class='right'>".formatNumber($value)."</td>";
         echo "</tr>";
         $total += $value;
      }

      echo "<tr class='tab_bg_1'><th colspan='2'><br></th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='right'>".$LANG['financial'][108]."</td>";
      echo "<td class='right b' colspan='2'>".formatNumber($total)."</td></tr>";
      if ($_SESSION['glpiactive_entity'] == $budget->fields['entities_id']) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='right'>".$LANG['financial'][109]."</td>";
         echo "<td class='right b'>".formatNumber($budget->fields['value'] - $total)."</td></tr>";
      }
      echo "</table></div>";

   }
}

?>