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

require_once(__DIR__ . '/../_check_webserver_config.php');

use Glpi\Form\AccessControl\FormAccessControl;

use function Safe\json_encode;

/**
 * Ajax endpoint to update an access control item.
 *
 * This endpoint is called once per possible access control stategies when
 * submitting the access control config page for a form.
 */

try {
    $access_control = new FormAccessControl();
    if (isset($_POST["update"])) {
        // Update access control policies
        foreach ($_POST['_access_control'] as $id => $input) {
            $input['id'] = $id;

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
} catch (Throwable $e) {
    // Log error
    global $PHPLOGGER;
    $PHPLOGGER->error(
        $e->getMessage() . ": " . json_encode($_POST),
        ['exception' => $e]
    );

    Session::addMessageAfterRedirect(
        __s('An unexpected error occurred'),
        false,
        ERROR
    );
}

// Redirect to previous page
Html::back();
