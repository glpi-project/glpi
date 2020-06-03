<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use Mexitek\PHPColors\Color;
use ScssPhp\ScssPhp\Compiler;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Central class
**/
class Widget extends \CommonGLPI {
   static $animation_duration = 1000; // in millseconds


   /**
    * Define all possible widget types with their $labels/
    * This is used when adding a new card to display optgroups
    * This array can be hooked by plugins to add their own definitions.
    *
    * @return array
    */
   public static function getAllTypes(): array {
      global $CFG_GLPI;

      $types = [
         'pie' => [
            'label'    => __("Pie"),
            'function' => 'Glpi\\Dashboard\\Widget::pie',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/pie.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'donut' => [
            'label'    => __("Donut"),
            'function' => 'Glpi\\Dashboard\\Widget::donut',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/donut.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'halfpie' => [
            'label'    => __("Half pie"),
            'function' => 'Glpi\\Dashboard\\Widget::halfPie',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/halfpie.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'halfdonut' => [
            'label'    => __("Half donut"),
            'function' => 'Glpi\\Dashboard\\Widget::halfDonut',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/halfdonut.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'bar' => [
            'label'    => __("Bars"),
            'function' => 'Glpi\\Dashboard\\Widget::simpleBar',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/bar.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'line' => [
            'label'    => __("Line"),
            'function' => 'Glpi\\Dashboard\\Widget::simpleLine',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/line.png',
            'limit'    => true,
         ],
         'lines' => [
            'label'    => __("Multiple lines"),
            'function' => 'Glpi\\Dashboard\\Widget::multipleLines',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/line.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'area' => [
            'label'    => __("Area"),
            'function' => 'Glpi\\Dashboard\\Widget::simpleArea',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/area.png',
            'limit'    => true,
         ],
         'areas' => [
            'label'    => __("Multiple areas"),
            'function' => 'Glpi\\Dashboard\\Widget::multipleAreas',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/area.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'bars' => [
            'label'    => __("Multiple bars"),
            'function' => 'Glpi\\Dashboard\\Widget::multipleBars',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/bar.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'stackedbars' => [
            'label'    => __("Stacked bars"),
            'function' => 'Glpi\\Dashboard\\Widget::StackedBars',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/stacked.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'hbar' => [
            'label'    => __("Horizontal bars"),
            'function' => 'Glpi\\Dashboard\\Widget::simpleHbar',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/hbar.png',
            'gradient' => true,
            'limit'    => true,
         ],
         'bigNumber' => [
            'label'    => __("Big number"),
            'function' => 'Glpi\\Dashboard\\Widget::bigNumber',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/bignumber.png',
         ],
         'multipleNumber' => [
            'label'    => __("Multiple numbers"),
            'function' => 'Glpi\\Dashboard\\Widget::multipleNumber',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/multiplenumbers.png',
            'limit'    => true,
         ],
         'markdown' => [
            'label'    => __("Editable markdown"),
            'function' => 'Glpi\\Dashboard\\Widget::markdown',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/markdown.png',
         ],
         'searchShowList' => [
            'label'    => __("Search result"),
            'function' => 'Glpi\\Dashboard\\Widget::searchShowList',
            'image'    => $CFG_GLPI['root_doc'].'/pics/charts/table.png',
            'limit'    => true,
         ],
      ];

      $more_types = \Plugin::doHookFunction("dashboard_types");
      if (is_array($more_types)) {
         $types = array_merge($types, $more_types);
      }

      return $types;
   }


   /**
    * Display a big number widget.
    *
    * @param array $params contains these keys:
    * - int    'number': the number to display
    * - string 'url': url to redirect when clicking on the widget
    * - string 'label': title of the widget
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    *
    * @return string html of the widget
    */
   public static function bigNumber(array $params = []): string {
      $default = [
         'number' => 0,
         'url'    => '',
         'label'  => '',
         'alt'    => '',
         'color'  => '',
         'icon'   => '',
         'id'     => 'bn_'.mt_rand(),
      ];
      $p = array_merge($default, $params);

      $formatted_number = \Toolbox::shortenNumber($p['number']);
      $fg_color         = \Toolbox::getFgColor($p['color']);
      $fg_hover_color   = \Toolbox::getFgColor($p['color'], 15);
      $fg_hover_border  = \Toolbox::getFgColor($p['color'], 30);

      $href = strlen($p['url'])
         ? "href='{$p['url']}'"
         : "";

      $html = <<<HTML
      <style>
         #{$p['id']} {
            background-color: {$p['color']};
            color: {$fg_color};
         }

         #{$p['id']}:hover {
            background-color: {$fg_hover_color};
            border: 1px solid {$fg_hover_border};
         }
      </style>
      <a {$href}
         id="{$p['id']}"
         class="card big-number"
         title="{$p['alt']}">
         <span class="content">$formatted_number</span>
         <div class="label">{$p['label']}</div>
         <i class="main-icon {$p['icon']}" style="color: {$fg_color}"></i>
      </a>
HTML;

      return $html;
   }


   /**
    * Display a multiple big number widget.
    *
    * @param array $params contains these keys:
    * - array  'data': represents the lines to display
    *    - int    'number': the number to display in the line
    *    - string 'url': url to redirect when clicking on the line
    *    - string 'label': title of the line
    *    - string 'number': number to display in the line
    *    - string 'icon': font awesome class to display an icon side of the line
    *    - int    'limit': the numbers of lines diplayed
    * - string 'label': global title of the widget
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    *
    * @return string html of the widget
    */
   public static function multipleNumber(array $params = []): string {
      $default = [
         'data'   => [],
         'label'  => '',
         'alt'    => '',
         'color'  => '',
         'icon'   => '',
         'limit'  => 99999,
      ];
      $p = array_merge($default, $params);
      $default_entry = [
         'url'    => '',
         'icon'   => '',
         'label'  => '',
         'number' => '',
      ];

      $nb_lines = min($p['limit'], count($p['data']));
      array_splice($p['data'], $nb_lines);

      $fg_color = \Toolbox::getFgColor($p['color']);

      $numbers_html = "";
      foreach ($p['data'] as $entry) {
         if (!is_array($entry)) {
            continue;
         }
         $entry = array_merge($default_entry, $entry);

         $href = strlen($entry['url'])
            ? "href='{$entry['url']}'"
            : "";

         $formatted_number = \Toolbox::shortenNumber($entry['number']);
         $numbers_html.= <<<HTML
            <a {$href} class="line">
               <span class="content">$formatted_number</span>
               <i class="icon {$entry['icon']}"></i>
               <span class="label">{$entry['label']}</span>
            </a>
HTML;
      }

      $nodata = isset($p['data']['nodata']) && $p['data']['nodata'];
      if ($nodata) {
         $numbers_html = "<span class='line empty-card no-data'>
               <span class='content'>
                  <i class='icon fas fa-exclamation-triangle'></i>
               </span>
               <span class='label'>".__('No data found')."</span>
            <span>";
      }

      $html = <<<HTML
      <div class="card multiple-number"
           title="{$p['alt']}"
           style="background-color: {$p['color']}; color: {$fg_color}">
         <div class='scrollable'>
            <div class='table'>
            {$numbers_html}
            </div>
         </div>
         <span class="main-label">{$p['label']}</span>
         <i class="main-icon {$p['icon']}" style="color: {$fg_color}"></i>
      </div>
HTML;

      return $html;
   }


   /**
    * Display a widget with a pie chart
    *
    * @param array $params contains these keys:
    * - array  'data': represents the slices to display
    *    - int    'number': number of the slice
    *    - string 'url': url to redirect when clicking on the slice
    *    - string 'label': title of the slice
    * - string 'label': global title of the widget
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    * - bool   'use_gradient': gradient or generic palette
    * - int    'limit': the number of slices
    * - bool 'donut': do we want a "holed" pie
    * - bool 'gauge': do we want an half pie
    *
    * @return string html of the widget
    */
   public static function pie(array $params = []): string {
      $default = [
         'data'         => [],
         'label'        => '',
         'alt'          => '',
         'color'        => '',
         'icon'         => '',
         'donut'        => false,
         'half'        => false,
         'use_gradient' => false,
         'limit'        => 99999,
         'rand'         => mt_rand(),
      ];
      $p = array_merge($default, $params);
      $default_entry = [
         'url'    => '',
         'icon'   => '',
         'label'  => '',
         'number' => '',
      ];

      $nb_slices = min($p['limit'], count($p['data']));
      array_splice($p['data'], $nb_slices);

      $nodata   = isset($p['data']['nodata']) && $p['data']['nodata'];
      $fg_color = \Toolbox::getFgColor($p['color']);

      $class = "pie";
      $class.= $p['half'] ? " half": "";
      $class.= $p['donut'] ? " donut": "";

      $no_data_html = "";
      if ($nodata) {
         $no_data_html = "<span class='empty-card no-data'>
               <div>".__('No data found')."</div>
            <span>";
      }

      $nb_series = min($p['limit'], count($p['data']));

      $palette_style = "";
      if ($p['use_gradient']) {
         $palette_style = self::getCssGradientPalette(
            $p['color'],
            $nb_series,
            ".dashboard #chart-{$p['rand']}",
            false
         );
      }

      $html = <<<HTML
      <style>
         #chart-{$p['rand']} .ct-label {
            fill: {$fg_color};
            color: {$fg_color};
         }
         {$palette_style}
      </style>
      <div class="card g-chart {$class}"
           id="chart-{$p['rand']}"
           style="background-color: {$p['color']}; color: {$fg_color}">
         <div class="chart ct-chart">{$no_data_html}</div>
         <span class="main-label">{$p['label']}</span>
         <i class="main-icon {$p['icon']}"></i>
      </div>
HTML;

      if ($nodata) {
         return $html;
      }

      $labels = [];
      $series = [];
      $total = 0;
      foreach ($p['data'] as $entry) {
         $entry = array_merge($default_entry, $entry);
         $total+= $entry['number'];

         $labels[] = $entry['label'];
         $series[] = [
            'meta'  => $entry['label'],
            'value' => $entry['number'],
            'url'   => $entry['url'],
         ];
      }
      $total_txt = \Toolbox::shortenNumber($total, 1, false);

      $labels = json_encode($labels);
      $series = json_encode($series);

      $chartPadding = 4;
      $height_divider = 1;
      $half_opts = "";
      if ($p['half']) {
         $half_opts = "
            startAngle: 270,
            total: ".($total*2).",
         ";
         $chartPadding = 9;
         $height_divider = 2;
      }

      $donut_opts = "
         showLabel: false,
      ";
      if ($p['donut']) {
         $donut_opts = "
            donutSolid: true,
            showLabel: true,
            labelInterpolationFnc: function(value) {
               return '{$total_txt}';
            },
         ";
      }

      $donut  = $p['donut'] ? 'true' : 'false';
      $height = $p['half'] ? '180%' : '100%';
      $animation_duration = self::$animation_duration;

      $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Pie('#chart-{$p['rand']} .chart', {
            labels: {$labels},
            series: {$series},
         }, {
            width: 'calc(100% - 5px)',
            height: 'calc({$height} - 5px)',
            chartPadding: {$chartPadding},
            donut: {$donut},
            $donut_opts
            $half_opts
            donutWidth: '50%',
            plugins: [
               Chartist.plugins.tooltip({
                  appendToBody: true,
                  class: 'dashboard-tooltip'
               })
            ]
         });


         chart.on('draw', function(data) {
            // animate
            if (data.type === 'slice') {
               // set url redirecting on slice
               var url = _.get(data, 'series.url') || "";
               if (url.length > 0) {
                  data.element.attr({
                     'data-clickable': true
                  });
                  data.element._node.onclick = function() {
                     if (!Dashboard.edit_mode) {
                        window.location = url;
                     }
                  }
               }

               // Get the total path length in order to use for dash array animation
               var pathLength = data.element._node.getTotalLength();

               // Set a dasharray that matches the path length as prerequisite to animate dashoffset
               data.element.attr({
                  'stroke-dasharray': pathLength + 'px ' + pathLength + 'px'
               });

               // Create animation definition while also assigning an ID to the animation for later sync usage
               var animationDefinition = {
                  'stroke-dashoffset': {
                     id: 'anim' + data.index,
                     dur: {$animation_duration},
                     from: -pathLength + 'px',
                     to:  '0px',
                     easing: Chartist.Svg.Easing.easeOutQuint,
                     // We need to use `fill: 'freeze'` otherwise our animation will fall back to initial (not visible)
                     fill: 'freeze'
                  }
               };

               // We need to set an initial value before the animation starts as we are not in guided mode which would do that for us
               data.element.attr({
                  'stroke-dashoffset': -pathLength + 'px'
               });

               // We can't use guided mode as the animations need to rely on setting begin manually
               // See http://gionkunz.github.io/chartist-js/api-documentation.html#chartistsvg-function-animate
               data.element.animate(animationDefinition, false);
            }

            // donut center label
            if (data.type === 'label') {
               if (data.index === 0) {
                  var width = data.element.root().width() / 2;
                  var height = data.element.root().height() / 2;
                  var fontsize = ((height / {$height_divider}) / (1.3 * "{$total_txt}".length));
                  data.element.attr({
                     dx: width,
                     dy: height - ($chartPadding / 2),
                     'style': 'font-size: '+fontsize,
                  });

                  // apend real total
                  var text = new Chartist.Svg('title');
                  text.text("{$total}");
                  data.element.append(text);
               } else {
                  data.element.remove();
               }
            }

            // fade others bars on one mouseouver
            chart.on('created', function(bar) {
               $('#chart-{$p['rand']} .ct-series')
                  .mouseover(function() {
                     $(this).parent().children().addClass('disable-animation');
                     $(this).addClass('mouseover');
                     $(this).siblings()
                        .addClass('notmouseover');

                     $('#chart-{$p['rand']} .ct-label')
                        .addClass('fade');
                  })
                  .mouseout(function() {
                     $(this).removeClass('mouseover');
                     $(this).siblings()
                        .removeClass('notmouseover');

                     $('#chart-{$p['rand']} .ct-label')
                        .removeClass('fade');
                  });
            });
         });
      });
JAVASCRIPT;
      $js = \Html::scriptBlock($js);

      return $html.$js;
   }


   /**
    * Display a widget with a donut chart
    * @see self::pie for params
    *
    * @return string html
    */
   public static function donut(array $params = []): string {
      return self::pie(array_merge($params, [
         'donut' => true,
      ]));
   }


   /**
    * Display a widget with a half donut chart
    * @see self::pie for params
    *
    * @return string html
    */
   public static function halfDonut(array $params = []): string {
      return self::donut(array_merge($params, [
         'half' => true,
      ]));
   }

   /**
    * Display a widget with a half pie chart
    * @see self::pie for params
    *
    * @return string html
    */
   public static function halfPie(array $params = []): string {
      return self::pie(array_merge($params, [
         'half' => true,
      ]));
   }


   /**
    * Display a widget with a bar chart (with single series)
    * @see self::getBarsGraph for params
    *
    * @return string html
    */
   public static function simpleBar(array $params = []): string {
      $default = [
         'data'        => [],
         'label'       => '',
         'alt'         => '',
         'color'       => '',
         'icon'        => '',
         'horizontal'  => false,
         'distributed' => true,
         'rand'        => mt_rand(),
      ];
      $params = array_merge($default, $params);
      $default_entry = [
         'url'    => '',
         'icon'   => '',
         'label'  => '',
         'number' => '',
      ];

      $labels = [];
      $series = [];
      $total = 0;
      foreach ($params['data'] as $entry) {
         if (!is_array($entry)) {
            continue;
         }
         $entry = array_merge($default_entry, $entry);
         $total+= $entry['number'];

         $labels[] = $entry['label'];
         $series[] = [
            'meta'  => $entry['label'],
            'value' => $entry['number'],
            'url'   => $entry['url'],
         ];
      }

      // chartist bar graphs are always multiple lines
      if (!$params['distributed']) {
         $series = [$series];
      }

      return self::getBarsGraph($params, $labels, $series);
   }


   /**
    * Display a widget with an horizontal bar chart
    * @see self::getBarsGraph for params
    *
    * @return string html
    */
   public static function simpleHbar(array $params = []): string {
      return self::simpleBar(array_merge($params, [
         'horizontal' => true,
      ]));
   }

   /**
    * @inheritdoc self::simpleHbar
    */
   public static function hbar(array $params = []): string {
      return self::simpleHbar($params);
   }


   /**
    * Display a widget with a multiple bars chart
    * @see self::getBarsGraph for params
    *
    * @return string html
    */
   public static function multipleBars(array $params = []): string {
      return self::getBarsGraph(
         array_merge($params, [
            'legend'   => true,
            'multiple' => true,
         ]),
         $params['data']['labels'],
         $params['data']['series']
      );
   }


   /**
    * Display a widget with a stacked multiple bars chart
    * @see self::getBarsGraph for params
    *
    * @return string html
    */
   public static function StackedBars(array $params = []): string {
      return self::multipleBars(array_merge($params, [
         'stacked' => true,
      ]));
   }


   /**
    * Display a widget with a bars chart
    *
    * @param array $params contains these keys:
    * - array  'data': represents the bars to display
    *    - string 'url': url to redirect when clicking on the bar
    *    - string 'label': title of the bar
    *    - int     'number': number of the bar
    * - string 'label': global title of the widget
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    * - bool   'horizontal': do we want an horizontal chart
    * - bool   'distributed': do we want a distributed chart (see https://gionkunz.github.io/chartist-js/examples.html#example-bar-distributed-series)
    * - bool   'legend': do we display a legend for the graph
    * - bool   'stacked': do we display multiple bart stacked or grouped
    * - bool   'use_gradient': gradient or generic palette
    * - int    'limit': the number of bars
    * @param array $labels title of the bars (if a single array is given, we have a single bar graph)
    * @param array $series values of the bar (if a single array is given, we have a single bar graph)
    *
    * @return string html of the widget
    */
   private static function getBarsGraph(
      array $params = [],
      array $labels = [],
      array $series = []): string {

      $defaults = [
         'label'        => '',
         'alt'          => '',
         'color'        => '',
         'icon'         => '',
         'legend'       => false,
         'multiple'     => false,
         'stacked'      => false,
         'horizontal'   => false,
         'distributed'  => false,
         'use_gradient' => false,
         'limit'        => 99999,
         'rand'         => mt_rand(),
      ];
      $p = array_merge($defaults, $params);

      $nb_series = count($series);
      $nb_labels = min($p['limit'], count($labels));
      if ($p['distributed']) {
         array_splice($labels, $nb_labels);
      } else {
         array_splice($labels, 0, -$nb_labels);
      }
      if ($p['multiple']) {
         foreach ($series as &$serie) {
            if (isset($serie['data'])) {
               array_splice($serie['data'], 0, -$nb_labels);
            }
         }
      } else {
         if ($p['distributed']) {
            array_splice($series, $nb_labels);
         } else {
            array_splice($series[0], 0, -$nb_labels);
         }
      }

      $json_labels = json_encode($labels);
      $json_series = json_encode($series);

      $fg_color   = \Toolbox::getFgColor($p['color']);
      $line_color = \Toolbox::getFgColor($p['color'], 10);

      $animation_duration = self::$animation_duration;

      $class = "bar";
      $class.= $p['horizontal'] ? " horizontal": "";
      $class.= $p['distributed'] ? " distributed": "";
      $class.= $nb_series <= 10 ? " tab10": "";
      $class.= $nb_series > 10 ? " tab20": "";

      $palette_style = "";
      if ($p['use_gradient']) {
         $nb_gradients = $p['distributed'] ? $nb_labels : $nb_series;
         $palette_style = self::getCssGradientPalette($p['color'], $nb_gradients, "#chart-{$p['rand']}");
      }

      $nodata = isset($p['data']['nodata']) && $p['data']['nodata']
                || count($series) == 0;
      $no_data_html = "";
      if ($nodata) {
         $no_data_html = "<span class='empty-card no-data'>
               <div>".__('No data found')."</div>
            <span>";
      }

      $html = <<<HTML
      <style>
      #chart-{$p['rand']} .ct-label {
         color: {$fg_color};
      }
      #chart-{$p['rand']} .ct-grid {
         stroke: {$line_color};
      }
      {$palette_style}
      </style>

      <div class="card g-chart $class"
            id="chart-{$p['rand']}"
            style="background-color: {$p['color']}; color: {$fg_color}">
         <div class="chart ct-chart">$no_data_html</div>
         <span class="main-label">{$p['label']}</span>
         <i class="main-icon {$p['icon']}"></i>
      </div>
HTML;

      $horizontal_options = "";
      $vertical_options   = "";
      $is_horizontal      = "false";
      if ($p['horizontal']) {
         $is_horizontal = "true";
         $horizontal_options = "
            horizontalBars: true,
            axisY: {
               offset: 100
            },
            axisX: {
               onlyInteger: true
            },
         ";
      } else {
         $vertical_options = "
            axisX: {
               offset: 50,
            },
            axisY: {
               onlyInteger: true
            },
         ";
      }

      $stack_options = "";
      if ($p['stacked']) {
         $stack_options = "
            stackBars: true,";
      }

      $distributed_options = "";
      if ($p['distributed']) {
         $distributed_options = "
            distributeSeries: true,";
      }

      $height = "calc(100% - 5px)";
      $legend_options = "";
      if ($p['legend']) {
         $height = "calc(100% - 40px)";
         $legend_options = "
            Chartist.plugins.legend(),";
      }

      $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Bar('#chart-{$p['rand']} .chart', {
            labels: {$json_labels},
            series: {$json_series},
         }, {
            width: '100%',
            height: '{$height}',
            seriesBarDistance: 10,
            chartPadding: 0,
            $distributed_options
            $horizontal_options
            $vertical_options
            $stack_options
            plugins: [
               $legend_options
               Chartist.plugins.tooltip({
                  appendToBody: true,
                  class: 'dashboard-tooltip'
               })
            ]
         });

         var is_horizontal = chart.options.horizontalBars;
         var nb_elements   = chart.data.labels.length;
         var bar_margin    = chart.options.seriesBarDistance;

         if (!chart.options.stackBars
             && chart.data.series.length > 0
             && chart.data.series[0].hasOwnProperty('data')) {
            nb_elements = nb_elements * chart.data.series.length;
            bar_margin = 2 + (chart.options.seriesBarDistance / 2);
         }

         chart.on('draw', function(data) {
            if (data.type === 'bar') {
               // set url redirecting on bar
               var url = _.get(data, 'series['+data.index+'].url')
                  || _.get(data, 'series.data['+data.index+'].url')
                  || _.get(data, 'series.url')
                  || "";
               if (url.length > 0) {
                  data.element.attr({
                     'data-clickable': true
                  });
                  data.element._node.onclick = function() {
                     if (!Dashboard.edit_mode) {
                        window.location = url;
                     }
                  }
               }

               var chart_height = data.chartRect.height();
               var chart_width = data.chartRect.width();

               var stroke_width = chart_width / nb_elements;
               if (is_horizontal) {
                  stroke_width = chart_height / nb_elements;
               }
               stroke_width -= bar_margin;
               data.element.attr({
                  'style': 'stroke-width: '+stroke_width+'px'
               });

               var axis_anim = 'y';
               if ({$is_horizontal}) {
                  axis_anim = 'x';
               }

               var animate_properties = {};
               animate_properties[axis_anim+'2'] = {
                  dur: {$animation_duration},
                  from: data[axis_anim+'1'],
                  to: data[axis_anim+'2']
               };
               data.element.animate(animate_properties);
            }
         });

         chart.on('created', function(bar) {
            $('#chart-{$p['rand']} .ct-series')
               .mouseover(function() {
                  $(this).siblings().children().css('stroke-opacity', "0.2");
               })
               .mouseout(function() {
                  $(this).siblings().children().css('stroke-opacity', "1");
               });
         });
      });
JAVASCRIPT;
      $js = \Html::scriptBlock($js);

      return $html.$js;
   }


   /**
    * Display a widget with a line chart (with single series)
    * @see self::getLinesGraph for params
    *
    * @return string html
    */
   public static function simpleLine(array $params = []): string {
      $default_entry = [
         'url'    => '',
         'icon'   => '',
         'label'  => '',
         'number' => '',
      ];

      $labels = [];
      $series = [];
      foreach ($params['data'] as $entry) {
         $entry = array_merge($default_entry, $entry);

         $labels[] = $entry['label'];
         $series[] = [
            'meta'  => $entry['label'],
            'value' => $entry['number'],
            'url'   => $entry['url'],
         ];
      }

      // chartist line graphs are always multiple lines
      $series = [$series];

      return self::getLinesGraph($params, $labels, $series);
   }


   /**
    * Display a widget with a area chart (with single serie)
    * @see self::getLinesGraph for params
    *
    * @return string html
    */
   public static function simpleArea(array $params = []): string {
      return self::simpleLine(array_merge($params, [
         'area' => true,
      ]));
   }


   /**
    * Display a widget with a multiple line chart (with multiple series)
    * @see self::getLinesGraph for params
    *
    * @return string html
    */
   public static function multipleLines(array $params = []): string {
      return self::getLinesGraph(
         array_merge($params, [
            'legend'   => true,
            'multiple' => true,
         ]),
         $params['data']['labels'],
         $params['data']['series']
      );
   }


   /**
    * Display a widget with a multiple area chart (with multiple series)
    * @see self::getLinesGraph for params
    *
    * @return string html
    */
   public static function multipleAreas(array $params = []): string {
      return self::multipleLines(array_merge($params, [
         'area' => true,
      ]));
   }


   /**
    * Display a widget with a lines chart
    *
    * @param array $params contains these keys:
    * - array  'data': represents the lines to display
    *    - string 'url': url to redirect when clicking on the line
    *    - string 'label': title of the line
    *    - int     'number': number of the line
    * - string 'label': global title of the widget
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    * - bool   'area': do we want an area chart
    * - bool   'legend': do we display a legend for the graph
    * - bool   'use_gradient': gradient or generic palette
    * - int    'limit': the number of lines
    * @param array $labels title of the lines (if a single array is given, we have a single line graph)
    * @param array $series values of the line (if a single array is given, we have a single line graph)
    *
    * @return string html of the widget
    */
   private static function getLinesGraph(
      array $params = [],
      array $labels = [],
      array $series = []): string {

      $defaults = [
         'data'         => [],
         'label'        => '',
         'alt'          => '',
         'color'        => '',
         'icon'         => '',
         'area'         => false,
         'legend'       => false,
         'multiple'     => false,
         'use_gradient' => false,
         'limit'        => 99999,
         'rand'         => mt_rand(),
      ];
      $p = array_merge($defaults, $params);

      $nb_series = count($series);
      $nb_labels = min($p['limit'], count($labels));
      array_splice($labels, 0, -$nb_labels);
      if ($p['multiple']) {
         foreach ($series as &$serie) {
            if (isset($serie['data'])) {
               array_splice($serie['data'], 0, -$nb_labels);
            }
         }
      } else {
         array_splice($series[0], 0, -$nb_labels);
      }

      $json_labels = json_encode($labels);
      $json_series = json_encode($series);

      $fg_color   = \Toolbox::getFgColor($p['color']);
      $line_color = \Toolbox::getFgColor($p['color'], 10);
      $class = "line";
      $class.= $p['area'] ? " area": "";
      $class.= $p['area'] ? " multiple": "";

      $animation_duration = self::$animation_duration;

      $palette_style = "";
      if (!$p['multiple'] || $p['use_gradient']) {
         $palette_style = self::getCssGradientPalette($p['color'], $nb_series, "#chart-{$p['rand']}");
      }

      $html = <<<HTML
      <style>
      #chart-{$p['rand']} .ct-label {
         color: {$fg_color};
      }

      #chart-{$p['rand']} .ct-grid {
         stroke: {$line_color};
      }
      {$palette_style}
      </style>

      <div class="card g-chart $class"
           id="chart-{$p['rand']}"
           style="background-color: {$p['color']}; color: {$fg_color}">
         <div class="chart ct-chart"></div>
         <span class="main-label">{$p['label']}</span>
         <i class="main-icon {$p['icon']}"></i>
      </div>
HTML;

      $area_options = "";
      if ($p['area']) {
         $area_options = "
            showArea: true,";
      }

      $height = "calc(100% - 1px)";
      $legend_options = "";
      if ($p['legend']) {
         $height = "calc(100% - 40px)";
         $legend_options = "
            Chartist.plugins.legend(),";
      }

      $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Line('#chart-{$p['rand']} .chart', {
            labels: {$json_labels},
            series: {$json_series},
         }, {
            width: '100%',
            height: '{$height}',
            fullWidth: true,
            chartPadding: {
               right: 40
            },
            {$area_options}
            plugins: [
               {$legend_options}
               Chartist.plugins.tooltip({
                  appendToBody: true,
                  class: 'dashboard-tooltip'
               })
            ]
         });

         // animation
         chart.on('draw', function(data) {
            if (data.type === 'line' || data.type === 'area') {
               data.element.animate({
                  d: {
                     begin: 300 * data.index,
                     dur: $animation_duration,
                     from: data.path.clone().scale(1, 0).translate(0, data.chartRect.height()).stringify(),
                     to: data.path.clone().stringify(),
                     easing: Chartist.Svg.Easing.easeOutQuint
                  }
               });
            }

            if (data.type === 'point') {
               // set url redirecting on line
               var url = _.get(data, 'series['+data.index+'].url')
                  || _.get(data, 'series.data['+data.index+'].url')
                  || _.get(data, 'series.url')
                  || '';

               if (url.length > 0) {
                  data.element.attr({
                     'data-clickable': true
                  });
                  data.element._node.onclick = function() {
                     if (!Dashboard.edit_mode) {
                        window.location = url;
                     }
                  }
               }
            }
         });

         // hide other lines when hovering a point
         chart.on('created', function(bar) {
            $('#chart-{$p['rand']} .ct-series .ct-point')
               .mouseover(function() {
                  $(this).parent(".ct-series")
                     .siblings().children()
                     .css('stroke-opacity', "0.05");
               })
               .mouseout(function() {
                  $(this).parent(".ct-series")
                     .siblings().children()
                     .css('stroke-opacity', "1");
               });
         });
      });
JAVASCRIPT;
      $js = \Html::scriptBlock($js);

      return $html.$js;
   }


   /**
    * Display an editable markdown widget
    *
    * @param array $params with these keys:
    * - string 'color': hex color
    * - string 'markdown_content': text content formatted with warkdown
    *
    * @return string html
    */
   public static function markdown(array $params = []): string {
      $default = [
         'color'             => '',
         'markdown_content'  => '',
      ];
      $p = array_merge($default, $params);

      // fix auto-escaping
      if (isset($p['markdown_content'])) {
         $p['markdown_content'] = \Html::cleanPostForTextArea($p['markdown_content']);
      }

      $ph       = __("Type markdown text here");
      $fg_color = \Toolbox::getFgColor($p['color']);
      $border_color = \Toolbox::getFgColor($p['color'], 10);
      $md       = new \Michelf\MarkdownExtra();
       // Prevent escaping as code is already escaped by GLPI sanityze
      $md->code_span_content_func = function ($code) { return $code; };
      $md->code_block_content_func = function ($code) { return $code; };

      $html = <<<HTML
      <div
         class="card markdown"
         style="background-color: {$p['color']}; color: {$fg_color}; border-color: {$border_color}">

         <div class="html_content">{$md->transform($p['markdown_content'])}</div>
         <textarea
            class="markdown_content"
            placeholder="{$ph}">{$p['markdown_content']}</textarea>

      </div>
HTML;

      return $html;
   }


   /**
    * Display an html table from a \Search result
    *
    * @param array $params contains these keys:
    * - string 'itemtype': Glpi oObject to search
    * - array  's_criteria': parameters to pass to the search engine (@see \Search::manageParams)
    * - string 'label': global title of the widget
    * - string 'url': link to the full search result
    * - string 'alt': tooltip
    * - string 'color': hex color of the widget
    * - string 'icon': font awesome class to display an icon side of the label
    * - string 'id': unique dom identifier
    * - int    'limit': the number of displayed lines
    *
    * @return string html of the widget
    */
   public static function searchShowList(array $params = []): string {
      $default = [
         'url'        => '',
         'label'      => '',
         'alt'        => '',
         'color'      => '',
         'icon'       => '',
         's_criteria' => '',
         'itemtype'   => '',
         'limit'      => $_SESSION['glpilist_limit'],
         'rand'       => mt_rand(),
      ];
      $p = array_merge($default, $params);

      $id = "search-table-".$p['rand'];

      $color = new Color($p['color']);
      $is_light = $color->isLight();

      $fg_color  = \Toolbox::getFgColor($p['color'], $is_light ? 65 : 40);
      $fg_color2 = \Toolbox::getFgColor($p['color'], 5);

      $href = strlen($p['url'])
         ? "href='{$p['url']}'"
         : "";

      // prepare search data
      $_GET['_in_modal'] = true;
      $params = [
         'criteria' => $p['s_criteria'],
         'reset'    => 'reset',
      ];

      ob_start();
      $params = \Search::manageParams($p['itemtype'], $params);
      // remove parts of search list
      $params = array_merge($params, [
         'showmassiveactions' => false,
         'dont_flush'         => true,
         'show_pager'         => false,
         'show_footer'        => false,
         'no_sort'            => true,
         'list_limit'         => $p['limit']
      ]);
      \Search::showList($p['itemtype'], $params);
      $search_result = ob_get_clean();

      $html = <<<HTML
      <style>
         #{$id} .tab_cadrehov th {
            background: {$fg_color2};
         }
      </style>
      <div
         class="card search-table"
         id="{$id}"
         style="background-color: {$p['color']}; color: {$fg_color}">
         <div class='table-container'>
            $search_result
         </div>
         <span class="main-label">
            <a {$href}>{$p['label']}</a>
         </span>
         <i class="main-icon {$p['icon']}"></i>
      </div>
HTML;

      return $html;
   }

   /**
    * Get a gradient palette for a given background color
    *
    * @param string $bgcolor the background color in hexadecimal format (Ex: #FFFFFF)
    * @param int $nb_series how much step in gradient we need
    * @param bool $revert direction of the gradient
    *
    * @return array with [
    *    'names' => [...]
    *    'colors' => [...]
    * ]
    */
   public static function getGradientPalette(
      string $bgcolor = "",
      int $nb_series = 1,
      bool $revert = true
   ) {
      if ($nb_series == 0) {
         return [
            'names'  => [],
            'colors' => [],
         ];
      }

      if ($nb_series == 1) {
         return [
            'names'  => ['a'],
            'colors' => [\Toolbox::getFgColor($bgcolor)],
         ];
      }

      $alphabet = range('a', 'z');
      $min_l = 20; // min for luminosity
      $max_l = 20; // max ...
      $min_s = 30; // min for saturation
      $max_s = 50; // max ...
      $step_l = (100 - ($min_l + $max_l)) / ($nb_series * 100);
      $step_s = (100 - ($min_s + $max_s)) / ($nb_series * 100);

      $color_instance = new Color($bgcolor);
      $hsl = $color_instance->getHsl();

      $names  = [];
      $colors = [];

      for ($i = 1; $i <= $nb_series; $i++) {
         $names[$i - 1] = $alphabet[$i - 1];

         // adjust luminosity
         $i_l_step = $i * $step_l + $min_l / 100;
         $hsl['L'] = min(1, $revert
            ? 1 - $i_l_step
            : $i_l_step);
         // adjust saturation
         if ($hsl['H'] != 0 && $hsl['H'] != 1) {
            $i_s_step = $i * $step_s + $min_s / 100;
            $hsl['S'] = min(1, $revert
               ? $i_s_step
               : 1 - $i_s_step);
         }

         $colors[$i - 1] = "#".Color::hslToHex($hsl);
      }

      return [
         'names'  => $names,
         'colors' => $colors,
      ];
   }


   /**
    * Generate a css ruleset for chartist given a starting background color
    * Based on @see self::getGradientPalette
    */
   public static function getCssGradientPalette(
      string $bgcolor = "",
      int $nb_series = 1,
      string $css_dom_parent = "",
      bool $revert = true
   ) {
      $palette = self::getGradientPalette($bgcolor, $nb_series, $revert);

      $series_names  = implode(',', $palette['names']);
      $series_colors = implode(',', $palette['colors']);

      $scss = new Compiler();
      $scss->addImportPath(GLPI_ROOT);

      $palette_css = $scss->compile("{$css_dom_parent} {
         \$ct-series-names: ({$series_names});
         \$ct-series-colors: ({$series_colors});

         @import 'css/chartist/generate';
      }");

      return $palette_css;
   }
}