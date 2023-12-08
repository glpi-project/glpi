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

if (($_GET['id'] ?? 0) == 0) {
    // Clear stale drafts
    // TODO: move to a dedicated cron task
    $DB->delete(Form::getTable(), [
        'is_draft'      => true,
        'date_creation' => ['<=', date('Y-m-d', strtotime('-7 days'))]
    ]);

    // Add as draft and redirect to the creation page
    // This allow to seamlessly skip the creation step and get straight to the
    // edit page which will contains more fields
    $form = new Form();
    $id = $form->add([
        'name'     => __("Untitled form"),
        "header"   => __("Form description..."),
        'is_draft' => true,
    ]);
    Html::redirect($form->getLinkURL());
    // Code stop here due to exit() in the Html::redirect() method
} else {
    // Show requested form
    Form::displayFullPageForItem($id, ['admin', Form::getType()], []);
}
