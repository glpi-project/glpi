<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"rulecriteria.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

Session::checkLoginUser();

if (isset($_POST["sub_type"]) && ($rule = getItemForItemtype($_POST["sub_type"]))) {
   $criterias = $rule->getAllCriteria();

   if (count($criterias)) {
      // First include -> first of the predefined array
      if (!isset($_POST["criteria"])) {
         $_POST["criteria"] = key($criterias);
      }

      if (isset($criterias[$_POST["criteria"]]['allow_condition'])) {
         $allow_condition = $criterias[$_POST["criteria"]]['allow_condition'];
      } else {
         $allow_condition = array();
      }

      $condparam = array('criterion'        => $_POST["criteria"],
                         'allow_conditions' => $allow_condition);
      if (isset($_POST['condition'])) {
         $condparam['value'] = $_POST['condition'];
      }
      echo "<table width='100%'><tr><td width='30%'>";
      $randcrit = RuleCriteria::dropdownConditions($_POST["sub_type"], $condparam);
      echo "</td><td>";
      echo "<span id='condition_span$randcrit'>\n";
      echo "</span>\n";

      $paramscriteria = array('condition' => '__VALUE__',
                              'criteria'  => $_POST["criteria"],
                              'sub_type'  => $_POST["sub_type"]);

      Ajax::updateItemOnSelectEvent("dropdown_condition$randcrit", "condition_span$randcrit",
                                    $CFG_GLPI["root_doc"]."/ajax/rulecriteriavalue.php",
                                    $paramscriteria);

      if (isset($_POST['pattern'])) {
         $paramscriteria['value'] = stripslashes($_POST['pattern']);
      }

      Ajax::updateItem("condition_span$randcrit",
                       $CFG_GLPI["root_doc"]."/ajax/rulecriteriavalue.php", $paramscriteria,
                       "dropdown_condition$randcrit");
      echo "</td></tr></table>";
   }
}
?>
