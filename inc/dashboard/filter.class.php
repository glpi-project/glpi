<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Dashboard;

use CommonGLPI;
use ITILCategory;
use RequestType;
use Location;
use Manufacturer;
use Session;
use Html;
use Group;
use Plugin;
use User;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Filter class
**/
class Filter extends CommonGLPI {

   /**
    * Return all available filters
    * Plugins can hooks on this functions to add their own filters
    *
    * @return array of filters
    */
   static function getAll(): array {
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

      $more_filters = Plugin::doHookFunction("dashboard_filters");
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
   static function dates($values = "", string $fieldname = 'dates'): string {
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
      $js = Html::scriptBlock($js);

      return $js.self::field($fieldname, $field, $label, is_array($values) && count($values) > 0);
   }

   /**
    * Get HTML for a dates range filter. Same as date but for last update field
    *
    * @param string|array $values init the input with these values, will be a string if empty values
    *
    * @return string
    */
   static function dates_mod($values): string {
      return self::dates($values, "dates_mod");
   }


   static function itilcategory(string $value = ""): string {
      return self::dropdown($value, 'itilcategory', ItilCategory::class);
   }

   static function requesttype(string $value = ""): string {
      return self::dropdown($value, 'requesttype', RequestType::class);
   }

   static function location(string $value = ""): string {
      return self::dropdown($value, 'location', Location::class);
   }

   static function manufacturer(string $value = ""): string {
      return self::dropdown($value, 'manufacturer', Manufacturer::class);
   }

   static function group_tech(string $value = ""): string {
      return self::dropdown($value, 'group_tech', Group::class, ['toadd' => [-1 => __("My groups")]]);
   }

   static function user_tech(string $value = ""): string {
      return self::dropdown($value, 'user_tech', User::class, ['right' => 'own_ticket']);
   }

   static function dropdown(
      string $value = "",
      string $fieldname = "",
      string $itemtype = "",
      array $add_params = []
   ): string {
      $value     = !empty($value) ? (int) $value : null;
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
         var selected    = parseInt(dom_elem.find(':selected').val());

         Dashboard.saveFilter('{$fieldname}', selected);

         $(dom_elem).closest("fieldset").toggleClass("filled", selected > 0)
      };

JAVASCRIPT;
      $js = Html::scriptBlock($js);

      return $js.self::field($fieldname, $field, $label, $value > 0);
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
   static function field(
      string $id = "",
      string $field = "",
      string $label = "",
      bool $filled = false
   ): string {

      $rand  = mt_rand();
      $class = $filled ? "filled" : "";
      $html  = <<<HTML
      <fieldset id='filter-{$rand}' class='filter $class' data-filter-id='{$id}'>
         $field
         <legend>$label</legend>
         <i class='fas fa-trash delete-filter'></i>
      </fieldset>
HTML;

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

      return $html.$js;
   }
}
