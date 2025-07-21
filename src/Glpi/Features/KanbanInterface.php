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

namespace Glpi\Features;

interface KanbanInterface
{
    public static function getDataToDisplayOnKanban($ID, $criteria = []);

    public static function getKanbanColumns($ID, $column_field = null, $column_ids = [], $get_default = false);

    public static function showKanban($ID);

    public static function getAllForKanban($active = true, $current_id = -1);

    public static function getAllKanbanColumns($column_field = null, $column_ids = [], $get_default = false);

    public function canModifyGlobalState();

    public function forceGlobalState();

    public function prepareKanbanStateForUpdate($oldstate, $newstate, $users_id);

    public function canOrderKanbanCard($ID);

    public static function getKanbanPluginFilters($itemtype);

    public static function getGlobalKanbanUrl(bool $full = true);
    public function getKanbanUrlWithID(int $items_id, bool $full = true);
}
