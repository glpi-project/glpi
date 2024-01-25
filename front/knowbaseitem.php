<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Toolbox\Sanitizer;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

if (!Session::haveRightsOr('knowbase', [READ, KnowbaseItem::READFAQ])) {
    Session::redirectIfNotLoggedIn();
    Html::displayRightError();
}
if (isset($_GET["id"])) {
    Html::redirect(KnowbaseItem::getFormURLWithID($_GET["id"]));
}

Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "knowbaseitem");

// Clean for search
$_GET = Sanitizer::dbUnescapeRecursive($_GET);

// Search a solution
if (
    !isset($_GET["contains"])
    && isset($_GET["item_itemtype"])
    && isset($_GET["item_items_id"])
) {
    if (in_array($_GET["item_itemtype"], $CFG_GLPI['kb_types']) && $item = getItemForItemtype($_GET["item_itemtype"])) {
        if ($item->can($_GET["item_items_id"], READ)) {
            $_GET["contains"] = $item->getField('name');
        }
    }
}

// Manage forcetab : non standard system (file name <> class name)
if (isset($_GET['forcetab'])) {
    Session::setActiveTab('Knowbase', $_GET['forcetab']);
    unset($_GET['forcetab']);
}

$kb = new Knowbase();
$kb->display($_GET);

Html::footer();
