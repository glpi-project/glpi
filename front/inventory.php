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

use Glpi\Inventory\Conf;
use Glpi\Inventory\Request;

$SECURITY_STRATEGY = 'no_check'; // allow anonymous requests from inventory agent

if (!defined('GLPI_ROOT')) {
    include(__DIR__ . '/../inc/includes.php');
}

$conf = new Conf();
if ($conf->enabled_inventory != 1) {
    http_response_code(403);
    die("Inventory is disabled");
}

$inventory_request = new Request();
if ($inventory_request->inError() && $inventory_request->getHttpResponseCode() == 415) {
    http_response_code($inventory_request->getHttpResponseCode());
    die("Unsupported compression");
}
$inventory_request->handleHeaders();

$refused = new RefusedEquipment();

$handle = true;
$contents = '';
if (isset($_GET['refused'])) {
    Session::checkRight("config", READ);
    if ($refused->getFromDB($_GET['refused']) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
        $contents = file_get_contents($inventory_file);
    } else {
        trigger_error(
            sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $_GET['refused']),
            E_USER_WARNING
        );
    }
} elseif (!isCommandLine() && $_SERVER['REQUEST_METHOD'] != 'POST') {
    if (isset($_GET['action']) && $_GET['action'] == 'getConfig') {
        /**
         * Even if Fusion protocol is not supported for getConfig requests, they
         * should be handled and answered with a json content type
         */
        $inventory_request->handleContentType('application/json');
        $inventory_request->addError('Protocol not supported', 400);
    } else {
        // Method not allowed answer without content
        $inventory_request->addError(null, 405);
    }
    $handle = false;
} else {
    if (isCommandLine()) {
        $f = fopen('php://stdin', 'r');
        $contents = '';
        while ($line = fgets($f)) {
            $contents .= $line;
        }
        fclose($f);
    } else {
        $contents = file_get_contents("php://input");
    }
}

if ($handle === true) {
    try {
        $inventory_request->handleRequest($contents);
    } catch (\Throwable $e) {
        $inventory_request->addError($e->getMessage());
    }
}

$inventory_request->handleMessages();

if (isset($_GET['refused'])) {
    $redirect_url = $refused->handleInventoryRequest($inventory_request);
    Html::redirect($redirect_url);
} else {
    if (isCommandLine()) {
        exit(0);
    }
    $headers = $inventory_request->getHeaders(true);
    http_response_code($inventory_request->getHttpResponseCode());
    foreach ($headers as $key => $value) {
        header(sprintf('%1$s: %2$s', $key, $value));
    }
    echo $inventory_request->getResponse();
}
