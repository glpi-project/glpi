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

namespace Glpi\Search;

use CommonDBTM;

use function Safe\json_encode;

/**
 * Helper trait to ease interaction with filters
 */
trait FilterableTrait
{
    public function itemMatchFilter(CommonDBTM $item): bool
    {
        $filter = CriteriaFilter::getForItem($this);

        // No filter defined
        if (!$filter) {
            return true;
        }

        // The ID is not always search option 2
        $opts = SearchOption::getOptionsForItemtype($item::getType());
        $item_table = $item::getTable();
        $id_field = $item::getIndexName();
        $id_opt_num = null;
        foreach ($opts as $opt_num => $opt) {
            if (isset($opt['field']) && $opt['field'] === $id_field && $opt['table'] === $item_table) {
                $id_opt_num = $opt_num;
                break;
            }
        }

        if ($id_opt_num === null) {
            trigger_error("Could not find {$id_field} option for itemtype {$item::getType()}. Cannot use FilterableTrait on this itemtype.", E_USER_WARNING);
            return false;
        }

        // Intersect current item's id with the filter
        $criteria = [
            [
                'link' => 'AND',
                'criteria' => $filter->fields['search_criteria'],
            ],
            [
                'link' => 'AND',
                // Id field
                'field' => $id_opt_num,
                // Search engine seems to expect "contains" here, even if the
                // real search will be done with "equals
                'searchtype' => "contains",
                'value' => $item->fields[$id_field],
            ],
        ];

        // Execute search
        $data = SearchEngine::getData($item::getType(), [
            'criteria' => $criteria,
        ]);

        return $data['data']['totalcount'] > 0;
    }

    public function saveFilter(
        array $search_criteria
    ): bool {
        $search_itemtype = $this->getItemtypeToFilter();
        $filter = CriteriaFilter::getForItem($this);

        // Build data
        $search_criteria = json_encode($search_criteria);

        if ($filter) {
            // Override existing filter
            $success = $filter->update([
                'id'              => $filter->fields['id'],
                'search_itemtype' => $search_itemtype,
                'search_criteria' => $search_criteria,
            ]);
        } else {
            // Create a new filter
            $filter = new CriteriaFilter();
            $id = $filter->add([
                'itemtype'        => self::getType(),
                'items_id'        => $this->getID(),
                'search_itemtype' => $search_itemtype,
                'search_criteria' => $search_criteria,
            ]);

            $success = $id != false;
        }

        // Log unexpected errors
        if (!$success) {
            trigger_error(
                "Failed to save data: $search_itemtype, $search_criteria",
                E_USER_WARNING
            );
        }

        return $success;
    }

    public function deleteFilter(): bool
    {
        $filter = CriteriaFilter::getForItem($this);

        // No filter, nothing to be done
        if (!$filter) {
            return true;
        }

        // Delete existing filter
        return $filter->delete(['id' => $filter->fields['id']]);
    }
}
