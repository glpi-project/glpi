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

use Glpi\Form\Form;
use Glpi\Form\Renderer\FormRenderer;
use Glpi\Http\Response;

include('../../inc/includes.php');

/**
 * AJAX endpoint used to display or preview a form.
 */

// For now form rendering is only used to preview a form by a technician
Session::checkRight(Form::$rightname, READ);

// Mandatory parameter: id of the form to render
$id = $_GET['id'] ?? 0;
if (!$id) {
    Response::sendError(400, __("Missing or invalid form's id"));
}

// Fetch form
$form = Form::getById($id);
if (!$form) {
    Response::sendError(404, __("Form not found"));
}

// TODO: if displaying a form, check form access configuration (not yet implemented)
// TODO: if previewing a form, check view rights on forms

// Render the requested form
$form_renderer = new FormRenderer();
echo $form_renderer->render($form);
