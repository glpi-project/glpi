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

class Item_Kanban extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype';
    public static $items_id_1 = 'items_id';
    public static $itemtype_2 = 'User';
    public static $items_id_2 = 'users_id';
    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    /**
     * Save the state of a Kanban's columns for a specific item for the current user or globally.
     * @since 9.5.0
     * @param string $itemtype Type of the item.
     * @param int $items_id ID of the item.
     * @param array $state Array of Kanban column state data.
     * @return bool
     */
    public static function saveStateForItem($itemtype, $items_id, $state)
    {
        /** @var \DBmysql $DB */
        global $DB;

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $item->getFromDB($items_id);
        $force_global = false;
        if (method_exists($item, 'forceGlobalState')) {
            $force_global = $item->forceGlobalState();
        }

        $oldstate = self::loadStateForItem($itemtype, $items_id);
        $users_id = $force_global ? 0 : Session::getLoginUserID();
        if (method_exists($item, 'prepareKanbanStateForUpdate')) {
            $state = $item->prepareKanbanStateForUpdate($oldstate, $state, $users_id);
        }

        if ($state === null || $state === 'null' || $state === false) {
            // Save was probably denied in prepareKanbanStateForUpdate or an invalid state was given
            return false;
        }

        $common_input = [
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'users_id'  => $users_id,
            'state'     => json_encode($state, JSON_FORCE_OBJECT),
            'date_mod'  => $_SESSION['glpi_currenttime'],
        ];
        $criteria = [
            'users_id' => $users_id,
            'itemtype' => $itemtype,
            'items_id' => $items_id,
        ];
        if (countElementsInTable('glpi_items_kanbans', $criteria)) {
            $DB->update('glpi_items_kanbans', [
                'date_mod'  => $_SESSION['glpi_currenttime'],
            ] + $common_input, $criteria);
        } else {
            $DB->insert('glpi_items_kanbans', [
                'date_creation'   => $_SESSION['glpi_currenttime'],
            ] + $common_input);
        }
        return true;
    }

    /**
     * Load the state of a Kanban's columns for a specific item for the current user or globally.
     * @since 9.5.0
     * @param string $itemtype Type of the item.
     * @param int $items_id ID of the item.
     * @param string $timestamp Timestamp string of last check or null to always get the state.
     * @return array Array of Kanban column state data.
     *       Null is returned if $timestamp is specified, but no changes have been made to the state since then
     *       An empty array is returned if the state is not in the DB.
     */
    public static function loadStateForItem($itemtype, $items_id, $timestamp = null)
    {
        /** @var \DBmysql $DB */
        global $DB;

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $item->getFromDB($items_id);
        $force_global = false;
        if (method_exists($item, 'forceGlobalState')) {
            $force_global = $item->forceGlobalState();
        }

        $iterator = $DB->request([
            'SELECT' => ['date_mod', 'state'],
            'FROM'   => 'glpi_items_kanbans',
            'WHERE'  => [
                'users_id' => $force_global ? 0 : Session::getLoginUserID(),
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
        ]);

        if (count($iterator)) {
            $data = $iterator->current();
            if ($timestamp !== null) {
                if (strtotime($timestamp) < strtotime($data['date_mod'])) {
                    return json_decode($data['state'], true);
                } else {
                    // No changes since last check
                    return null;
                }
            }
            return json_decode($data['state'], true);
        } else {
            // State is not saved
            return [];
        }
    }

    public static function moveCard($itemtype, $items_id, $card, $column, $position)
    {
        $state = self::loadStateForItem($itemtype, $items_id);

        if ($position < 0) {
            $position = 0;
        }

        // Search for old location and remove card
        foreach ($state as $column_index => $col) {
            if (isset($col['cards'])) {
                foreach ($col['cards'] as $card_index => $card_id) {
                    if ($card_id === $card) {
                        unset($state[$column_index]['cards'][$card_index]);
                        // Re-index
                        $state[$column_index]['cards'] = array_values($state[$column_index]['cards']);
                    }
                }
            }
        }

        /** @var CommonDBTM $item */
        $item = new $itemtype();
        $item->getFromDB($items_id);
        $all_columns = [];
        if (method_exists($item, 'getAllKanbanColumns')) {
            $all_columns = $item->getAllKanbanColumns();
        }
        $new_column_index = array_keys(array_filter($state, function ($c, $k) use ($column) {
            return $c['column'] === $column;
        }, ARRAY_FILTER_USE_BOTH));
        if (count($new_column_index)) {
            $new_column_index = reset($new_column_index);
            if (isset($all_columns[$new_column_index])) {
                $drop_only = $all_columns[$new_column_index]['drop_only'] ?? false;
                if (isset($all_columns[$new_column_index]) && !$drop_only) {
                    array_splice($state[$new_column_index]['cards'], $position, 0, $card);
                }
            }
        }

        self::saveStateForItem($itemtype, $items_id, $state);
    }

    public static function getAllShownColumns($itemtype, $items_id)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        return array_column($state, 'column');
    }

    public static function showColumn($itemtype, $items_id, $column)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        $found = false;
        foreach ($state as $column_index => &$col) {
            if ($col['column'] === $column) {
                $col['visible'] = true;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $state[] = [
                'column' => $column,
                'visible' => true,
                'folded' => false,
                'cards' => [],
            ];
        }
        self::saveStateForItem($itemtype, $items_id, $state);
    }

    public static function hideColumn($itemtype, $items_id, $column)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        foreach ($state as $column_index => &$col) {
            if ($col['column'] === $column) {
                $col['visible'] = false;
                break;
            }
        }
        self::saveStateForItem($itemtype, $items_id, $state);
    }

    public static function collapseColumn($itemtype, $items_id, $column)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        foreach ($state as $column_index => &$col) {
            if ($col['column'] === $column) {
                $col['folded'] = true;
                break;
            }
        }
        self::saveStateForItem($itemtype, $items_id, $state);
    }

    public static function expandColumn($itemtype, $items_id, $column)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        foreach ($state as $column_index => &$col) {
            if ($col['column'] === $column) {
                $col['folded'] = false;
                break;
            }
        }
        self::saveStateForItem($itemtype, $items_id, $state);
    }

    public static function moveColumn($itemtype, $items_id, $column, $position)
    {
        $state = self::loadStateForItem($itemtype, $items_id);
        $existing_pos = array_search($column, array_column($state, 'column'));
        if ($existing_pos) {
            $col = $state[$existing_pos];
            unset($state[$existing_pos]);
            array_splice($state, $position, 0, [$col]);
        }
        self::saveStateForItem($itemtype, $items_id, $state);
    }
}
