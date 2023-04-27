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

namespace Glpi\Dashboard;
use Glpi\Plugin\Hooks;
use Session;
use Glpi\Dashboard\Filters\DatesFilter;
use Glpi\Dashboard\Filters\ItilCategoryFilter;
use Glpi\Dashboard\Filters\LocationFilter;
use Glpi\Dashboard\Filters\ManufacturerFilter;
use Glpi\Dashboard\Filters\RequestTypeFilter;
use Glpi\Dashboard\Filters\StateFilter;
use Glpi\Dashboard\Filters\TicketTypeFilter;
use Glpi\Dashboard\Filters\GroupTechFilter;
use Glpi\Dashboard\Filters\UserTechFilter;
use Glpi\Dashboard\Filters\DatesModFilter;

/**
 * Filter class
 **/
class Filter extends \CommonDBChild
{
    public static $itemtype = "Glpi\\Dashboard\\Dashboard";
    public static $items_id = 'dashboards_dashboards_id';

    /**
     * Return all available filters
     *
     * @return array of filters
     */
    public static function getAll(): array
    {
        global $PLUGIN_HOOKS;
        $more_filters = [];

        $filters = [
            DatesFilter::class,
            DatesModFilter::class,
            ItilCategoryFilter::class,
            LocationFilter::class,
            ManufacturerFilter::class,
            RequestTypeFilter::class,
            StateFilter::class,
            TicketTypeFilter::class,
            GroupTechFilter::class,
            UserTechFilter::class,
        ];

        if(isset($PLUGIN_HOOKS[Hooks::DASHBOARD_FILTERS])) {
            foreach ($PLUGIN_HOOKS[Hooks::DASHBOARD_FILTERS] as $hook_filters) {
                foreach ($hook_filters as $filter) {
                    $more_filters[] = $filter;
                }
            }
        }
        $filters = array_merge($filters, $more_filters);

        return $filters;
    }   

    /**
     * Return filters for the provided dashboard
     *
     * @param int $dashboards_id
     *
     * @return array the JSON representation of the filter data
     */
    public static function getForDashboard(int $dashboards_id = 0): string
    {
        global $DB;


        $dr_iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id,
                'users_id'                 => Session::getLoginUserID(),
            ]
        ]);

        $settings = $dr_iterator->count() === 1 ? $dr_iterator->current()['filter'] : null;

        return is_string($settings) ? $settings : '{}';
    }

    /**
     * Save filter in DB for the provided dashboard
     *
     * @param int $dashboards_id id (not key) of the dashboard
     * @param array $settings contains a JSON representation of the filter data
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
