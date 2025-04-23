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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;

Session::checkRight("software", UPDATE);

if (
    isset($_POST["add"])
    && (!isset($_POST['itemtype']) || !isset($_POST['items_id']) || $_POST['items_id'] <= 0)
) {
    $message = sprintf(
        __('Mandatory fields are not filled. Please correct: %s'),
        _n('Item', 'Items', 1)
    );
    Session::addMessageAfterRedirect(htmlescape($message), false, ERROR);
    Html::back();
}

if (isset($_POST['itemtype']) && $_POST['itemtype'] == 'User') {
    $isl = new SoftwareLicense_User();
    // convert form data to match the SoftwareLicense_User case
    $_POST['users_id'] = $_POST['items_id'];
    unset($_POST['itemtype'], $_POST['items_id']);
} else {
    $isl = new Item_SoftwareLicense();
}

if (isset($_POST["add"])) {
    if ($_POST['softwarelicenses_id'] > 0) {
        if ($isl->add($_POST)) {
            Event::log(
                $_POST['softwarelicenses_id'],
                "softwarelicense",
                4,
                "inventory",
                //TRANS: %s is the user login
                sprintf(__('%s associates an item and a license'), $_SESSION["glpiname"])
            );
        }
    }
    Html::back();
}

throw new BadRequestHttpException();
