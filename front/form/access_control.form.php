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

include('../../inc/includes.php');

use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Form;

/**
 * Ajax endpoint to update an access control item.
 *
 * This endpoint is called once per possible access control stategies when
 * submitting the access control config page for a form.
 */

try {
    $form = new Form();
    $access_control = new FormAccessControl();

    if (isset($_POST["update"])) {
        // ID is mandatory
        $id = $_POST['id'] ?? null;
        if ($id === null) {
            // Invalid request
            throw new InvalidArgumentException("Missing id");
        }

        // Update main form
        $form->check($id, UPDATE, $_POST);
        if (!$form->update($_POST, true)) {
            throw new RuntimeException(
                "Failed to update form access controls configuration"
            );
        }

        // Update access control policies
        foreach ($access_control->splitEncodedInputs($_POST) as $input) {
            $id = $input['id'] ?? 0;

            $access_control->check($id, UPDATE, $input);
            $access_control->getFromDB($id);
            $input['_config'] = $access_control->createConfigFromUserInput($input);
            if (!$access_control->update($input, true)) {
                throw new RuntimeException(
                    "Failed to update access control item"
                );
            }
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

    Session::addMessageAfterRedirect(
        __('An unexpected error occured.'),
        false,
        ERROR
    );
} finally {
    // Redirect to previous page
    Html::back();
}
