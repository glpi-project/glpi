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
     * Return IDs of filters that can be applied to given table.
     *
     * @return array of filters
     */
    public static function getAppliableFilters(string $table): array
    {
        $filters_ids = [];

        foreach (self::getRegisteredFilterClasses() as $filter_class) {
            if ($filter_class::canBeApplied($table)) {
                $filters_ids[] = $filter_class::getId();
            }
        }

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
     * Return all available filters.
     * Keys are filters ids, values are filters labels.
     *
     * @return array of filters
     *
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function getAll(): array
    {
        return self::getFilterChoices();
    }

    /**
     * Get HTML for a dates range filter
     *
     * @param string|array $values init the input with these values, will be a string if empty values
     * @param string $fieldname how is named the current date field
     *                         (used to specify creation date or last update)
     *
     * @return string
     *
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function dates($values = "", string $fieldname = 'dates'): string
    {
        return DatesFilter::getHtml($values);
    }

    /**
     * Get HTML for a dates range filter. Same as date but for last update field
     *
     * @param string|array $values init the input with these values, will be a string if empty values
     *
     * @return string
     *
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function dates_mod($values): string
    {
        return DatesModFilter::getHtml($values);
    }


    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function itilcategory(string $value = ""): string
    {
        return ItilCategoryFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function requesttype(string $value = ""): string
    {
        return RequestTypeFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function location(string $value = ""): string
    {
        return LocationFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function manufacturer(string $value = ""): string
    {
        return ManufacturerFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function group_tech(string $value = ""): string
    {
        return GroupTechFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function user_tech(string $value = ""): string
    {
        return UserTechFilter::getHtml($value);
    }

    /**
     * @deprecated 10.0.8
     */
    public static function state(string $value = ""): string
    {
        return StateFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function tickettype(string $value = ""): string
    {
        return TicketTypeFilter::getHtml($value);
    }

    /**
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function displayList(
        string $value = "",
        string $fieldname = "",
        string $itemtype = "",
        array $add_params = []
    ): string {
        $value     = !empty($value) ? $value : null;
        $rand      = mt_rand();
        $label     = self::getFilterChoices()[$fieldname];
        $field     = $itemtype::dropdown([
            'name'                => $fieldname,
            'value'               => $value,
            'rand'                => $rand,
            'display'             => false,
            'display_emptychoice' => false,
            'emptylabel'          => '',
            'placeholder'         => $label,
            'on_change'           => "on_change_{$rand}()",
            'allowClear'          => true,
            'width'               => ''
        ] + $add_params);

        $js = <<<JAVASCRIPT
      var on_change_{$rand} = function() {
         var dom_elem    = $('#dropdown_{$fieldname}{$rand}');
         var selected    = dom_elem.find(':selected').val();

         Dashboard.getActiveDashboard().saveFilter('{$fieldname}', selected);

         $(dom_elem).closest("fieldset").toggleClass("filled", selected !== null)
      };

JAVASCRIPT;
        $field .= \Html::scriptBlock($js);

        return self::field($fieldname, $field, $label, $value !== null);
    }

    /**
     * Get generic HTML for a filter
     *
     * @param string $id system name of the filter (ex "dates")
     * @param string $field html of the filter
     * @param string $label displayed label for the filter
     * @param bool   $filled
     *
     * @return string the html for the complete field
     *
     * @FIXME Deprecate/remove in GLPI 10.1.
     */
    public static function field(
        string $id = "",
        string $field = "",
        string $label = "",
        bool $filled = false
    ): string {
        return AbstractFilter::field($id, $field, $label, $filled);
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
