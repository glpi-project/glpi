<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use Glpi\Http\Response;
use Glpi\Plugin\Hooks;
use Plugin;

/**
 * Trait Kanban.
 * @since 9.5.0
 */
trait Kanban
{
    /**
     * Get all data needed to display a Kanban for the item with the specified ID.
     * This function does not format the data for viewing in the kanban.
     * @since 9.5.0
     * @param int $ID ID of the item
     * @param array $criteria Array of criteria to restrict the data returned.
     *       For example, it can restrict based on a specific Kanban column field to only get data for a specific column.
     * @return array Array of the data.
     *       This can be in any format as long as the getKanbanColumns function in this class can interpret it.
     **/
    abstract public static function getDataToDisplayOnKanban($ID, $criteria = []);

    /**
     * Get Kanban columns data for the specified item to pass to the Kanban JS component.
     * @since 9.5.0
     * @param int $ID ID of the item
     * @param string $column_field The field used to represent columns (Ex: projectstates_id).
     *       If no field is specified, the default columns are returned.
     * @param array $column_ids Array of Kanban column IDs that should be returned.
     *       By default, this array is empty which signifies that all columns should be retuned.
     * @param bool $get_default If true, the default columns are returned in addition to the requested ones in $column_ids.
     * @return array Array of constructed columns data for the Kanban
     **/
    abstract public static function getKanbanColumns($ID, $column_field = null, $column_ids = [], $get_default = false);

    /**
     * Show Kanban for a single item, or a global view for the itemtype.
     * @since 9.5.0
     * @param int $ID ID of the item or -1 for a global view.
     * @return void|bool
     **/
    abstract public static function showKanban($ID);

    /**
     * Get a list of all items to be included in the 'switch board' dropdown.
     * @since 9.5.0
     * @param bool $active True if only open/active items should be returned.
     * @param integer $current_id ID of the currently viewed Kanban.
     *       This is used to ensure the current Kanban is always in the list regardless of if it is active or not.
     * @return array Array of items that can have a Kanban view.
     *       Array format must be item_id => item_name.
     */
    abstract public static function getAllForKanban($active = true, $current_id = -1);

    /**
     * Get a list of all valid columns (without items) for the column based on the specified column field.
     * @since 9.5.0
     * @param string $column_field The field used to represent columns (Ex: projectstates_id).
     *       If no field is specified, all columns are returned.
     * @param array $column_ids Array of column IDs to limit the result. These IDs are values of the column_field in the DB.
     * @return array Array of columns in the format:
     *       column_field => [id => [name, header_color, etc]]
     */
    abstract public static function getAllKanbanColumns($column_field = null, $column_ids = [], $get_default = false);

    /**
     * Check if the current user can modify the global Kanban state.
     * @since 9.5.0
     * @return bool
     */
    public function canModifyGlobalState()
    {
        return false;
    }

    /**
     * Force the current user to use the global state when loading and saving (if they are allowed).
     * @since 9.5.0
     * @return bool
     * @see Kanban::canModifyGlobalState()
     */
    public function forceGlobalState()
    {
        return false;
    }

    /**
     * Modify the state before it gets saved to the DB.
     * You can deny the save completely by returning null or false.
     * @param array $oldstate The state currently in the DB.
     * @param array $newstate The new state to be saved.
     * @param int $users_id The ID of the user this state is for. If 0, it is the global/default state.
     * @return mixed The modified state or false/null to deny the save.
     */
    public function prepareKanbanStateForUpdate($oldstate, $newstate, $users_id)
    {
        return $newstate;
    }

    /**
     * Check if the current user can move Kanban cards inside the same column.
     * This is usually reserved for managers since the order can relate to priority.
     * @param integer $ID Item's ID
     * @return bool
     */
    public function canOrderKanbanCard($ID)
    {
        return true;
    }

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

    public static function getGlobalKanbanUrl(bool $full = true): string
    {
        if (method_exists(static::class, 'getFormUrl')) {
            return static::getFormURL($full) . '?showglobalkanban=1';
        }
        return '';
    }

    public function getKanbanUrlWithID(int $items_id, bool $full = true): string
    {
        $tabs = $this->defineTabs();
        $tab_id = array_search(__('Kanban'), $tabs);
        if (false === $tab_id || is_null($tab_id)) {
            Response::sendError(400, "Itemtype does not have a Kanban tab!", Response::CONTENT_TYPE_TEXT_HTML);
        }
        return static::getFormURLWithID($items_id, $full) . "&forcetab={$tab_id}";
    }
}
