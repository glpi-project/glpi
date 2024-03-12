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

include('../../../inc/includes.php');

use Glpi\Form\Destination\FormDestination;

try {
    $destination = new FormDestination();

    if (isset($_POST["add"])) {
        // Right check
        $destination->check(-1, CREATE, $_POST);

        // Create destination item
        if (!$destination->add($_POST)) {
            throw new RuntimeException("Failed to create destination item");
        }
    } elseif (isset($_POST["purge"])) {
        // ID is mandatory
        $id = $_POST['id'] ?? null;
        if ($id === null) {
            // Invalid request
            throw new InvalidArgumentException("Missing id");
        }

        // Right check
        $destination->check($id, PURGE, $_POST);

        // Delete destination item
        if (!$destination->delete($_POST, true)) {
            throw new RuntimeException("Failed to create destination item");
        }
    } else {
        // Unknown request
        throw new InvalidArgumentException("Unknown action");
    }
} catch (\Throwable $e) {
    // Log error
    trigger_error(
        // Insert POST data into logs to ease debugging
        $e->getMessage() . ": " . json_encode($_POST),
        E_USER_WARNING
    );

    Session::addMessageAfterRedirect(__('Unexpected error'), false, ERROR);
} finally {
    // Stop script and return to previous page
    // Must always be run to prevent users being stuck on a blank page
    Html::back();
}
