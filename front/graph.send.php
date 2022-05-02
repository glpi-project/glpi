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
use Glpi\Csv\StatCsvExport;
use Glpi\Http\Response;
use Glpi\Stat\StatData;

include('../inc/includes.php');

// Check rights
Session::checkRight("statistic", READ);

// Read params
$statdata_itemtype = $_UGET['statdata_itemtype'] ?? null;

// Validate stats itemtype
if (!is_a($statdata_itemtype, StatData::class, true)) {
    Response::sendError(400, "Invalid stats itemtype", Response::CONTENT_TYPE_TEXT_PLAIN);
}

// Get data and output csv
$graph_data = new $statdata_itemtype($_GET);
CsvResponse::output(
    new StatCsvExport($graph_data->getSeries(), $graph_data->getOptions())
);
