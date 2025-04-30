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

use Glpi\Exception\Http\NotFoundHttpException;

if (isset($_POST['id'])) {
    $stencil = Stencil::getStencilFromID($_POST['id']);

    if (!$stencil) {
        throw new NotFoundHttpException('Stencil not found');
    }

    $stencil->check($_POST['id'], READ);

    if (isset($_POST['update'])) {
        $stencil->check($_POST['id'], UPDATE);
        $stencil->update($_POST);
    } elseif (isset($_POST['add-new-zone'])) {
        $stencil->check($_POST['id'], UPDATE);
        $stencil->addNewZones($_POST);
    } elseif (isset($_POST['remove-zone'])) {
        $stencil->check($_POST['id'], UPDATE);
        $stencil->removeZones($_POST);
    } elseif (isset($_POST['reset-zone'])) {
        $stencil->check($_POST['id'], UPDATE);
        $stencil->resetZones($_POST);
    }
}
