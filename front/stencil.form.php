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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

if (isset($_POST['id'])) {
    $stencil = Stencil::getStencilFromID($_POST['id']);

    if (!$stencil) {
        throw new NotFoundHttpException('Stencil not found');
    }

    $stencil->check($_POST['id'], READ);

    if (isset($_POST['purge'])) {
        $stencil->check($_POST['id'], PURGE);
        $stencil->delete($_POST, true);
    }
} elseif (isset($_POST['itemtype'])) {
    // This code block retrieves an item based on the itemtype and items_id parameters.
    // The itemtype and items_id parameters are necessary because the Stencil class targets multiple objects of different types
    $item = getItemForItemtype($_POST['itemtype']);
    if (!$item || !$item->canView()) {
        throw new AccessDeniedHttpException();
    }

    if ($item->getFromDB($_POST['items_id'])) {
        $stencil = Stencil::getStencilFromItem($item);
        if (isset($_POST['add'])) {
            $stencil->check(-1, CREATE, $_POST);
            $stencil->add($_POST);
        }
    }
}

Html::back();
