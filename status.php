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

use Glpi\System\Status\StatusChecker;

include('./inc/includes.php');

// Force in normal mode
$_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

// Need to be used using :
// check_http -H servername -u /glpi/status.php -s GLPI_OK

$valid_response_types = ['text/plain', 'application/json'];
$fallback_response_type = 'text/plain';

if (!isset($_SERVER['HTTP_ACCEPT']) || !in_array($_SERVER['HTTP_ACCEPT'], $valid_response_types, true)) {
    $_SERVER['HTTP_ACCEPT'] = $fallback_response_type;
}

$format = $_SERVER['HTTP_ACCEPT'];
if (isset($_REQUEST['format'])) {
    switch ($_REQUEST['format']) {
        case 'json':
            $format = 'application/json';
            break;
        case 'plain':
            $format = 'text/plain';
            break;
    }
}

if ($format === 'text/plain') {
    Toolbox::deprecated('Plain-text status output is deprecated please use the JSON format instead by specifically setting the Accept header to "application/json". In the future, JSON output will be the default.');
}
header('Content-type: ' . $format);

if ($format === 'application/json') {
    echo json_encode(StatusChecker::getServiceStatus($_REQUEST['service'] ?? null, true, true));
} else {
    echo StatusChecker::getServiceStatus($_REQUEST['service'] ?? null, true, false);
}
