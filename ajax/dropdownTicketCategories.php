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

if (strpos($_SERVER['PHP_SELF'], "dropdownTicketCategories.php")) {
    include('../inc/includes.php');
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

$opt = ['entity' => $_POST["entity_restrict"]];
$condition  = [];

if (Session::getCurrentInterface() == "helpdesk") {
    $condition['is_helpdeskvisible'] = 1;
}

$currentcateg = new ITILCategory();
$currentcateg->getFromDB($_POST['value']);

if ($_POST["type"]) {
    switch ($_POST['type']) {
        case Ticket::INCIDENT_TYPE:
            $condition['is_incident'] = 1;
            if ($currentcateg->getField('is_incident') == 1) {
                $opt['value'] = $_POST['value'];
            }
            break;

        case Ticket::DEMAND_TYPE:
            $condition['is_request'] = 1;
            if ($currentcateg->getField('is_request') == 1) {
                $opt['value'] = $_POST['value'];
            }
            break;
    }
}

$opt['condition'] = $condition;
$opt['width']     = '100%';
ITILCategory::dropdown($opt);
