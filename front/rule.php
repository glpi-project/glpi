<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

include ('../inc/includes.php');

Session::checkCentralAccess();

Html::header(Rule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "admin", "rule", -1);

RuleCollection::titleBackup();

echo "<table class='tab_cadre'>";
echo "<tr><th>" . __('Rule type') . "</th></tr>";

foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
   $rulecollection = new $rulecollectionclass();
   if ($rulecollection->canList()) {
      if ($plug = isPluginItemType($rulecollectionclass)) {
         $title = sprintf(__('%1$s - %2$s'), Plugin::getInfo($plug['plugin'], 'name'),
                                             $rulecollection->getTitle());
      } else {
         $title = $rulecollection->getTitle();
      }
      echo "<tr class='tab_bg_1'><td class='center b'>";
      $ruleClassName = $rulecollection->getRuleClassName();
      echo "<a href='".$ruleClassName::getSearchURL()."'>";
      echo $title."</a></td></tr>";
   }
}

if (Session::haveRight("transfer", READ)
    && Session::isMultiEntitiesMode()) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='".$CFG_GLPI['root_doc']."/front/transfer.php'>".__('Transfer')."</a>";
   echo "</td></tr>";
}

if (Session::haveRight("config", READ)) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='".$CFG_GLPI['root_doc']."/front/blacklist.php'>".
        _n('Blacklist', 'Blacklists', 2)."</a>";
   echo "</td></tr>";
}

echo "</table>";

Html::footer();
