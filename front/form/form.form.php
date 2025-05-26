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

use Glpi\Form\Form;

// Read parameters
$id = $_REQUEST['id'] ?? null;

if (($_REQUEST['id'] ?? 0) == 0) {
    Session::checkRight(Form::$rightname, CREATE);

    // Add as draft and redirect to the creation page
    // This allow to seamlessly skip the creation step and get straight to the
    // edit page which will contains more fields
    $form = new Form();
    $id = $form->add([
        'name'         => __("Untitled form"),
        'entities_id'  => $_SESSION['glpiactive_entity'],
        'is_recursive' => true,
        'is_draft'     => true,
    ]);
    Session::setActiveTab(Form::class, Form::class . '$main');
    Html::redirect($form->getLinkURL());
} elseif (isset($_POST['update'])) {
    $id = $_POST['id'] ?? 0;

    $form = new Form();
    $form->getFromDB($id);
    $form->check($id, UPDATE);
    $form->update($_POST);

    Html::redirect($form->getLinkURL());
} else {
    // Show requested form
    Session::checkRight(Form::$rightname, READ);
    Form::displayFullPageForItem($id, ['admin', Form::getType()], []);
}
