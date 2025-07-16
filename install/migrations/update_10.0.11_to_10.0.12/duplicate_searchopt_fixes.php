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
/**
 * @var DBmysql $DB
 * @var Migration $migration
 */
use function Safe\json_decode;

$iterator = $DB->request(['FROM' => 'glpi_configs', 'WHERE' => ['name' => 'lock_use_lock_item']]);
$lock_use_lock_item = $iterator->current()['value'] ?? false;

if ($lock_use_lock_item) {
    $iterator = $DB->request(['FROM' => 'glpi_configs', 'WHERE' => ['name' => 'lock_item_list']]);
    $lock_item_list = $iterator->current()['value'] ?? '';
    $lock_item_list = json_decode($lock_item_list);

    if (is_array($lock_item_list)) {
        foreach ($lock_item_list as $itemtype) {
            $migration->changeSearchOption($itemtype, 205, 207);
            $migration->changeSearchOption($itemtype, 206, 208);
        }
    }
}
