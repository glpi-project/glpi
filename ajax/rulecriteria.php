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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"rulecriteria.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();

if (isset($_POST["sub_type"])) {
   $type      = $_POST["sub_type"];
   $rule      = new $type();
   $criterias = $rule->getCriterias();

   if (count($criterias)) {
      // First include -> first of the predefined array
      if (!isset($_POST["criteria"])) {
         $_POST["criteria"] = key($criterias);
      }
      $type = "";

      if (isset($criterias[$_POST["criteria"]]['type'])) {
         $type = $criterias[$_POST["criteria"]]['type'];
      }

      if (isset($criterias[$_POST["criteria"]]['allow_condition'])) {
         $allow_condition = $criterias[$_POST["criteria"]]['allow_condition'];
      } else {
         $allow_condition = array();
      }

      $randcrit = RuleCriteria::dropdownConditions($type, "condition", '', $allow_condition);

      echo "&nbsp;&nbsp;";
      echo "<span id='condition_span'>\n";
      echo "</span>\n";

      $paramscriteria = array('condition' => '__VALUE__',
                              'criteria'  => $_POST["criteria"],
                              'sub_type'  => $_POST["sub_type"]);

      ajaxUpdateItemOnSelectEvent("dropdown_condition$randcrit", "condition_span",
                                  $CFG_GLPI["root_doc"]."/ajax/rulecriteriavalue.php",
                                  $paramscriteria, false);

      ajaxUpdateItem("condition_span", $CFG_GLPI["root_doc"]."/ajax/rulecriteriavalue.php",
                     $paramscriteria, false, "dropdown_condition$randcrit");
   }
}

?>
