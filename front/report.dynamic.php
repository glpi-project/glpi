<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

include('../inc/includes.php');

if (!isset($_GET['item_type']) || !is_string($_GET['item_type']) || !is_a($_GET['item_type'], CommonGLPI::class, true)) {
    return;
}

$itemtype = $_GET['item_type'];
if ($itemtype === 'AllAssets') {
    Session::checkCentralAccess();
} else {
    Session::checkValidSessionId();
    $item = new $itemtype();
    if (!$item->canView()) {
        Html::displayRightError();
    }
}

if (isset($_GET["display_type"])) {
    if ($_GET["display_type"] < 0) {
        $_GET["display_type"] = -$_GET["display_type"];
        $_GET["export_all"]   = 1;
    }

    switch ($itemtype) {
        case 'KnowbaseItem':
            KnowbaseItem::showList($_GET, $_GET["is_faq"]);
            break;

        case 'Stat':
            if (isset($_GET["item_type_param"])) {
                $params = Toolbox::decodeArrayFromInput($_GET["item_type_param"]);
                switch ($params["type"]) {
                    case "comp_champ":
                        $val = Stat::getItems(
                            $_GET["itemtype"],
                            $params["date1"],
                            $params["date2"],
                            $params["dropdown"]
                        );
                        Stat::showTable(
                            $_GET["itemtype"],
                            $params["type"],
                            $params["date1"],
                            $params["date2"],
                            $params["start"],
                            $val,
                            $params["dropdown"]
                        );
                        break;

                    case "device":
                        $val = Stat::getItems(
                            $_GET["itemtype"],
                            $params["date1"],
                            $params["date2"],
                            $params["dropdown"]
                        );
                        Stat::showTable(
                            $_GET["itemtype"],
                            $params["type"],
                            $params["date1"],
                            $params["date2"],
                            $params["start"],
                            $val,
                            $params["dropdown"]
                        );
                        break;

                    default:
                          $val2 = (isset($params['value2']) ? $params['value2'] : 0);
                          $val  = Stat::getItems(
                              $_GET["itemtype"],
                              $params["date1"],
                              $params["date2"],
                              $params["type"],
                              $val2
                          );
                         Stat::showTable(
                             $_GET["itemtype"],
                             $params["type"],
                             $params["date1"],
                             $params["date2"],
                             $params["start"],
                             $val,
                             $val2
                         );
                }
            } else if (isset($_GET["type"]) && ($_GET["type"] == "hardwares")) {
                Stat::showItems("", $_GET["date1"], $_GET["date2"], $_GET['start']);
            }
            break;

        default:
           // Plugin case
            if ($plug = isPluginItemType($itemtype)) {
                if (Plugin::doOneHook($plug['plugin'], 'dynamicReport', $_GET)) {
                    exit();
                }
            }
            $params = Search::manageParams($itemtype, $_GET);
            Search::showList($itemtype, $params);
    }
}
