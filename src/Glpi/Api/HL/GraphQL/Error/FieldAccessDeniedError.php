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

namespace Glpi\Api\HL\GraphQL\Error;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use GraphQL\Language\Source;

class FieldAccessDeniedError extends Error
{
    public function __construct($nodes = null, ?Source $source = null, ?array $positions = null, ?array $path = null, ?\Throwable $previous = null, ?array $extensions = null, ?array $unaliasedPath = null)
    {
        $field_path = implode('.', $path ?? []);
        $message = "You do not have permission to view the field $field_path";
        parent::__construct($message, $nodes, $source, $positions, $path, $previous, $extensions, $unaliasedPath);
    }

    public function isClientSafe(): bool
    {
        return true;
    }
}
