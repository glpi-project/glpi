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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Http\Response;

include('../../inc/includes.php');

/**
 * AJAX endpoint used to submit answers for a given form.
 */

// Validate forms_forms_id parameter
$forms_id = $_POST['forms_id'] ?? 0;
if (!$forms_id) {
    Response::sendError(400, __('Missing form id'));
}

// Load form
$form = Form::getById($forms_id);
if (!$form) {
    Response::sendError(404, __('Form not found'));
}

// Validate answers parameter
$answers = $_POST['answers'] ?? [];
if (!is_array($answers) || empty($answers)) {
    Response::sendError(400, __('Invalid answers'));
}

// Try to save answers
$handler = new AnswersHandler();
$answers_set = $handler->saveAnswers($form, $answers, Session::getLoginUserID());
if (!$answers_set) {
    Response::sendError(500, __('Failed to save answers'));
}

// Success response
$response = new Response(
    200,
    ['Content-Type' => 'application/json'],
    json_encode([
        'link_to_created_item' => $answers_set->getLink(),
    ]),
);
$response->send();
