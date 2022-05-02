<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Glpi\Csv\CsvResponse;
use Glpi\Csv\ImpactCsvExport;

include('../inc/includes.php');

$itemtype = $_GET['itemtype'] ?? '';
$items_id = $_GET['items_id'] ?? '';

// Check for mandatory params
if (empty($itemtype) || empty($items_id)) {
    http_response_code(400);
    die();
}

// Check right
Session::checkRight($itemtype::$rightname, READ);

// Load item
$item = new $itemtype();
$item->getFromDB($items_id);

CsvResponse::output(new ImpactCsvExport($item));
