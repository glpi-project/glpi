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

/**
 * @since 9.1
 */

use Glpi\Http\Response;

$AJAX_INCLUDE = 1;

include('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Mandatory parameter: tasktemplates_id
$tasktemplates_id = $_POST['tasktemplates_id'] ?? null;
if ($tasktemplates_id === null) {
    Response::sendError(400, "Missing or invalid parameter: 'tasktemplates_id'");
} else if ($tasktemplates_id == 0) {
   // Reset form
    echo json_encode([
        'content' => ""
    ]);
    die;
}

// Mandatory parameter: items_id
$parents_id = $_POST['items_id'] ?? 0;
if (!$parents_id) {
    Response::sendError(400, "Missing or invalid parameter: 'items_id'");
}

// Mandatory parameter: itemtype
$parents_itemtype = $_POST['itemtype'] ?? '';
if (empty($parents_itemtype) || !is_subclass_of($parents_itemtype, CommonITILObject::class)) {
    Response::sendError(400, "Missing or invalid parameter: 'itemtype'");
}

// Load task template
$template = new TaskTemplate();
if (!$template->getFromDB($tasktemplates_id)) {
    Response::sendError(400, "Unable to load template: $tasktemplates_id");
}

// Load parent item
$parent = new $parents_itemtype();
if (!$parent->getFromDB($parents_id)) {
    Response::sendError(400, "Unable to load parent item: $parents_itemtype $parents_id");
}

// Render template content using twig
$template->fields['content'] = $template->getRenderedContent($parent);

//load taskcategorie name (use to create OPTION dom)
//need when template is used and when GLPI preselected type if defined
$template->fields['taskcategories_name'] = "";
if ($template->fields['taskcategories_id']) {
    $entityRestrict = getEntitiesRestrictCriteria(getTableForItemType(TaskCategory::getType()), "", $parent->fields['entities_id'], true);

    $taskcategory = new TaskCategory();
    if (
        $taskcategory->getFromDBByCrit([
            "id" => $template->fields['taskcategories_id'],
        ] + $entityRestrict)
    ) {
        $template->fields['taskcategories_name'] = Dropdown::getDropdownName(
            getTableForItemType(TaskCategory::getType()),
            $template->fields['taskcategories_id'],
            0,
            true,
            false,
            //default value like "(id)" is the default behavior of GLPI when field 'name' is empty
            "(" . $template->fields['taskcategories_id'] . ")"
        );
    }
}


// Return json response with the template fields
echo json_encode($template->fields);
