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

namespace Glpi\Toolbox;

/**
 * Input keys that are meant to be set only by the internal processes (rules engine, mail collector, SLA/OLA
 * escalations, ...) to bypass some rights or consistency checks of the items.
 *
 * They must never be accepted from the data sent by a client.
 */
final class InternalInputKeys
{
    public const KEYS = [
        '_rule_process',
        '_auto_import',
        '_auto_update',
        '_from_assignment',
        '_from_itilvalidation',
    ];

    /**
     * Remove the internal keys from the top level of the given input.
     */
    public static function remove(mixed $input): mixed
    {
        if (!is_array($input)) {
            return $input;
        }

        return array_diff_key($input, array_flip(self::KEYS));
    }
}
