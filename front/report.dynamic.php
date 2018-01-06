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

if (isset($_GET["item_type"]) && isset($_GET["display_type"])) {
   if ($_GET["display_type"] < 0) {
      $_GET["display_type"] = -$_GET["display_type"];
      $_GET["export_all"]   = 1;
   }

   switch ($_GET["item_type"]) {
      case 'KnowbaseItem' :
         KnowbaseItem::showList($_GET, $_GET["is_faq"]);
         break;

      case 'Stat' :
         if (isset($_GET["item_type_param"])) {
            $params = Toolbox::decodeArrayFromInput($_GET["item_type_param"]);
            switch ($params["type"]) {
               case "comp_champ" :
                  $val = Stat::getItems($_GET["itemtype"], $params["date1"], $params["date2"],
                                        $params["dropdown"]);
                  Stat::showTable($_GET["itemtype"], $params["type"], $params["date1"],
                                  $params["date2"], $params["start"], $val, $params["dropdown"]);
                  break;

               case "device" :
                  $val = Stat::getItems($_GET["itemtype"], $params["date1"], $params["date2"],
                                        $params["dropdown"]);
                  Stat::showTable($_GET["itemtype"], $params["type"], $params["date1"],
                                  $params["date2"], $params["start"], $val, $params["dropdown"]);
                  break;

               default :
                  $val2 = (isset($params['value2']) ? $params['value2'] : 0);
                  $val  = Stat::getItems($_GET["itemtype"], $params["date1"], $params["date2"],
                                         $params["type"], $val2);
                  Stat::showTable($_GET["itemtype"], $params["type"], $params["date1"],
                                  $params["date2"], $params["start"], $val, $val2);
            }
         } else if (isset($_GET["type"]) && ($_GET["type"] == "hardwares")) {
            Stat::showItems("", $_GET["date1"], $_GET["date2"], $_GET['start']);
         }
         break;

      default :
         // Plugin case
         if ($plug = isPluginItemType($_GET["item_type"])) {
            if (Plugin::doOneHook($plug['plugin'], 'dynamicReport', $_GET)) {
               exit();
            }
         }
         $params = Search::manageParams($_GET["item_type"], $_GET);
         Search::showList($_GET["item_type"], $params);
   }
}
