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

/**
 * @since 9.5
 */

use Glpi\Exception\Http\BadRequestHttpException;

use function Safe\json_encode;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

// Mandatory parameter: itilfollowuptemplates_id
$itilfollowuptemplates_id = $_POST['itilfollowuptemplates_id'] ?? null;
if ($itilfollowuptemplates_id === null) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'itilfollowuptemplates_id'");
} elseif ($itilfollowuptemplates_id == 0) {
    // Reset form
    echo json_encode([
        'content' => "",
    ]);
    return;
}

// Mandatory parameter: items_id
$parents_id = $_POST['items_id'] ?? 0;
if (!$parents_id) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'items_id'");
}

// Mandatory parameter: itemtype
$parents_itemtype = $_POST['itemtype'] ?? '';
if (empty($parents_itemtype) || !is_subclass_of($parents_itemtype, CommonITILObject::class)) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'itemtype'");
}

// Load followup template
$template = new ITILFollowupTemplate();
if (!$template->getFromDB($itilfollowuptemplates_id)) {
    throw new BadRequestHttpException("Unable to load template: $itilfollowuptemplates_id");
}

// Load parent item
$parent = new $parents_itemtype();
if (!$parent->getFromDB($parents_id)) {
    throw new BadRequestHttpException("Unable to load parent item: $parents_itemtype $parents_id");
}

// Render template content using twig
$template->fields['content'] = $template->getRenderedContent($parent);

//load requesttypes name (use to create OPTION dom)
//need when template is used and when GLPI preselected type if defined
$template->fields['requesttypes_name'] = "";
if ($template->fields['requesttypes_id']) {
    $requesttype = new RequestType();
    if (
        $requesttype->getFromDBByCrit([
            "id" => $template->fields['requesttypes_id'],
        ])
    ) {
        $template->fields['requesttypes_name'] = Dropdown::getDropdownName(
            getTableForItemType(RequestType::getType()),
            $template->fields['requesttypes_id'],
            false,
            true,
            false,
            //default value like "(id)" is the default behavior of GLPI when field 'name' is empty
            "(" . $template->fields['requesttypes_id'] . ")"
        );
    }
}

if (($template->fields['pendingreasons_id'] ?? 0) > 0) {
    $pendingReason = new PendingReason();
    if ($pendingReason->getFromDB($template->fields['pendingreasons_id'])) {
        $template->fields = array_merge($template->fields, [
            'pendingreasons_name'         => $pendingReason->fields['name'],
            'followup_frequency'          => $pendingReason->fields['followup_frequency'],
            'followups_before_resolution' => $pendingReason->fields['followups_before_resolution'],
        ]);
    }
}

// Return json response with the template fields
echo json_encode($template->fields);
