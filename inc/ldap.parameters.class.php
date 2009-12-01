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
// Original Author of file: Olivier Andreotti
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// LDAP criteria class
class LdapCriteria extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_ruleldapparameters';

   /// Get parameters list
   function getParametersList() {
      global $DB;

      $sql = "SELECT *
              FROM `".$this->table."`
              ORDER BY `name` ASC";
      $result = $DB->query($sql);
      $parameters = array ();

      while ($datas = $DB->fetch_array($result)) {
         $parameters[] = $datas;
      }

      return $parameters;
   }

   /**
    * Print the ldap criteria form
    *
    *@param $target filename : where to go when done.
    **/
   function showForm($target) {
      global $LANG,$CFG_GLPI;

      $canedit = haveRight("config", "w");
      $ID=-1;
      $parameters = $this->getParametersList();

      echo "<form name='entityaffectation_form' id='ldapcriterias_form' method='post' ".
           "action=\"$target\">";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th colspan='3'>" .$LANG['ruleldap'][3] . "</tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>".$LANG['common'][16] . "&nbsp;:&nbsp";
         autocompletionTextField("name", $this->table, "name", "", 40);
         echo "</td><td>".$LANG['setup'][601] . "&nbsp;:&nbsp";
         autocompletionTextField("value", $this->table, "value", "", 40);
         echo "</td><td><input type='submit' name='add' value=\"" . $LANG['buttons'][8] .
                     "\" class='submit'>";
         echo "</td></tr>";
         echo "</table></div><br>";
      }

      if (!count($parameters)) {
         echo "<center>".$LANG['ruleldap'][2]."</center>";
      } else {
         echo "<div class='center'><table class='tab_cadrehov'>";
         echo "<tr><th colspan='3'>" . $LANG['common'][53]." ".$LANG['ruleldap'][1] . "</th></tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2' colspan='2'>" . $LANG['common'][16]."</td>";
         echo "<td class='tab_bg_2'>".$LANG['setup'][601] . "</td></tr>";

         foreach ($parameters as $parameter) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               $sel = "";
               if (isset ($_GET["select"]) && $_GET["select"] == "all") {
                  $sel = "checked";
               }
               echo "<input type='checkbox' name='item[" . $parameter["id"] . "]' value='1' $sel>";
               echo "</td>";
            }
            echo "<td>" . $parameter["name"] . "</td>";
            echo "<td>" . $parameter["value"] . "</td>";
            echo "</tr>";
         }
         echo "</table></div>";

         if ($canedit) {
            openArrowMassive("ldapcriterias_form");
            closeArrowMassive('delete', $LANG['buttons'][6]);
         }
      }
      echo "</form>";
   }

   function prepareInputForAdd($input) {

      //LDAP parameters MUST be in lower case
      //because the are retieved in lower case  from the directory
      $input["value"] = utf8_strtolower($input["value"]);
      return $input;
   }

   function title() {
      global $LANG,$CFG_GLPI;

      displayTitle('','','',array($CFG_GLPI["root_doc"].
                        "/front/ruleright.php"=>$LANG['buttons'][13]));
      echo "<br>";
   }

}

?>
