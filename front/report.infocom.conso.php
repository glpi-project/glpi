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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG['Menu'][6], $_SERVER['PHP_SELF'], "utils", "report");

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
   $year = date("Y")-1;
   $_POST["date1"] = date("Y-m-d", mktime(1,0,0,date("m"),date("d"),$year));
   $_POST["date2"] = date("Y-m-d");
}

if (!empty($_POST["date1"])
    && !empty($_POST["date2"])
    && strcmp($_POST["date2"], $_POST["date1"]) < 0) {

   $tmp = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

echo "\n<form method='post' name='form' action='".$_SERVER['PHP_SELF']."'>";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td class='right'>".$LANG['search'][8]."&nbsp;:&nbsp;</td><td>";
showDateFormItem("date1", $_POST["date1"]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='button' name='submit' value='".$LANG['buttons'][7]."'></td></tr>\n";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;:&nbsp;</td><td>";
showDateFormItem("date2", $_POST["date2"]);
echo "</td></tr>";
echo "</table></form>\n";

$valeurtot           = 0;
$valeurnettetot      = 0;
$valeurnettegraphtot = array();
$valeurgraphtot      = array();


/** Display an infocom report for items like consumables
 *
 * @param $itemtype item type
 * @param $begin begin date
 * @param $end end date
**/
function display_infocoms_report($itemtype, $begin, $end) {
   global $DB, $valeurtot, $valeurnettetot, $valeurnettegraphtot, $valeurgraphtot, $LANG, $CFG_GLPI;

   $itemtable = getTableForItemType($itemtype);
   $query = "SELECT `glpi_infocoms`.*
             FROM `glpi_infocoms`
             INNER JOIN `$itemtable`
                  ON (`$itemtable`.`id` = `glpi_infocoms`.`items_id`
                      AND `glpi_infocoms`.`itemtype`='$itemtype') ";

   switch ($itemtype) {
      case 'Consumable' :
         $query .= " INNER JOIN `glpi_consumableitems`
                        ON (`glpi_consumables`.`consumableitems_id` = `glpi_consumableitems`.`id`) ".
                     getEntitiesRestrictRequest("WHERE","glpi_consumableitems");
         break;

      case 'Cartridge' :
         $query .= " INNER JOIN `glpi_cartridgeitems`
                        ON (`glpi_cartridges`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`) ".
                     getEntitiesRestrictRequest("WHERE","glpi_cartridgeitems");
         break;

      case 'SoftwareLicense' :
         $query .= " INNER JOIN `glpi_softwares`
                        ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`) ".
                     getEntitiesRestrictRequest("WHERE","glpi_softwarelicenses");
         break;
   }

   if (!empty($begin)) {
      $query .= " AND (`glpi_infocoms`.`buy_date` >= '$begin'
                       OR `glpi_infocoms`.`use_date` >= '$begin')";
   }
   if (!empty($end)) {
      $query .= " AND (`glpi_infocoms`.`buy_date` <= '$end'
                       OR `glpi_infocoms`.`use_date` <= '$end')";
   }

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) >0) {
         $item = new $itemtype();

         echo "<h2>".$item->getTypeName()."</h2>";
         echo "<table class='tab_cadre'>";

         $valeursoustot      = 0;
         $valeurnettesoustot = 0;
         $valeurnettegraph   = array();
         $valeurgraph        = array();

         while ($line=$DB->fetch_array($result)) {
            if ($itemtype == 'SoftwareLicense') {
               $item->getFromDB($line["items_id"]);

               if ($item->fields["serial"] == "global") {
                  if ($item->fields["number"] > 0) {
                     $line["value"] *= $item->fields["number"];
                  }
               }

            }
            if ($line["value"] >0) {
               $valeursoustot += $line["value"];
            }

            $valeurnette = Infocom::Amort($line["sink_type"], $line["value"], $line["sink_time"],
                                          $line["sink_coeff"], $line["buy_date"], $line["use_date"],
                                          $CFG_GLPI["date_tax"], "n");

            $tmp = Infocom::Amort($line["sink_type"], $line["value"], $line["sink_time"],
                                  $line["sink_coeff"], $line["buy_date"], $line["use_date"],
                                  $CFG_GLPI["date_tax"], "all");

            if (is_array($tmp) && count($tmp) >0) {
               foreach ($tmp["annee"] as $key => $val) {

                  if ($tmp["vcnetfin"][$key] >0) {
                     if (!isset($valeurnettegraph[$val])) {
                        $valeurnettegraph[$val] = 0;
                     }
                     $valeurnettegraph[$val] += $tmp["vcnetdeb"][$key];
                  }

               }
            }

            if (!empty($line["buy_date"])) {
               $year = substr($line["buy_date"],0,4);

               if ($line["value"] >0) {
                  if (!isset($valeurgraph[$year])) {
                     $valeurgraph[$year] = 0;
                  }
                  $valeurgraph[$year] += $line["value"];
               }

            }
            $valeurnettesoustot += str_replace(" ","",$valeurnette);
         }

         $valeurtot += $valeursoustot;
         $valeurnettetot += $valeurnettesoustot;

         if (count($valeurnettegraph) >0) {
            echo "<tr><td colspan='5' class='center'>";
            ksort($valeurnettegraph);
            $valeurnettegraphdisplay = array_map('round', $valeurnettegraph);

            foreach ($valeurnettegraph as $key => $val) {
               if (!isset($valeurnettegraphtot[$key])) {
                  $valeurnettegraphtot[$key] = 0;
               }
               $valeurnettegraphtot[$key] += $valeurnettegraph[$key];
            }

            Stat::showGraph(array($LANG['financial'][81] => $valeurnettegraphdisplay),
                            array('title' => $LANG['financial'][81],
                                  'width' => 500));

            echo "</td></tr>\n";
         }

         if (count($valeurgraph) >0) {
            echo "<tr><td colspan='5' class='center'>";
            ksort($valeurgraph);
            $valeurgraphdisplay = array_map('round', $valeurgraph);

            foreach ($valeurgraph as $key => $val) {
               if (!isset($valeurgraphtot[$key])) {
                  $valeurgraphtot[$key] = 0;
               }
               $valeurgraphtot[$key] += $valeurgraph[$key];
            }

            Stat::showGraph(array($LANG['financial'][21] => $valeurgraphdisplay),
                            array('title' => $LANG['financial'][21],
                                  'width' => 500));

            echo "</td></tr>";
         }
         echo "</table>\n";
         return true;
      }
   }
   return false;
}


$types = array('Consumable', 'Cartridge', 'SoftwareLicense');

$i = 0;
while (count($types)>0) {
   if ($i==0){
      echo "<table width='90%'><tr><td class='center top'>";
   }
   $type = array_shift($types);

   if (display_infocoms_report($type,$_POST["date1"],$_POST["date2"])) {
      echo "</td>";
      $i++;

      if (($i%2)==0){
         echo "</tr><tr>";
      }

      echo "<td class='center top'>";
   }
}

if (($i%2)==0){
   echo "</td><td>&nbsp;";
}

if ($i>0){
   echo "&nbsp;</td></tr></table>";
}

echo "<div class='center'><h3>".$LANG['common'][33]."&nbsp;: ".
      $LANG['financial'][21]." = ".formatNumber($valeurtot)." - ".
      $LANG['financial'][81]." = ".formatNumber($valeurnettetot)."</h3></div>\n";

if (count($valeurnettegraphtot) >0) {
   $valeurnettegraphtotdisplay = array_map('round', $valeurnettegraphtot);
   Stat::showGraph(array($LANG['financial'][81] => $valeurnettegraphtotdisplay),
                   array('title' => $LANG['financial'][81]));

}
if (count($valeurgraphtot) >0) {
   $valeurgraphtotdisplay = array_map('round', $valeurgraphtot);
   Stat::showGraph(array($LANG['financial'][21] => $valeurgraphtotdisplay),
                   array('title' => $LANG['financial'][21]));
}

commonFooter();

?>
