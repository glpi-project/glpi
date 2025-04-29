<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

Session::checkCentralAccess();

if (isset($_GET["itemtype"])) {
    $itemtype = $_GET['itemtype'];
    $link     = $itemtype::getFormURL();

    // Get right sector
    $sector   = 'assets';

    //Get sectors from the menu
    $menu     = Html::getMenuInfos();

    //Try to find to which sector the itemtype belongs
    foreach ($menu as $menusector => $infos) {
        if (isset($infos['types']) && in_array($itemtype, $infos['types'])) {
            $sector = $menusector;
            break;
        }
    }

    Html::header(__('Manage templates...'), $_SERVER['PHP_SELF'], $sector, $itemtype);

    CommonDBTM::listTemplates($itemtype, $link, $_GET["add"]);

    Html::footer();
}
