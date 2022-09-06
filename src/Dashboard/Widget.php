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
use Glpi\RichText\RichText;
use Html;
use Mexitek\PHPColors\Color;
use Michelf\MarkdownExtra;
use Plugin;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\DomCrawler\Crawler;
use Search;
use Toolbox;

/**
 * Widget class
 **/
class Widget
{
    public static $animation_duration = 1000; // in millseconds


    /**
     * Define all possible widget types with their $labels/
     * This is used when adding a new card to display optgroups
     * This array can be hooked by plugins to add their own definitions.
     *
     * @return array
     */
    public static function getAllTypes(): array
    {
        global $CFG_GLPI;

        $types = [
            'pie' => [
                'label'    => __("Pie"),
                'function' => 'Glpi\\Dashboard\\Widget::pie',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/pie.png',
                'gradient' => true,
                'limit'    => true,
                'width'    => 3,
                'height'   => 3,
            ],
            'donut' => [
                'label'    => __("Donut"),
                'function' => 'Glpi\\Dashboard\\Widget::donut',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/donut.png',
                'gradient' => true,
                'limit'    => true,
                'width'    => 3,
                'height'   => 3,
            ],
            'halfpie' => [
                'label'    => __("Half pie"),
                'function' => 'Glpi\\Dashboard\\Widget::halfPie',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/halfpie.png',
                'gradient' => true,
                'limit'    => true,
                'width'    => 3,
                'height'   => 2,
            ],
            'halfdonut' => [
                'label'    => __("Half donut"),
                'function' => 'Glpi\\Dashboard\\Widget::halfDonut',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/halfdonut.png',
                'gradient' => true,
                'limit'    => true,
                'width'    => 3,
                'height'   => 2,
            ],
            'bar' => [
                'label'    => __("Bars"),
                'function' => 'Glpi\\Dashboard\\Widget::simpleBar',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/bar.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'line' => [
                'label'    => \Line::getTypeName(1),
                'function' => 'Glpi\\Dashboard\\Widget::simpleLine',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'lines' => [
                'label'    => __("Multiple lines"),
                'function' => 'Glpi\\Dashboard\\Widget::multipleLines',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'area' => [
                'label'    => __("Area"),
                'function' => 'Glpi\\Dashboard\\Widget::simpleArea',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/area.png',
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'areas' => [
                'label'    => __("Multiple areas"),
                'function' => 'Glpi\\Dashboard\\Widget::multipleAreas',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/area.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 5,
                'height'   => 3,
            ],
            'bars' => [
                'label'    => __("Multiple bars"),
                'function' => 'Glpi\\Dashboard\\Widget::multipleBars',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/bar.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 5,
                'height'   => 3,
            ],
            'hBars' => [
                'label'    => __("Multiple horizontal bars"),
                'function' => 'Glpi\\Dashboard\\Widget::multipleHBars',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/hbar.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 5,
                'height'   => 3,
            ],
            'stackedbars' => [
                'label'    => __("Stacked bars"),
                'function' => 'Glpi\\Dashboard\\Widget::StackedBars',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/stacked.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'stackedHBars' => [
                'label'    => __("Horizontal stacked bars"),
                'function' => 'Glpi\\Dashboard\\Widget::stackedHBars',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/hstacked.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 4,
                'height'   => 3,
            ],
            'hbar' => [
                'label'    => __("Horizontal bars"),
                'function' => 'Glpi\\Dashboard\\Widget::simpleHbar',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/hbar.png',
                'gradient' => true,
                'limit'    => true,
                'pointlbl' => true,
                'width'    => 3,
                'height'   => 4,
            ],
            'bigNumber' => [
                'label'    => __("Big number"),
                'function' => 'Glpi\\Dashboard\\Widget::bigNumber',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/bignumber.png',
            ],
            'multipleNumber' => [
                'label'    => __("Multiple numbers"),
                'function' => 'Glpi\\Dashboard\\Widget::multipleNumber',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/multiplenumbers.png',
                'limit'    => true,
                'gradient' => true,
                'width'    => 3,
                'height'   => 3,
            ],
            'markdown' => [
                'label'    => __("Editable markdown"),
                'function' => 'Glpi\\Dashboard\\Widget::markdown',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/markdown.png',
                'width'    => 4,
                'height'   => 4,
            ],
            'searchShowList' => [
                'label'    => __("Search result"),
                'function' => 'Glpi\\Dashboard\\Widget::searchShowList',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/table.png',
                'limit'    => true,
                'width'    => 5,
                'height'   => 4,
            ],
            'summaryNumbers' => [
                'label'    => __("Summary numbers"),
                'function' => 'Glpi\\Dashboard\\Widget::summaryNumber',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/summarynumber.png',
                'limit'    => true,
                'gradient' => true,
                'width'    => 4,
                'height'   => 2,
            ],
            'articleList' => [
                'label'    => __("List of articles"),
                'function' => 'Glpi\\Dashboard\\Widget::articleList',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/articles.png',
                'limit'    => true,
                'width'    => 3,
                'height'   => 4,
            ],
        ];

        $more_types = Plugin::doHookFunction(Hooks::DASHBOARD_TYPES);
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
     * - array  'filters': array of filter's id to apply classes on widget html
     *
     * @return string html of the widget
     */
    public static function bigNumber(array $params = []): string
    {
        $default = [
            'number'  => 0,
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'bn_' . mt_rand(),
            'filters' => [],
        ];
        $p = array_merge($default, $params);

        $formatted_number = Toolbox::shortenNumber($p['number']);
        $fg_color         = Toolbox::getFgColor($p['color']);
        $fg_hover_color   = Toolbox::getFgColor($p['color'], 15);
        $fg_hover_border  = Toolbox::getFgColor($p['color'], 30);

        $class = count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $href = strlen($p['url'])
         ? "href='{$p['url']}'"
         : "";

        $label = $p['label'];
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

         .theme-dark #{$p['id']} {
            background-color: {$fg_color};
            color: {$p['color']};
         }

         .theme-dark #{$p['id']}:hover {
            background-color: {$fg_hover_color};
            color: {$fg_color};
            border: 1px solid {$fg_hover_border};
         }
      </style>
      <a {$href}
         id="{$p['id']}"
         class="card big-number $class"
         title="{$p['alt']}">
         <span class="content">$formatted_number</span>
         <div class="label" title="{$label}">{$label}</div>
         <i class="main-icon {$p['icon']}"></i>
      </a>
HTML;

        return $html;
    }


    public static function summaryNumber(array $params = []): string
    {
        $params['class'] = 'summary-numbers';
        return self::multipleNumber($params);
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
     * - array  'filters': array of filter's id to apply classes on widget html
     *
     * @return string html of the widget
     */
    public static function multipleNumber(array $params = []): string
    {
        $default = [
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'color'        => '',
            'icon'         => '',
            'limit'        => 99999,
            'use_gradient' => false,
            'class'        => "multiple-numbers",
            'filters'      => [],
            'rand'         => mt_rand(),
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

        $fg_color = Toolbox::getFgColor($p['color']);

        $class = $p['class'];
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $alphabet = range('a', 'z');
        $numbers_html = "";
        $i = 0;
        foreach ($p['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $entry = array_merge($default_entry, $entry);

            $href = strlen($entry['url'])
            ? "href='{$entry['url']}'"
            : "";

            $color = isset($entry['color'])
            ? "style=\"color: {$entry['color']};\""
            : "";

            $color2 = isset($entry['color'])
            ? "style=\"color: " . Toolbox::getFgColor($entry['color'], 20) . ";\""
            : "";

            $formatted_number = Toolbox::shortenNumber($entry['number']);

            $numbers_html .= <<<HTML
            <a {$href} class="line line-{$alphabet[$i]}">
               <span class="content" {$color}>$formatted_number</span>
               <i class="icon {$entry['icon']}" {$color2}></i>
               <span class="label" {$color2}>{$entry['label']}</span>
            </a>
HTML;
            $i++;
        }

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata'];
        if ($nodata) {
            $numbers_html = "<span class='line empty-card no-data'>
               <span class='content'>
                  <i class='icon fas fa-alert-triangle'></i>
               </span>
               <span class='label'>" . __('No data found') . "</span>
            <span>";
        }

        $palette_style = "";
        if ($p['use_gradient']) {
            $palette = self::getGradientPalette($p['color'], $i, false);
            foreach ($palette['names'] as $index => $letter) {
                $bgcolor   = $palette['colors'][$index];
                $bgcolor_h = Toolbox::getFgColor($bgcolor, 10);
                $color     = Toolbox::getFgColor($bgcolor);

                $palette_style .= "
               #chart-{$p['rand']} .line-$letter {
                  background-color: $bgcolor;
                  color: $color;
               }

               #chart-{$p['rand']} .line-$letter:hover {
                  background-color: $bgcolor_h;
                  font-weight: bold;
               }
            ";
            }
        }

        $html = <<<HTML
      <style>
         {$palette_style}

         #chart-{$p['rand']} {
            background-color: {$p['color']};
            color: {$fg_color};
         }

         .theme-dark #chart-{$p['rand']} {
            background-color: {$fg_color};
            color: {$p['color']};
         }
      </style>

      <div class="card $class"
           id="chart-{$p['rand']}"
           title="{$p['alt']}">
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
     * - array  'filters': array of filter's id to apply classes on widget html
     *
     * @return string html of the widget
     */
    public static function pie(array $params = []): string
    {
        $default = [
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'color'        => '',
            'icon'         => '',
            'donut'        => false,
            'half'         => false,
            'use_gradient' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($default, $params);
        $p['cache_key'] = $p['cache_key'] ?? $p['rand'];
        $default_entry = [
            'url'    => '',
            'icon'   => '',
            'label'  => '',
            'number' => '',
        ];

        $nb_slices = min($p['limit'], count($p['data']));
        array_splice($p['data'], $nb_slices);

        $nodata   = isset($p['data']['nodata']) && $p['data']['nodata'];

        $fg_color      = Toolbox::getFgColor($p['color']);
        $dark_bg_color = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color = Toolbox::getFgColor($p['color'], 40);

        $chart_id = "chart-{$p['cache_key']}";

        $class = "pie";
        $class .= $p['half'] ? " half" : "";
        $class .= $p['donut'] ? " donut" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $no_data_html = "";
        if ($nodata) {
            $no_data_html = "<span class='empty-card no-data'>
               <div>" . __('No data found') . "</div>
            <span>";
        }

        $nb_series = min($p['limit'], count($p['data']));

        $palette_style = "";
        if ($p['use_gradient']) {
            $palette_style = self::getCssGradientPalette(
                $p['color'],
                $nb_series,
                ".dashboard #{$chart_id}",
                false
            );
        }

        $html = <<<HTML
      <style>
         #{$chart_id} {
            background-color: {$p['color']};
            color: {$fg_color}
         }

         .theme-dark #{$chart_id} {
            background-color: {$dark_bg_color};
            color: {$dark_fg_color};
         }

         #{$chart_id} .ct-label {
            fill: {$fg_color};
            color: {$fg_color};
         }

         .theme-dark #{$chart_id} .ct-label {
            fill: {$dark_fg_color};
            color: {$dark_fg_color};
         }

         {$palette_style}
      </style>
      <div>
         <div class="card g-chart {$class}"
            id="{$chart_id}">
            <div class="chart ct-chart">{$no_data_html}</div>
            <span class="main-label">{$p['label']}</span>
            <i class="main-icon {$p['icon']}"></i>
         </div>
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
            $total += $entry['number'];

            $labels[] = $entry['label'];
            $series[] = [
                'meta'  => $entry['label'],
                'value' => $entry['number'],
                'url'   => $entry['url'],
            ];
        }
        $total_txt = Toolbox::shortenNumber($total, 1, false);

        $labels = json_encode($labels);
        $series = json_encode($series);

        $chartPadding = 4;
        $height_divider = 1;
        $half_opts = "";
        if ($p['half']) {
            $half_opts = "
            startAngle: 270,
            total: " . ($total * 2) . ",
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
        $animation_duration = self::$animation_duration;

        $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Pie('#{$chart_id} .chart', {
            labels: {$labels},
            series: {$series},
         }, {
            width: 'calc(100% - 5px)',
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
               $('#{$chart_id} .ct-series')
                  .mouseover(function() {
                     $(this).parent().children().addClass('disable-animation');
                     $(this).addClass('mouseover');
                     $(this).siblings()
                        .addClass('notmouseover');

                     $('#{$chart_id} .ct-label')
                        .addClass('fade');
                  })
                  .mouseout(function() {
                     $(this).removeClass('mouseover');
                     $(this).siblings()
                        .removeClass('notmouseover');

                     $('#{$chart_id} .ct-label')
                        .removeClass('fade');
                  });
            });
         });
      });
JAVASCRIPT;
        $js = \Html::scriptBlock($js);

        return $html . $js;
    }


    /**
     * Display a widget with a donut chart
     * @see self::pie for params
     *
     * @return string html
     */
    public static function donut(array $params = []): string
    {
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
    public static function halfDonut(array $params = []): string
    {
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
    public static function halfPie(array $params = []): string
    {
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
    public static function simpleBar(array $params = []): string
    {
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
            $total += $entry['number'];

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
    public static function simpleHbar(array $params = []): string
    {
        return self::simpleBar(array_merge($params, [
            'horizontal' => true,
        ]));
    }

    /**
     * @inheritdoc self::simpleHbar
     */
    public static function hbar(array $params = []): string
    {
        return self::simpleHbar($params);
    }


    /**
     * Display a widget with a multiple bars chart
     * @see self::getBarsGraph for params
     *
     * @return string html
     */
    public static function multipleBars(array $params = []): string
    {
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
    public static function StackedBars(array $params = []): string
    {
        return self::multipleBars(array_merge($params, [
            'stacked' => true,
        ]));
    }


    /**
     * Display a widget with a horizontal stacked multiple bars chart
     * @see self::getBarsGraph for params
     *
     * @return string html
     */
    public static function stackedHBars(array $params = []): string
    {
        return self::StackedBars(array_merge($params, [
            'horizontal' => true,
        ]));
    }


    /**
     * Display a widget with a horizontal multiple bars chart
     * @see self::getBarsGraph for params
     *
     * @return string html
     */
    public static function multipleHBars(array $params = []): string
    {
        return self::multipleBars(array_merge($params, [
            'horizontal' => true,
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
     * - bool   'point_labels': display labels (for values) directly on graph
     * - int    'limit': the number of bars
     * - array  'filters': array of filter's id to apply classes on widget html
     * @param array $labels title of the bars (if a single array is given, we have a single bar graph)
     * @param array $series values of the bar (if a single array is given, we have a single bar graph)
     *
     * @return string html of the widget
     */
    private static function getBarsGraph(
        array $params = [],
        array $labels = [],
        array $series = []
    ): string {

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
            'point_labels' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($defaults, $params);

        $p['cache_key'] = $p['cache_key'] ?? $p['rand'];
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

        $fg_color        = Toolbox::getFgColor($p['color']);
        $line_color      = Toolbox::getFgColor($p['color'], 10);
        $dark_bg_color   = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = Toolbox::getFgColor($p['color'], 40);
        $dark_line_color = Toolbox::getFgColor($p['color'], 90);

        $animation_duration = self::$animation_duration;

        $chart_id = 'chart_' . $p['cache_key'];

        $class = "bar";
        $class .= $p['horizontal'] ? " horizontal" : "";
        $class .= $p['distributed'] ? " distributed" : "";
        $class .= $nb_series <= 10 ? " tab10" : "";
        $class .= $nb_series > 10 ? " tab20" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $palette_style = "";
        if ($p['use_gradient']) {
            $nb_gradients = $p['distributed'] ? $nb_labels : $nb_series;
            $palette_style = self::getCssGradientPalette($p['color'], $nb_gradients, "#{$chart_id}");
        }

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata']
                || count($series) == 0;
        $no_data_html = "";
        if ($nodata) {
            $no_data_html = "<span class='empty-card no-data'>
               <div>" . __('No data found') . "</div>
            <span>";
        }

        $legend_options = "";
        if ($p['legend']) {
            $legend_options = "
            Chartist.plugins.legend(),";
        }

        $html = <<<HTML
      <style>
      #{$chart_id} {
         background-color: {$p['color']};
         color: {$fg_color}
      }

      .theme-dark #{$chart_id} {
         background-color: {$dark_bg_color};
         color: {$dark_fg_color};
      }

      #{$chart_id} .ct-label {
         color: {$fg_color};
      }

      .theme-dark #{$chart_id} .ct-label {
         color: {$dark_fg_color};
      }

      #{$chart_id} .ct-grid {
         stroke: {$line_color};
      }

      .theme-dark #{$chart_id} .ct-grid {
         stroke: {$dark_line_color};
      }

      {$palette_style}
      </style>

      <div>
         <div class="card g-chart $class"
               id="{$chart_id}">
            <div class="chart ct-chart">$no_data_html</div>
            <span class="main-label">{$p['label']}</span>
            <i class="main-icon {$p['icon']}"></i>
         </div>
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

       // just to avoid issues with syntax coloring
        $point_labels = $p['point_labels'] ? "true" : "false;";
        $is_multiple  = $p['multiple'] ? "true" : "false;";

        $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Bar('#{$chart_id} .chart', {
            labels: {$json_labels},
            series: {$json_series},
         }, {
            width: '100%',
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
         var is_vertical   = !is_horizontal;
         var is_stacked    = chart.options.stackBars;
         var nb_elements   = chart.data.labels.length;
         var nb_series     = chart.data.series.length;
         var bar_margin    = chart.options.seriesBarDistance;
         var point_labels  = {$point_labels}
         var is_multiple   = {$is_multiple}

         if (!chart.options.stackBars
             && chart.data.series.length > 0
             && chart.data.series[0].hasOwnProperty('data')) {
            nb_elements = nb_elements * chart.data.series.length;
            bar_margin += 1;
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

               if (!chart.options.stackBars
                  && chart.data.series.length > 0 && is_vertical) {
                  stroke_width -= bar_margin * nb_elements;
               } else {
                  stroke_width -= bar_margin;
               }
               data.element.attr({
                  'style': 'stroke-width: '+stroke_width+'px'
               });

               var axis_anim = 'y';
               if ({$is_horizontal}) {
                  axis_anim = 'x';
               }

               var animate_properties = {
                  opacity: {
                     dur: {$animation_duration},
                     from: 0,
                     to: 1,
                     easing: Chartist.Svg.Easing.easeOutQuint
                  }
               };
               animate_properties[axis_anim+'2'] = {
                  dur: {$animation_duration},
                  from: data[axis_anim+'1'],
                  to: data[axis_anim+'2'],
                  easing: Chartist.Svg.Easing.easeOutQuint
               };
               data.element.animate(animate_properties);

               // append labels
               var display_labels = true;
               var labelX = 0;
               var labelY = 0;
               var value = data.element.attr('ct:value').toString();
               var text_anchor = 'middle';

               if (is_vertical) {
                  labelX = data.x2;
                  labelY = data.y2 + 15;

                  if (is_multiple) {
                     labelY = data.y2 - 5;
                  } else if (data.y1 - data.y2 < 18) {
                     display_labels = false;
                  }
               }

               if (is_horizontal) {
                  var word_width = value.length * 5 + 5;
                  var bar_width = 0;

                  if (value > 0) {
                     labelX = data.x2 - word_width;
                     bar_width = data.x2 - data.x1;
                  } else {
                     labelX = data.x2 + word_width;
                     bar_width = data.x1 - data.x2;
                  }
                  labelY = data.y2;

                  // don't display label if width too short
                  if (bar_width < word_width) {
                     display_labels = false;
                  }
               }

               if (is_stacked) {
                  labelY = data.y2 + 15;

                  // don't display label if height too short
                  if (is_horizontal) {
                     if (data.x2 - data.x1 < 15) {
                        display_labels = false;
                     }
                  } else {
                     if (data.y1 - data.y2 < 15) {
                        display_labels = false;
                     }
                  }
               }

               // don't display label if value is not relevant
               if (value == 0 || !point_labels) {
                  display_labels = false;
               }

               if (display_labels) {
                  label = new Chartist.Svg('text');
                  label.text(value);
                  label.addClass("ct-barlabel");
                  label.attr({
                     x: labelX,
                     y: labelY,
                     'text-anchor': text_anchor
                  });
                  return data.group.append(label);
               }
            }
         });

         chart.on('created', function(bar) {
            $('#{$chart_id} .ct-series')
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

        return $html . $js;
    }


    /**
     * Display a widget with a line chart (with single series)
     * @see self::getLinesGraph for params
     *
     * @return string html
     */
    public static function simpleLine(array $params = []): string
    {
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
    public static function simpleArea(array $params = []): string
    {
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
    public static function multipleLines(array $params = []): string
    {
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
    public static function multipleAreas(array $params = []): string
    {
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
     * - bool   'point_labels': display labels (for values) directly on graph
     * - int    'limit': the number of lines
     * - array  'filters': array of filter's id to apply classes on widget html
     * @param array $labels title of the lines (if a single array is given, we have a single line graph)
     * @param array $series values of the line (if a single array is given, we have a single line graph)
     *
     * @return string html of the widget
     */
    private static function getLinesGraph(
        array $params = [],
        array $labels = [],
        array $series = []
    ): string {

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
            'point_labels' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($defaults, $params);
        $p['cache_key'] = $p['cache_key'] ?? $p['rand'];

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

        $chart_id = 'chart_' . $p['cache_key'];

        $fg_color        = Toolbox::getFgColor($p['color']);
        $line_color      = Toolbox::getFgColor($p['color'], 10);
        $dark_bg_color   = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = Toolbox::getFgColor($p['color'], 40);
        $dark_line_color = Toolbox::getFgColor($p['color'], 90);

        $class = "line";
        $class .= $p['area'] ? " area" : "";
        $class .= $p['multiple'] ? " multiple" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $animation_duration = self::$animation_duration;

        $palette_style = "";
        if (!$p['multiple'] || $p['use_gradient']) {
            $palette_style = self::getCssGradientPalette($p['color'], $nb_series, "#{$chart_id}");
        }

        $pointlabels_plugins = "";
        if ($p['point_labels']) {
            $pointlabels_plugins = ",
            Chartist.plugins.ctPointLabels({
               textAnchor: 'middle',
               labelInterpolationFnc: function(value) {
                  if (value == undefined) {
                     return ''
                  }
                  return value;
               }
            })";
        }

        $legend_options = "";
        if ($p['legend']) {
            $legend_options = "
            Chartist.plugins.legend(),";
        }

        $html = <<<HTML
      <style>

      #{$chart_id} {
         background-color: {$p['color']};
         color: {$fg_color}
      }

      .theme-dark #{$chart_id} {
         background-color: {$dark_bg_color};
         color: {$dark_fg_color};
      }

      #{$chart_id} .ct-label {
         color: {$fg_color};
      }

      .theme-dark #{$chart_id} .ct-label {
         color: {$dark_fg_color};
      }

      #{$chart_id} .ct-grid {
         stroke: {$line_color};
      }

      .theme-dark #{$chart_id} .ct-grid {
         stroke: {$dark_line_color};
      }

      #{$chart_id} .ct-circle {
         stroke: {$p['color']};
         stroke-width: 3;
      }
      #{$chart_id} .ct-circle + .ct-label {
         stroke: {$p['color']};
      }
      {$palette_style}
      </style>

      <div>
          <div class="card g-chart $class"
               id="{$chart_id}">
             <div class="chart ct-chart"></div>
             <span class="main-label">{$p['label']}</span>
             <i class="main-icon {$p['icon']}"></i>
          </div>
      </div>
HTML;

        $area_options = "";
        if ($p['area']) {
            $area_options = "
            showArea: true,";
        }

        $js = <<<JAVASCRIPT
      $(function () {
         var chart = new Chartist.Line('#{$chart_id} .chart', {
            labels: {$json_labels},
            series: {$json_series},
         }, {
            width: '100%',
            fullWidth: true,
            chartPadding: {
               right: 40
            },
            axisY: {
               labelInterpolationFnc: function(value) {
                  if (value < 1e3) {
                     // less than 1K
                     return value;
                  } else if (value < 1e6) {
                     // More than 1k, less than 1M
                     return value / 1e3 + "K";
                  } else {
                     // More than 1M
                     return value / 1e6 + "M";
                  }
               },
            },
            {$area_options}
            plugins: [
               {$legend_options}
               Chartist.plugins.tooltip({
                  appendToBody: true,
                  class: 'dashboard-tooltip',
                  pointClass: 'ct-circle'
               })
               {$pointlabels_plugins}
            ]
         });

         chart.on('draw', function(data) {
            // animation
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
               var clickable = url.length > 0;

               var circle = new Chartist.Svg('circle', {
                  cx: [data.x],
                  cy: [data.y],
                  r: data.value.y > 0 ? [5] : [0],
                  "ct:value": data.value.y,
                  "data-clickable": clickable
               }, 'ct-circle');
               var circle = data.element.replace(circle);

               if (clickable) {
                  circle.getNode().onclick = function() {
                     if (!Dashboard.edit_mode) {
                        window.location = url;
                     }
                  }
               }
            }
         });

         // hide other lines when hovering a point
         chart.on('created', function(bar) {
            $('#{$chart_id} .ct-series .ct-circle, #{$chart_id} .ct-series .ct-circle + .ct-label')
               .mouseover(function() {
                  $(this)
                     .attr('r', "9")
                     .parent(".ct-series")
                     .siblings().children()
                     .css('stroke-opacity', "0.05")
                     .filter(".ct-circle, .ct-label").css('fill-opacity', "0.1");
               })
               .mouseout(function() {
                  $(this)
                     .attr('r', "5")
                     .parent(".ct-series")
                     .siblings().children()
                     .css('stroke-opacity', "1")
                     .filter(".ct-circle, .ct-label").css('fill-opacity', "1");
               });
         });
      });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        return $html . $js;
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
    public static function markdown(array $params = []): string
    {
        $default = [
            'color'             => '',
            'markdown_content'  => '',
        ];
        $p = array_merge($default, $params);

       // fix auto-escaping
        if (isset($p['markdown_content'])) {
            $p['markdown_content'] = \Html::cleanPostForTextArea($p['markdown_content']);
        }

        $ph           = __("Type markdown text here");
        $fg_color     = Toolbox::getFgColor($p['color']);
        $border_color = Toolbox::getFgColor($p['color'], 10);
        $md           = new MarkdownExtra();
       // Prevent escaping as code is already escaped by GLPI sanityze
        $md->code_span_content_func  = function ($code) {
            return $code;
        };
        $md->code_block_content_func = function ($code) {
            return $code;
        };

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
     * - array  'filters': array of filter's id to apply classes on widget html
     *
     * @return string html of the widget
     */
    public static function searchShowList(array $params = []): string
    {
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
            'filters'      => [],
        ];
        $p = array_merge($default, $params);

        $id = "search-table-" . $p['rand'];

        $color = new Color($p['color']);
        $is_light = $color->isLight();

        $fg_color  = Toolbox::getFgColor($p['color'], $is_light ? 65 : 40);
        $fg_color2 = Toolbox::getFgColor($p['color'], 5);

        $href = strlen($p['url'])
         ? "href='{$p['url']}'"
         : "";

        $class = count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

       // prepare search data
        $_GET['_in_modal'] = true;
        $params = [
            'criteria' => $p['s_criteria'],
            'reset'    => 'reset',
        ];

        ob_start();
        $params = Search::manageParams($p['itemtype'], $params);
       // remove parts of search list
        $params = array_merge($params, [
            'showmassiveactions' => false,
            'dont_flush'         => true,
            'show_pager'         => false,
            'show_footer'        => false,
            'no_sort'            => true,
            'list_limit'         => $p['limit']
        ]);
        Search::showList($p['itemtype'], $params);

        $crawler = new Crawler(ob_get_clean());
        $search_result = $crawler->filter('.search-results')->outerHtml();

        $html = <<<HTML
      <style>
         #{$id} .tab_cadrehov th {
            background: {$fg_color2};
         }
      </style>
      <div
         class="card search-table {$class}"
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


    public static function articleList(array $params): string
    {
        $default = [
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'url'          => '',
            'color'        => '',
            'icon'         => '',
            'limit'        => 99999,
            'class'        => "articles-list",
            'rand'         => mt_rand(),
            'filters'      => [],
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
        $fg_color = Toolbox::getFgColor($p['color']);
        $bg_color_2 = Toolbox::getFgColor($p['color'], 5);

        $class = $p['class'];
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $i = 0;
        $list_html = "";
        foreach ($p['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entry = array_merge($default_entry, $entry);

            $href = strlen($entry['url'])
            ? "href='{$entry['url']}'"
            : "";

            $author = strlen($entry['author'])
            ? "<i class='fas fa-user'></i>&nbsp;{$entry['author']}"
            : "";

            $content_size = strlen($entry['content']);
            $content = strlen($entry['content'])
            ? RichText::getEnhancedHtml($entry['content']) .
              ($content_size > 300
               ? "<p class='read_more'><span class='read_more_button'>...</span></p>"
               : ""
              )
             : "";

            $list_html .= <<<HTML
            <li class="line"><a {$href}>
               <span class="label">{$entry['label']}</span>
               <div class="content long_text">{$content}</div>
               <span class="author">$author</span>
               <span class="date">{$entry['date']}</span>
            </a></li>
HTML;
            $i++;
        }

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata'];
        if ($nodata) {
            $list_html = "<span class='line empty-card no-data'>
            <span class='content'>
               <i class='icon fas fa-exclamation-triangle'></i>
            </span>
            <span class='label'>" . __('No data found') . "</span>
         <span>";
        }

        $view_all = strlen($p['url'])
         ? "<a href='{$p['url']}'><i class='fas fa-eye' title='" . __("See all") . "'></i></a>"
         : "";

        $html = <<<HTML
      <style>
         #chart-{$p['rand']} .line {
            background-color: $bg_color_2;
         }

         #chart-{$p['rand']} .fa-eye {
            color: {$fg_color};
         }
      </style>

      <div class="card {$class}"
           id="chart-{$p['rand']}"
           title="{$p['alt']}"
           style="background-color: {$p['color']}; color: {$fg_color}">
         <div class='scrollable'>
            <ul class='list'>
            {$list_html}
   </ul>
         </div>
         <span class="main-label">
            {$p['label']}
            $view_all
         </span>
         <i class="main-icon {$p['icon']}" style="color: {$fg_color}"></i>
      </div>
HTML;

        $js = <<<JAVASCRIPT
      $(function () {
         // init readmore controls
         read_more();

         // set dates in relative format
         $('#chart-{$p['rand']} .date').each(function() {
            var line_date = $(this).html();
            var rel_date = relativeDate(line_date);

            $(this).html(rel_date).attr('title', line_date);
         });
      });
JAVASCRIPT;
        $js = \Html::scriptBlock($js);

        return $html . $js;
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
                'colors' => [Toolbox::getFgColor($bgcolor)],
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

            $colors[$i - 1] = "#" . Color::hslToHex($hsl);
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
        global $GLPI_CACHE;

        $palette = self::getGradientPalette($bgcolor, $nb_series, $revert);

        $series_names  = implode(',', $palette['names']);
        $series_colors = implode(',', $palette['colors']);

        $hash = sha1($css_dom_parent . $series_names . $series_colors);
        if (($palette_css = $GLPI_CACHE->get($hash)) !== null) {
            return $palette_css;
        }

        $scss = new Compiler();
        $generate_scss_path = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            realpath(GLPI_ROOT . '/css/includes/components/chartist/_generate.scss')
        );
        $result = $scss->compileString(
            "{$css_dom_parent} {
            \$ct-series-names: ({$series_names});
            \$ct-series-colors: ({$series_colors});

            @import '{$generate_scss_path}';
         }",
            dirname($generate_scss_path)
        );
        $palette_css = $result->getCss();

        $GLPI_CACHE->set($hash, $palette_css);

        return $palette_css;
    }
}
