<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
   define('GLPI_ROOT', realpath('..'));
   include (GLPI_ROOT . "/inc/includes.php");
}

Session::checkCentralAccess();

if (!strpos($_SERVER['PHP_SELF'],"popup")) {
   Html::header(Rule::getTypeName(2), $_SERVER['PHP_SELF'], "admin", "dictionnary", "cache");
}

if (isset($_GET["sub_type"])) {
   echo "<br>";
   $rulecollection = RuleCollection::getClassByType($_GET["sub_type"]);

   if ($rulecollection->canView()) {
      if (!isset($_GET["rules_id"])) {
         $rulecollection->showCacheStatusForRuleType();
      } else {
         if ($rule = getItemForItemtype($_GET["sub_type"])) {
            $rule->getRuleWithCriteriasAndActions($_GET["rules_id"],0,0);
            $rule->showCacheStatusByRule($_SERVER["HTTP_REFERER"]);
         }
      }
   }
}

if (!strpos($_SERVER['PHP_SELF'],"popup")) {
   Html::footer();
}
?>