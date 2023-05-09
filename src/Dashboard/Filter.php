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
use Plugin;
use Toolbox;
use Glpi\Dashboard\Filters\{
    AbstractFilter,
    DatesFilter,
    ItilCategoryFilter,
    LocationFilter,
    ManufacturerFilter,
    RequestTypeFilter,
    StateFilter,
    TicketTypeFilter,
    GroupTechFilter,
    UserTechFilter,
    DatesModFilter
};

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

        foreach (($PLUGIN_HOOKS[Hooks::DASHBOARD_FILTERS] ?? []) as $hook_filters) {
            if (!Plugin::isPluginActive($hook_filters)) {
                continue;
            }

            array_push($filters, ...$hook_filters);
        }

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

    /**
     * @deprecated 10.0.0
     */
    public static function dates($values = "", string $fieldname = 'dates'): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . DatesFilter::class . ' instead.');
        return DatesFilter::getHtml($values, $fieldname);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function dates_mod($values): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . DatesModFilter::class . ' instead.');
        return DatesModFilter::getHtml($values);
    }


    /**
     * @deprecated 10.0.0
     */
    public static function itilcategory(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . ItilCategoryFilter::class . ' instead.');
        return ItilCategoryFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function requesttype(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . RequestTypeFilter::class . ' instead.');
        return RequestTypeFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function location(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . LocationFilter::class . ' instead.');
        return LocationFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function manufacturer(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . ManufacturerFilter::class . ' instead.');
        return ManufacturerFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function group_tech(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . GroupTechFilter::class . ' instead.');
        return GroupTechFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function user_tech(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . UserTechFilter::class . ' instead.');
        return UserTechFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function state(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . StateFilter::class . ' instead.');
        return StateFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function tickettype(string $value = ""): string
    {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . TicketTypeFilter::class . ' instead.');
        return TicketTypeFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.0
     */
    public static function displayList(
        string $value = "",
        string $fieldname = "",
        string $itemtype = "",
        array $add_params = []
    ): string {
        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . AbstractFilter::class . ' instead.');
        return "";
    }

    /**
     * Get generic HTML for a filter
     *
     * @deprecated 10.0.0
     * @param string $id system name of the filter (ex "dates")
     * @param string $field html of the filter
     * @param string $label displayed label for the filter
     * @param bool   $filled
     *
     * @return string the html for the complete field
     */
    public static function field(
        string $id = "",
        string $field = "",
        string $label = "",
        bool $filled = false
    ): string {

        Toolbox::deprecated(__METHOD__ . ' is deprecated. Use ' . AbstractFilter::class . ' instead.');
        return AbstractFilter::field($id, $field, $label, $filled);
    }
}
