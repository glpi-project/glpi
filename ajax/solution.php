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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\RichText\RichText;

use function Safe\json_encode;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

// Mandatory parameter: solutiontemplates_id
$solutiontemplates_id = $_POST['solutiontemplates_id'] ?? null;
if ($solutiontemplates_id === null) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'solutiontemplates_id'");
} elseif ($solutiontemplates_id == 0) {
    // Reset form
    echo json_encode([
        'content' => "",
    ]);
    return;
}

$parents_id = $_POST['items_id'] ?? 0;
$parents_itemtype = $_POST['itemtype'] ?? '';

// Load solution template
$template = new SolutionTemplate();
if (!$template->getFromDB($solutiontemplates_id)) {
    throw new BadRequestHttpException("Unable to load template: $solutiontemplates_id");
}

if ($parents_id > 0 && !empty($parents_itemtype) && is_a($parents_itemtype, CommonITILObject::class, true)) {
    // Load parent item
    $parent = new $parents_itemtype();
    if (!$parent->getFromDB($parents_id)) {
        throw new BadRequestHttpException("Unable to load parent item: $parents_itemtype $parents_id");
    }

    // Render template content using twig
    $template->fields['content'] = $template->getRenderedContent($parent);
} else {
    // We can't render the twig template at this state for some cases (e.g. massive
    // actions: we don't have one but multiple items so it net possible to parse the
    // values yet).

    $content = DropdownTranslation::getTranslatedValue(
        $template->getID(),
        $template->getType(),
        'content',
        $_SESSION['glpilanguage'],
        $template->fields['content']
    );
    $template->fields['content'] = RichText::getSafeHtml($content);
}

//load solutiontype name (use to create OPTION dom)
//need when template is used and when GLPI preselcted type if defined

$template->fields['solutiontypes_name'] = "";
if ($template->fields['solutiontypes_id']) {
    $entityRestrict = getEntitiesRestrictCriteria(
        getTableForItemType(SolutionType::getType()),
        "",
        $parent->fields['entities_id'] ?? 0,
        true
    );

    $solutiontype = new SolutionType();
    if (
        $solutiontype->getFromDBByCrit([
            "id" => $template->fields['solutiontypes_id'],
        ] + $entityRestrict)
    ) {
        $template->fields['solutiontypes_name'] = Dropdown::getDropdownName(
            getTableForItemType(SolutionType::getType()),
            $template->fields['solutiontypes_id'],
            false,
            true,
            false,
            //default value like "(id)" is the default behavior of GLPI when field 'name' is empty
            "(" . $template->fields['solutiontypes_id'] . ")"
        );
    }
}

// Return json response with the template fields
echo json_encode($template->fields);
