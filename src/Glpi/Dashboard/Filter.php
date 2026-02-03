<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Dashboard;

use CommonDBChild;
use Glpi\Dashboard\Filters\{
    DatesFilter,
    DatesModFilter,
    GroupRequesterFilter,
    GroupTechFilter,
    ItilCategoryFilter,
    LocationFilter,
    ManufacturerFilter,
    RequestTypeFilter,
    StateFilter,
    TicketTypeFilter,
    UserTechFilter
};
use Glpi\Plugin\Hooks;
use Plugin;
use Session;

/**
 * Filter class
 **/
class Filter extends CommonDBChild
{
    public static string $itemtype = Dashboard::class;
    public static string $items_id = 'dashboards_dashboards_id';

    /**
     * Return IDs of filters that can be applied to given table.
     *
     * @return array of filters
     */
    public static function getAppliableFilters(string $table): array
    {
        global $GLPI_CACHE;

        $cache_key = "dashboard_filters_appliable_$table" . Session::getLoginUserID();
        if (($filters_ids = $GLPI_CACHE->get($cache_key)) !== null) {
            return $filters_ids;
        }

        $filters_ids = [];

        foreach (self::getRegisteredFilterClasses() as $filter_class) {
            if ($filter_class::canBeApplied($table)) {
                $filters_ids[] = $filter_class::getId();
            }
        }

        // Set a short cache to have an auto-invalidation;
        // 5s should be enough to load a full dashboard
        // and expiring before the user hit another request.
        $GLPI_CACHE->set($cache_key, $filters_ids, 5);

        return $filters_ids;
    }

    /**
     * Return registered filters classes.
     *
     * @return array
     */
    public static function getRegisteredFilterClasses(): array
    {
        global $PLUGIN_HOOKS;

        $filters = [
            DatesFilter::class,
            DatesModFilter::class,
            ItilCategoryFilter::class,
            LocationFilter::class,
            ManufacturerFilter::class,
            RequestTypeFilter::class,
            StateFilter::class,
            TicketTypeFilter::class,
            GroupRequesterFilter::class,
            GroupTechFilter::class,
            UserTechFilter::class,
        ];

        foreach (($PLUGIN_HOOKS[Hooks::DASHBOARD_FILTERS] ?? []) as $plugin => $hook_filters) {
            if (!Plugin::isPluginActive($plugin)) {
                continue;
            }
            array_push($filters, ...$hook_filters);
        }

        return $filters;
    }

    /**
     * Return filters choices (to be used in a dropdown context).
     * Keys are filters ids, values are filters labels.
     *
     * @return array of filters
     */
    public static function getFilterChoices(): array
    {
        $filters = [];

        /* @var \Glpi\Dashboard\Filters\AbstractFilter $filter_class */
        foreach (self::getRegisteredFilterClasses() as $filter_class) {
            $filters[$filter_class::getId()] = $filter_class::getName();
        }

        return $filters;
    }

    /**
     * Return filters for the provided dashboard
     *
     * @param int $dashboards_id
     *
     * @return string the JSON representation of the filter data
     */
    public static function getForDashboard(int $dashboards_id = 0): string
    {
        global $DB;

        $dr_iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id,
                'users_id'                 => Session::getLoginUserID(),
            ],
        ]);

        $settings = $dr_iterator->count() === 1 ? $dr_iterator->current()['filter'] : null;

        return \is_string($settings) && \json_validate($settings) ? $settings : '{}';
    }

    /**
     * Save filter in DB for the provided dashboard
     *
     * @param int $dashboards_id id (not key) of the dashboard
     * @param string $settings contains a JSON representation of the filter data
     *
     * @return void
     */
    public static function addForDashboard(int $dashboards_id = 0, string $settings = '')
    {
        global $DB;

        $DB->updateOrInsert(
            self::getTable(),
            [
                'filter'                   => $settings,
            ],
            [
                'dashboards_dashboards_id' => $dashboards_id,
                'users_id'                 => Session::getLoginUserID(),
            ]
        );
    }
}
