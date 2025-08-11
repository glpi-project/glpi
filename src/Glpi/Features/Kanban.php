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

namespace Glpi\Features;

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Plugin\Hooks;

/**
 * Trait Kanban.
 * @since 9.5.0
 */
trait Kanban
{
    /** @see KanbanInterface::canModifyGlobalState() */
    public function canModifyGlobalState()
    {
        return false;
    }

    /** @see KanbanInterface::forceGlobalState() */
    public function forceGlobalState()
    {
        return false;
    }

    /** @see KanbanInterface::prepareKanbanStateForUpdate() */
    public function prepareKanbanStateForUpdate($oldstate, $newstate, $users_id)
    {
        return $newstate;
    }

    /** @see KanbanInterface::canOrderKanbanCard() */
    public function canOrderKanbanCard($ID)
    {
        return true;
    }

    /** @see KanbanInterface::getKanbanPluginFilters() */
    public static function getKanbanPluginFilters($itemtype)
    {
        global $PLUGIN_HOOKS;
        $filters = [];

        if (isset($PLUGIN_HOOKS[Hooks::KANBAN_FILTERS])) {
            foreach ($PLUGIN_HOOKS[Hooks::KANBAN_FILTERS] as $plugin => $itemtype_filters) {
                $filters = array_merge($filters, $itemtype_filters[$itemtype] ?? []);
            }
        }
        return $filters;
    }

    /** @see KanbanInterface::getGlobalKanbanUrl() */
    public static function getGlobalKanbanUrl(bool $full = true): string
    {
        return static::getFormURL($full) . '?showglobalkanban=1';
    }

    /** @see KanbanInterface::getKanbanUrlWithID() */
    public function getKanbanUrlWithID(int $items_id, bool $full = true): string
    {
        $tabs = $this->defineTabs();
        $tab_id = null;
        // search each value for one that contains "Kanban"
        foreach ($tabs as $id => $tab) {
            if (str_contains($tab, __('Kanban'))) {
                $tab_id = $id;
                break;
            }
        }
        if (false === $tab_id || is_null($tab_id)) {
            throw new BadRequestHttpException("Itemtype does not have a Kanban tab!");
        }
        return static::getFormURLWithID($items_id, $full) . "&forcetab={$tab_id}";
    }
}
