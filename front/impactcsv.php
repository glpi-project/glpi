<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use Glpi\Csv\CsvResponse;
use Glpi\Csv\ImpactCsvExport;

include ('../inc/includes.php');

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
