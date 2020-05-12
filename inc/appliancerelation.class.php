<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This is a special relation between
 *    a glpi_application_items
 *    a dropdwn (glpi_locations, glpi_domains, glpi_networks)
**/
class ApplianceRelation extends CommonDBTM {


   static function getTypeTable($value) {

      switch ($value) {
         case 1:
            $table = "glpi_locations";
            break;

         case 2:
            $table = "glpi_networks";
            break;

         case 3:
            $table = "glpi_domains";
            break;

         default:
            $table ="";
      }
      return $table;
   }


   static function getItemType($value) {

      switch ($value) {
         case 1 : // Location
            $name = "Location";
            break;

         case 2 : // Réseau
            $name = "Network";
            break;

         case 3 : // Domain
            $name = "Domain";
            break;

         default:
            $name ="";
      }
      return $name;
   }


   static function getTypeName($value = 0) {

      switch ($value) {
         case 1 : // Location
            $name = Location::getTypeName(1);
            break;

         case 2 : // Réseau
            $name = Network::getTypeName(1);
            break;

         case 3 : // Domain
            $name = Domain::getTypeName(1);
            break;

         default :
            $name = "&nbsp;";
      }
      return $name;
   }


   static function dropdownType($myname, $value = 0) {
      Dropdown::showFromArray(
         $myname, [
            0 => Dropdown::EMPTY_VALUE,
            1 => Location::getTypeName(1),
            2 => Network::getTypeName(1),
            3 => Domain::getTypeName(1)
         ], [
            'value' => $value
         ]
      );
   }


   /**
    * Show the relation for a device/applicatif
    *
    * Called from Appliance->showItem and Appliance::showAssociated
    *
    * @param integer $relationtype               type of the relation
    * @param integer $relID                      ID of the relation
    * @param integer $entity                     ID of the entity of the device
    * @param boolean $canedit                    if user is allowed to edit the relation
    *    - canedit the device if called from the device form
    *    - must be false if called from the applicatif form
    *
   **/
   static function showList($relationtype, $relID, $entity, $canedit) {
      global $DB;

      if (!$relationtype) {
         return false;
      }

      // selects all the attached relations
      $itemtype = self::getItemType($relationtype);
      $title    = self::getTypeName($relationtype);

      $field    = 'name AS dispname';
      if ($itemtype == 'Location') {
         $field = 'completename AS dispname';
      }

      $sql_loc = [
         'SELECT'    => ['glpi_appliancerelations.id', $field],
         'FROM'      => $itemtype::getTable(),
         'LEFT JOIN' => [
            'glpi_appliancerelations' => [
               'ON' => [
                  $itemtype::getTable()         => 'id',
                  'glpi_appliancerelations'   => 'relations_id'
               ]
            ],
            'glpi_appliances_items' => [
               'ON' => [
                  'glpi_appliancerelations'   => 'appliances_items_id',
                  'glpi_appliances_items'       => 'id'
               ]
            ]
         ],
         'WHERE'     => ['glpi_appliances_items.id' => $relID]
      ];

      $result_loc = $DB->request($sql_loc);
      $number_loc = count($result_loc);

      if ($canedit) {
         echo "<form method='post' name='relation' action='".Appliance::getFormURL()."'>";
         echo "<input type='hidden' name='deviceID' value='".$relID."'>";

         $i    = 0;
         $used = [];

         if ($number_loc >0) {
            echo "<table>";
            while ($i < $number_loc) {
               $res = $result_loc->next();
               echo "<tr><td class=top>";
               // when the value of the checkbox is changed, the corresponding hidden variable value
               // is also changed by javascript
               echo "<input type='checkbox' name='itemrelation[".$res["id"]. "]' value='1'></td><td>";
               echo $res["dispname"];
               echo "</td></tr>";
               $i++;
            }
            echo "</table>";
            echo "<input type='submit' name='delrelation' value='"._sx('button', 'Delete permanently')."'
                   class='submit'>";
         }

         echo $title;

         Dropdown::show(
            $itemtype, [
               'name'   => "tablekey[" . $relID . "]",
               'entity' => $entity,
               'used'   => $used
            ]
         );
         echo "&nbsp;<input type='submit' name='addrelation' value=\""._sx('button', 'Add').
               "\" class='submit'><br>&nbsp;";
         Html::closeForm();

      } else if ($number_loc > 0) {
         while ($res = $result_loc->next()) {
            echo $res["dispname"]."<br>";
         }
      } else {
         echo "&nbsp;";
      }
   }
}
