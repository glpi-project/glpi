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
use Glpi\Form\Tag\FormTagsManager;
use Glpi\Http\Response;

include('../../inc/includes.php');

// The user must be able to respond to forms.
Session::checkRight(Form::$rightname, UPDATE);

// Get mandatory form parameter
$form = Form::getById($_GET['form_id'] ?? null);
if (!$form) {
    Response::sendError(400, __('Form not found'));
}

// Get filter parameter
$filter = $_GET['filter'] ?? "";

// Get tags
$tag_manager = new FormTagsManager();
header('Content-Type: application/json');
echo json_encode($tag_manager->getTags($form, $filter));
