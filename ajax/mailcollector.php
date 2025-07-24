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

use Glpi\Exception\Http\AccessDeniedHttpException;

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("config", READ);

$mailcollector = new MailCollector();

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case "getFoldersList":
            // Load config if already exists
            // Necessary if password is not updated
            if (array_key_exists('id', $_REQUEST)) {
                $mailcollector->getFromDB($_REQUEST['id']);
            }

            // Update fields with input values
            $input = $_REQUEST;

            if (isset($input["passwd"])) {
                if (empty($input["passwd"])) {
                    unset($input["passwd"]);
                } else {
                    $input["passwd"] = (new GLPIKey())->encrypt($input["passwd"]);
                }
            }

            if (!empty($input['mail_server'])) {
                $input["host"] = Toolbox::constructMailServerConfig($input);
                if (!isset($input['passwd'])) {
                    $exception = new AccessDeniedHttpException();
                    $exception->setMessageToDisplay(__('Password is required to list mail folders.'));
                    throw $exception;
                }
            }

            if (!isset($input['errors'])) {
                $input['errors'] = 0;
            }

            $mailcollector->fields = array_merge($mailcollector->fields, $input);
            $mailcollector->displayFoldersList($_REQUEST['input_id']);
            break;
    }
}
