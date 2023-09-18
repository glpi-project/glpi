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

use Glpi\Form\Form;

include('../../inc/includes.php');

// Only super admins for now - TODO add specific rights
Session::checkRight("config", UPDATE);

// Read parameters
$id = $_REQUEST['id'] ?? null;

if (isset($_POST["add"])) {
    // Create form
    $form = new Form();
    $form->check($id, CREATE);

    if ($form->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($form->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    // Update form
    $form = new Form();
    $form->check($id, UPDATE);
    $form->update($_POST);
    Html::back();
} elseif (isset($_POST["delete"])) {
    // Delete form
    $form = new Form();
    $form->check($id, DELETE);
    $form->delete($_POST);
    $form->redirectToList();
} elseif (isset($_POST["restore"])) {
    // Restore form
    $form = new Form();
    $form->check($id, DELETE);
    $form->restore($_POST);
    $form->redirectToList();
} elseif (isset($_POST["purge"])) {
    // Purge form
    $form = new Form();
    $form->check($id, PURGE);
    $form->delete($_POST, true);
    $form->redirectToList();
} else {
    // Show requested form
    Form::displayFullPageForItem($id, ['admin', Form::getType()], []);
}
