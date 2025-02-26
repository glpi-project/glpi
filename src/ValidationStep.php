<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class ValidationStep extends \CommonDropdown
{
    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Approval step', 'Approval steps', $nb);
    }

    /**
     * Default Validation steps data
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getDefaults(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Validation',
                'mininal_required_validation_percent' => 100,
                'is_default' => 1,
                'date_creation' => date('Y-m-d H:i:s'),
                'date_mod' => date('Y-m-d H:i:s'),
                'comment' => '',
            ],
        ];
    }
}
