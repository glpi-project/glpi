<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\Toolbox\Sanitizer;
use LogicException;

/**
 * Helper trait to ease interaction with filters
 */
trait FilterableTrait
{
    /**
     * Check that the given item match the filters defined for the current item
     *
     * @param CommonDBTM $item Given item
     *
     * @return bool
     */
    public function itemMatchFilter(CommonDBTM $item): bool
    {
        // Should only be used by FilterableInterface items
        if (!($this instanceof FilterableInterface)) {
            return new LogicException("Not filterable");
        }

        $filter = Item_Filter::getForItem($this);

        // No filter defined
        if (!$filter) {
            return true;
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
                'field' => 2,
                // Search engine seems to expect "contains" here, even if the
                // real search will be done with "equals
                'searchtype' => "contains",
                'value' => $item->fields['id'],
            ],
        ];

        // Execute search
        $data = SearchEngine::getData($item::getType(), [
            'criteria' => $criteria,
        ]);

        return $data['data']['totalcount'] > 0;
    }

    /**
     * Create or update filter for a given item
     *
     * @param string $search_itemtype Itemtype to filter
     * @param array  $search_criteria Search criterias used as filter
     *
     * @return bool
     */
    public function saveFilter(
        string $search_itemtype,
        array $search_criteria
    ): bool {
        // Should only be used by FilterableInterface items
        if (!($this instanceof FilterableInterface)) {
            return new LogicException("Not filterable");
        }

        $filter = Item_Filter::getForItem($this);

        // Build data
        // JSON fields must only be sanitized AFTER being encoded to avoid \'
        // being encoded as \\' where both antislashes cancel each others
        // -> $search_criteria should come from $_UPOST not $_POST
        $search_criteria = json_encode($search_criteria);
        $search_criteria = Sanitizer::sanitize($search_criteria);

        if ($filter) {
            // Override existing filter
            $success = $filter->update([
                'id'              => $filter->fields['id'],
                'search_itemtype' => $search_itemtype,
                'search_criteria' => $search_criteria,
            ]);
        } else {
            // Create a new filter
            $filter = new Item_Filter();
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

    /**
     * Delete filter for a given item
     *
     * @return bool
     */
    public function deleteFilter(): bool
    {
        // Should only be used by FilterableInterface items
        if (!($this instanceof FilterableInterface)) {
            return new LogicException("Not filterable");
        }

        $filter = Item_Filter::getForItem($this);

        // No filter, nothing to be done
        if (!$filter) {
            return true;
        }

        // Delete existing filter
        return $filter->delete(['id' => $filter->fields['id']]);
    }
}
