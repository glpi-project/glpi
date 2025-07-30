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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Search\Input\QueryBuilder;

use function Safe\json_decode;

/**
 * Define filters for a given itemtype, using the search engine UI
 */
final class CriteriaFilter extends CommonDBChild
{
    public static $itemtype = "itemtype";
    public static $items_id = "items_id";

    public static function getTypeName($nb = 0)
    {
        return __('Filter');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        // Only on filterable items
        if (!$item instanceof CommonDBTM || !$item instanceof FilterableInterface) {
            return "";
        }

        // Count number of filter criteria (with nested sub-criteria)
        $nb = 0;
        if (($filter = self::getForItem($item))) {
            // important: array_walk_recursive iterates only over non-array values
            // so we need to count only when we found the 'field' key
            array_walk_recursive($filter->fields['search_criteria'], function ($value, $key) use (&$nb) {
                if ($key === 'field') {
                    $nb++;
                }
            });
        }

        return self::createTabEntry(
            self::getTypeName($nb),
            $nb,
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
        $criteria = $filter ? $filter->fields['search_criteria'] : self::getDefaultSearch($itemtype);
        $can_edit = $item->canUpdateItem();

        // Force criteria into session
        // Even when specifying the criteria in $params, they are not taken into
        // account and GLPI relies on session data instead
        $_SESSION['glpisearch'][$itemtype]['criteria'] = $criteria;

        // Set search engine parameters
        $params = [
            'criteria'                => $criteria,
            'metacriteria'            => [],
            'push_history'            => false,
            'hide_controls'           => true,
            'showmassiveactions'      => false,
            'showbookmark'            => false,
            'showreset'               => false,
            'forcereset'              => true,
            'actionvalue'             => __("Preview results"),
            'extra_actions_templates' => [
                "components/search/criteria_filter_actions.html.twig" => [
                    'itemtype'    => $item->getType(),
                    'items_id'    => $item->getID(),
                    'show_save'   => $can_edit,
                    'show_delete' => $can_edit && !is_null($filter),
                ],
            ],
        ];

        // Print page
        $twig = TemplateRenderer::getInstance();
        $twig->display("components/search/criteria_filter.html.twig", [
            'info_title'       => $item->getInfoTitle(),
            'info_description' => $item->getInfoDescription(),
            'itemtype'         => $itemtype,
            'params'           => $params,
            'filter_enabled'   => $filter !== null,
        ]);

        return true;
    }

    public function post_getFromDB()
    {
        parent::post_getFromDB();

        // Decode filter
        $this->fields['search_criteria'] = json_decode(
            $this->fields['search_criteria'],
            true
        );
    }

    public function getHistoryChangeWhenUpdateField($field)
    {
        // Don't show json changes in history
        if ($field == "search_criteria") {
            return ['0', '', ''];
        }
        return parent::getHistoryChangeWhenUpdateField($field);
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
     * Compute the default search criteria to display for an itemtype
     *
     * @param string $itemtype
     *
     * @return array
     */
    public static function getDefaultSearch(string $itemtype): array
    {
        // Some item may define a getDefaultSearchRequest method
        $item = getItemForItemtype($itemtype);
        if ($item instanceof DefaultSearchRequestInterface) {
            $default_search_request = $item::getDefaultSearchRequest();

            // Not all search request define search criteria
            if (isset($default_search_request['criteria'])) {
                return $default_search_request['criteria'];
            }
        }

        // Fallback to getDefaultCriteria
        return QueryBuilder::getDefaultCriteria($itemtype);
    }
}
