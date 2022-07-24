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
use Glpi\Csv\LogCsvExport;
use Glpi\Http\Response;

include('../../inc/includes.php');

// Read params
$itemtype = $_GET['itemtype']   ?? null;
$id       = $_GET['id']         ?? null;
$filter   = $_GET['filter']     ?? [];

Session::checkRight(Log::$rightname, READ);

// Validate itemtype
if (!is_a($itemtype, CommonDBTM::class, true)) {
    Response::sendError(400, "Invalid itemtype", Response::CONTENT_TYPE_TEXT_PLAIN);
}

// Validate id
$item = $itemtype::getById($id);
if (!$item || !$item->canViewItem()) {
    Response::sendError(400, "No item found for given id", Response::CONTENT_TYPE_TEXT_PLAIN);
}

// Validate filter
if (!is_array($filter)) {
    Response::sendError(400, "Invalid filter", Response::CONTENT_TYPE_TEXT_PLAIN);
}

CsvResponse::output(new LogCsvExport($item, $filter));
