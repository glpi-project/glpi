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

use Glpi\Exception\Http\AccessDeniedHttpException;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["my_items"]) && !empty($_POST["my_items"])) {
    $splitter = explode("_", $_POST["my_items"]);
    if (count($splitter) == 2) {
        $_POST["itemtype"] = $splitter[0];
        $_POST["items_id"] = $splitter[1];
    }
}

if (
    isset($_POST['itemtype'])
    && isset($_POST['items_id']) && ($_POST['items_id'] > 0)
) {
    // Security
    if (!($item = getItemForItemtype($_POST['itemtype'])) || !$item->can($_POST['items_id'], READ)) {
        throw new AccessDeniedHttpException();
    }

    $days   = 3;

    $ticket = new Ticket();
    $data   = $ticket->getActiveOrSolvedLastDaysForItem(
        $_POST['itemtype'],
        $_POST['items_id'],
        $days
    );

    $nb = count($data);
    $badge_helper = sprintf(
        _sn(
            '%s ticket in progress or recently solved on this item.',
            '%s tickets in progress or recently solved on this item.',
            $nb
        ),
        $nb
    );
    echo "<span class='badge badge-secondary' title='$badge_helper'>$nb</span>";

    if ($nb) {
        $content = '';
        foreach ($data as $title) {
            $content .= htmlescape($title) . '<br>';
        }
        echo '&nbsp;';
        Html::showToolTip($content);
    }
}
