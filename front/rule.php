<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

echo "<div class='row justify-content-evenly'>";
echo "<div class='card col-12 col-xxl-4'>";
echo "<div class='card-header'><h3>".__('Rule type')."</h3></div>";
echo "<div class='list-group list-group-flush'>";

foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
   $rulecollection = new $rulecollectionclass();
   if ($rulecollection->canList()) {
      if ($plug = isPluginItemType($rulecollectionclass)) {
         $title = sprintf(__('%1$s - %2$s'), Plugin::getInfo($plug['plugin'], 'name'),
                                             $rulecollection->getTitle());
      } else {
         $title = $rulecollection->getTitle();
      }
      $ruleClassName = $rulecollection->getRuleClassName();
      echo "<a class='list-group-item list-group-item-action' href='".$ruleClassName::getSearchURL()."'>";
      echo "<i class='fa-fw ".$ruleClassName::getIcon()." me-1'></i>";
      echo $title;
      echo "</a>";
   }
}

if (Session::haveRight("transfer", READ)
    && Session::isMultiEntitiesMode()) {
   echo "<a class='list-group-item list-group-item-action' href='".Transfer::getSearchURL()."'>";
   echo "<i class='fa-fw ".Transfer::getIcon()." me-1'></i>";
   echo __('Transfer');
   echo "</a>";
}

if (Session::haveRight("config", READ)) {
   echo "<a class='list-group-item list-group-item-action' href='".Blacklist::getSearchURL()."'>";
   echo "<i class='fa-fw ".Blacklist::getIcon()." me-1'></i>";
   echo _n('Blacklist', 'Blacklists', Session::getPluralNumber());
   echo "</a>";
}

echo "</div>"; // .list-group
echo "</div>"; // .card
echo "</div>"; // .row

Html::footer();
