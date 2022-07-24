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

namespace Glpi\Dashboard;

use Glpi\Plugin\Hooks;
use Group;
use Html;
use ITILCategory;
use Location;
use Manufacturer;
use Plugin;
use RequestType;
use Session;
use User;

/**
 * Filter class
 **/
class Filter extends \CommonDBChild
{
    public static $itemtype = "Glpi\\Dashboard\\Dashboard";
    public static $items_id = 'dashboards_dashboards_id';

    /**
     * Return all available filters
     * Plugins can hooks on this functions to add their own filters
     *
     * @return array of filters
     */
    public static function getAll(): array
    {
        $filters = [
            'dates'        => __("Creation date"),
            'dates_mod'    => __("Last update"),
            'itilcategory' => ITILCategory::getTypeName(Session::getPluralNumber()),
            'requesttype'  => RequestType::getTypeName(Session::getPluralNumber()),
            'location'     => Location::getTypeName(Session::getPluralNumber()),
            'manufacturer' => Manufacturer::getTypeName(Session::getPluralNumber()),
            'group_tech'   => __("Technician group"),
            'user_tech'    => __("Technician"),
        ];

        $more_filters = Plugin::doHookFunction(Hooks::DASHBOARD_FILTERS);
        if (is_array($more_filters)) {
            $filters = array_merge($filters, $more_filters);
        }

        return $filters;
    }

    /**
     * Get HTML for a dates range filter
     *
     * @param string|array $values init the input with these values, will be a string if empty values
     * @param string $fieldname how is named the current date field
     *                         (used to specify creation date or last update)
     *
     * @return string
     */
    public static function dates($values = "", string $fieldname = 'dates'): string
    {
       // string mean empty value
        if (is_string($values)) {
            $values = [];
        }

        $rand  = mt_rand();
        $label = self::getAll()[$fieldname];
        $field = Html::showDateField('filter-dates', [
            'value'        => $values,
            'rand'         => $rand,
            'range'        => true,
            'display'      => false,
            'calendar_btn' => false,
            'placeholder'  => $label,
            'on_change'    => "on_change_{$rand}(selectedDates, dateStr, instance)",
        ]);

        $js = <<<JAVASCRIPT
      var on_change_{$rand} = function(selectedDates, dateStr, instance) {
         // we are waiting for empty value or a range of dates,
         // don't trigger when only the first date is selected
         var nb_dates = selectedDates.length;
         if (nb_dates == 0 || nb_dates == 2) {
            Dashboard.saveFilter('{$fieldname}', selectedDates);
            $(instance.input).closest("fieldset").addClass("filled");
         }
      };
JAVASCRIPT;
        $field .= Html::scriptBlock($js);

        return self::field($fieldname, $field, $label, is_array($values) && count($values) > 0);
    }

    /**
     * Get HTML for a dates range filter. Same as date but for last update field
     *
     * @param string|array $values init the input with these values, will be a string if empty values
     *
     * @return string
     */
    public static function dates_mod($values): string
    {
        return self::dates($values, "dates_mod");
    }


    public static function itilcategory(string $value = ""): string
    {
        return self::displayList($value, 'itilcategory', ITILCategory::class);
    }

    public static function requesttype(string $value = ""): string
    {
        return self::displayList($value, 'requesttype', RequestType::class);
    }

    public static function location(string $value = ""): string
    {
        return self::displayList($value, 'location', Location::class);
    }

    public static function manufacturer(string $value = ""): string
    {
        return self::displayList($value, 'manufacturer', Manufacturer::class);
    }

    public static function group_tech(string $value = ""): string
    {
        return self::displayList($value, 'group_tech', Group::class, ['toadd' => ['mygroups' => __("My groups")]]);
    }

    public static function user_tech(string $value = ""): string
    {
        return self::displayList($value, 'user_tech', User::class, [
            'right' => 'own_ticket',
            'toadd' => [
                [
                    'id'    => 'myself',
                    'text'  => __('Myself'),
                ]
            ]
        ]);
    }

    public static function displayList(
        string $value = "",
        string $fieldname = "",
        string $itemtype = "",
        array $add_params = []
    ): string {
        $value     = !empty($value) ? $value : null;
        $rand      = mt_rand();
        $label     = self::getAll()[$fieldname];
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

         Dashboard.saveFilter('{$fieldname}', selected);

         $(dom_elem).closest("fieldset").toggleClass("filled", selected !== null)
      };

JAVASCRIPT;
        $field .= Html::scriptBlock($js);

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
     */
    public static function field(
        string $id = "",
        string $field = "",
        string $label = "",
        bool $filled = false
    ): string {

        $rand  = mt_rand();
        $class = $filled ? "filled" : "";

        $js = <<<JAVASCRIPT
      $(function () {
         $('#filter-{$rand} input')
            .on('input', function() {
               var str_len = $(this).val().length;
               if (str_len > 0) {
                  $('#filter-{$rand}').addClass('filled');
               } else {
                  $('#filter-{$rand}').removeClass('filled');
               }

               $(this).width((str_len + 1) * 8 );
            });

         $('#filter-{$rand}')
            .hover(function() {
               $('.dashboard .card.filter-{$id}').addClass('filter-impacted');
            }, function() {
               $('.dashboard .card.filter-{$id}').removeClass('filter-impacted');
            });
      });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        $html  = <<<HTML
      <fieldset id='filter-{$rand}' class='filter $class' data-filter-id='{$id}'>
         $field
         <legend>$label</legend>
         <i class='btn btn-sm btn-icon btn-ghost-secondary ti ti-trash delete-filter'></i>
         {$js}
      </fieldset>
HTML;

        return $html;
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
