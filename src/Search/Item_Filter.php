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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Toolbox\Sanitizer;
use Session;

/**
 * Define filters for a given itemtype, using the search engine UI
 */
final class Item_Filter extends CommonDBChild
{
    public static $itemtype = "itemtype";
    public static $items_id = "items_id";

    public static function getTypeName($nb = 0)
    {
        return __('Filter');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Only on filterable items
        if (!$item instanceof CommonDBTM || !$item instanceof FilterableInterface) {
            return false;
        }

        return self::createTabEntry(
            self::getTypeName(Session::getPluralNumber()),
            self::getForItem($item) ? 1 : 0, // Help user spot that a filter exist for this item
            $item::getType(),
            'ti ti-adjustments-horizontal'
        );
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // Only on filterable commondbtm
        if (!$item instanceof CommonDBTM || !$item instanceof FilterableInterface) {
            return false;
        }

        // Load saved filters
        $filter = self::getForItem($item);
        $itemtype = $item->getItemtypeToFilter();
        $criteria = $filter ? $filter->fields['search_criteria'] : $itemtype::getDefaultSearchRequest()['criteria'];
        $can_edit = $item->canUpdateItem();

        // Force criteria into session
        // Even when specifying the criteria in $params, they are not taken into
        // account and GLPI relies on session data instead
        $_SESSION['glpisearch'][$itemtype]['criteria'] = $criteria;

        // Print page
        echo "<div class='col search-container'>";
        $params = [
            'criteria'                => $criteria,
            'metacriteria'            => [],
            'push_history'            => false,
            'hide_controls'           => true,
            'showmassiveactions'      => false,
            'showbookmark'            => false,
            'showreset'               => false,
            'actionvalue'             => __("Preview results"),
            'extra_actions_templates' => [
                "components/search/items_filter_actions.html.twig" => [
                    'itemtype'    => $item->getType(),
                    'items_id'    => $item->getID(),
                    'show_save'   => $can_edit,
                    'show_delete' => $can_edit && !is_null($filter),
                ],
            ],
        ];
        QueryBuilder::showGenericSearch($itemtype, $params);
        SearchEngine::showOutput($itemtype, $params);
        echo "</div>";

        return true;
    }

    /**
     * Check that the given item match a filterable item restrictions
     *
     * @param CommonDBTM                     $item       Given item
     * @param CommonDBTM&FilterableInterface $filterable Filterable
     *
     * @return bool
     */
    public static function itemMatchFilter(
        CommonDBTM $item,
        CommonDBTM&FilterableInterface $filterable
    ): bool {
        $filter = self::getForItem($filterable);

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

    public function post_getFromDB()
    {
        parent::post_getFromDB();

        // Decode filter
        $this->fields['search_criteria'] = json_decode(
            $this->fields['search_criteria'],
            JSON_OBJECT_AS_ARRAY
        );
    }

    /**
     * Get filter for a given item
     *
     * @param CommonDBTM&FilterableInterface $item Given item
     *
     * @return null|self Null if no filter are defined for the given item
     */
    public static function getForItem(CommonDBTM&FilterableInterface $item): ?self
    {
        $filter = new self();
        $filter_exist = $filter->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        return $filter_exist ? $filter : null;
    }

    /**
     * Delete filter for a given item
     *
     * @param CommonDBTM&FilterableInterface $item Target item
     *
     * @return bool
     */
    public static function deleteFilter(CommonDBTM&FilterableInterface $item): bool
    {
        $filter = self::getForItem($item);

        // No filter, nothing to be done
        if (!$filter) {
            return true;
        }

        // Delete existing filter
        return $filter->delete(['id' => $filter->fields['id']]);
    }

    /**
     * Create or update filter for a given item
     *
     * @param CommonDBTM&FilterableInterface $item            Target item
     * @param string                $search_itemtype Itemtype to filte
     * @param array                 $search_criteria Search criterias used as filter
     *
     * @return bool
     */
    public static function saveFilter(
        CommonDBTM&FilterableInterface $item,
        string $search_itemtype,
        array $search_criteria
    ): bool {
        $filter = self::getForItem($item);

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
            $filter = new self();
            $id = $filter->add([
                'itemtype'        => $item::getType(),
                'items_id'        => $item->getID(),
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

    public function getHistoryChangeWhenUpdateField($field)
    {
        // Don't show json changes in history
        if ($field == "search_criteria") {
            return ['0', '', ''];
        }
        return parent::getHistoryChangeWhenUpdateField($field);
    }
}
