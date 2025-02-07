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
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\RichText\RichText;

/*
 * Ajax tooltip endpoint for CommonITILObjects
 */

// Read parameters
$itemtype = $_GET['itemtype'] ?? null;
$items_id = $_GET['items_id'] ?? null;

// Validate mandatory parameters
if (is_null($itemtype) || is_null($items_id)) {
    throw new BadRequestHttpException("Missing required parameters");
}

// Validate itemtype (only CommonITILObject allowed for now)
if (!is_a($itemtype, CommonITILObject::class, true)) {
    throw new BadRequestHttpException("Invalid itemtype");
}
$item = new $itemtype();

// Validate item
if (
    !$item->getFromDB($items_id)
    || !$item->canViewItem()
    || !$item->isField('content')
) {
    throw new NotFoundHttpException("Item not found");
}

// Display content
header('Content-type: text/html; charset=UTF-8');
echo RichText::getEnhancedHtml($item->fields['content'], [
    'images_gallery' => false, // Don't show photoswipe gallery
]);
