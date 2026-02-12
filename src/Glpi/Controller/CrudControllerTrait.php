<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller;

use CommonDBTM;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use RuntimeException;

trait CrudControllerTrait
{
    /** @param array<mixed> $input */
    private function update(string $class, int $id, array $input): CommonDBTM
    {
        $item = getItemForItemtype($class);
        if (!$item || !$item->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $input['id'] = $id;
        if (!$item->can($id, UPDATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        if ($input === null || !$item->update($input)) {
            throw new RuntimeException("Failed to update item");
        }

        return $item;
    }

    private function delete(string $class, int $id): void
    {
        $item = getItemForItemtype($class);
        if (!$item || !$item->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $input = ['id' => $id];
        if (!$item->can($id, DELETE, $input)) {
            throw new AccessDeniedHttpException();
        }

        if ($input === null || !$item->delete($input)) {
            throw new RuntimeException("Failed to delete item");
        }
    }

    private function purge(string $class, int $id): void
    {
        $item = getItemForItemtype($class);
        if (!$item || !$item->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $input = ['id' => $id];
        if (!$item->can($id, PURGE, $input)) {
            throw new AccessDeniedHttpException();
        }

        if ($input === null || !$item->delete($input, force: true)) {
            throw new RuntimeException("Failed to purge item");
        }
    }
}
