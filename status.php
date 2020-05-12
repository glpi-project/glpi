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

use Glpi\System\Status\StatusChecker;

define('DO_NOT_CHECK_HTTP_REFERER', 1);
include ('./inc/includes.php');

// Force in normal mode
$_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

// Need to be used using :
// check_http -H servername -u /glpi/status.php -s GLPI_OK

$valid_response_types = ['text/plain', 'application/json'];
$fallback_response_type = 'text/plain';

if (!isset($_SERVER['HTTP_ACCEPT']) || !in_array($_SERVER['HTTP_ACCEPT'], $valid_response_types, true)) {
   $_SERVER['HTTP_ACCEPT'] = $fallback_response_type;
}
header('Content-type: ' . $_SERVER['HTTP_ACCEPT']);

if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
   echo json_encode(StatusChecker::getFullStatus(true, true));
} else {
   echo StatusChecker::getFullStatus(true, false);
}
