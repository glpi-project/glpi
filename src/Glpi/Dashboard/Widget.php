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

namespace Glpi\Dashboard;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Debug\Profiler;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\Toolbox\MarkdownRenderer;
use Html;
use Line;
use Mexitek\PHPColors\Color;
use Plugin;
use Search;
use Symfony\Component\DomCrawler\Crawler;
use Toolbox;

use function Safe\ob_get_clean;
use function Safe\ob_start;
use function Safe\preg_match;

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

        Profiler::getInstance()->start(__METHOD__);
        $types = [
            'pie' => [
                'label'      => __("Pie"),
                'function'   => 'Glpi\\Dashboard\\Widget::pie',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/pie.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'legend'     => true,
                'labels'     => true,
                'width'      => 3,
                'height'     => 3,
            ],
            'donut' => [
                'label'      => __("Donut"),
                'function'   => 'Glpi\\Dashboard\\Widget::donut',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/donut.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'legend'     => true,
                'labels'     => true,
                'width'      => 3,
                'height'     => 3,
            ],
            'halfpie' => [
                'label'      => __("Half pie"),
                'function'   => 'Glpi\\Dashboard\\Widget::halfPie',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/halfpie.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'legend'     => true,
                'labels'     => true,
                'width'      => 3,
                'height'     => 2,
            ],
            'halfdonut' => [
                'label'      => __("Half donut"),
                'function'   => 'Glpi\\Dashboard\\Widget::halfDonut',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/halfdonut.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'legend'     => true,
                'labels'     => true,
                'width'      => 3,
                'height'     => 2,
            ],
            'bar' => [
                'label'      => __("Bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::simpleBar',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/bar.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'line' => [
                'label'      => Line::getTypeName(1),
                'function'   => 'Glpi\\Dashboard\\Widget::simpleLine',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
                'haspalette' => false,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'lines' => [
                'label'      => __("Multiple lines"),
                'function'   => 'Glpi\\Dashboard\\Widget::multipleLines',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'area' => [
                'label'      => __("Area"),
                'function'   => 'Glpi\\Dashboard\\Widget::simpleArea',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/area.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'areas' => [
                'label'      => __("Multiple areas"),
                'function'   => 'Glpi\\Dashboard\\Widget::multipleAreas',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/area.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 5,
                'height'     => 3,
            ],
            'bars' => [
                'label'      => __("Multiple bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::multipleBars',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/bar.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 5,
                'height'     => 3,
            ],
            'hBars' => [
                'label'      => __("Multiple horizontal bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::multipleHBars',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/hbar.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 5,
                'height'     => 3,
            ],
            'stackedbars' => [
                'label'      => __("Stacked bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::StackedBars',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/stacked.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'stackedHBars' => [
                'label'      => __("Horizontal stacked bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::stackedHBars',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/hstacked.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 4,
                'height'     => 3,
            ],
            'hbar' => [
                'label'      => __("Horizontal bars"),
                'function'   => 'Glpi\\Dashboard\\Widget::simpleHbar',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/hbar.png',
                'haspalette' => true,
                'gradient'   => true,
                'limit'      => true,
                'pointlbl'   => true,
                'legend'     => true,
                'width'      => 3,
                'height'     => 4,
            ],
            'bigNumber' => [
                'label'    => __("Big number"),
                'function' => 'Glpi\\Dashboard\\Widget::bigNumber',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/bignumber.png',
            ],
            'multipleNumber' => [
                'label'      => __("Multiple numbers"),
                'function'   => 'Glpi\\Dashboard\\Widget::multipleNumber',
                'image'      => $CFG_GLPI['root_doc'] . '/pics/charts/multiplenumbers.png',
                'limit'      => true,
                'haspalette' => true,
                'gradient'   => true,
                'width'      => 3,
                'height'     => 3,
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

        Profiler::getInstance()->stop(__METHOD__);
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

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color         = Toolbox::getFgColor($p['color']);
        $fg_hover_color   = Toolbox::getFgColor($p['color'], 15);
        $fg_hover_border  = Toolbox::getFgColor($p['color'], 30);

        $class = htmlescape(count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "");

        $href = strlen($p['url'])
            ? 'href="' . \htmlescape($p['url']) . '"'
            : "";

        $label = \htmlescape($p['label']);
        $icon = \htmlescape($p['icon']);

        if (empty($p['alt'])) {
            $p['alt'] = sprintf(
                __('%1$s %2$s'),
                $p['number'],
                $p['label'],
            );
        }
        $alt = \htmlescape($p['alt']);

        $id = Toolbox::slugify($p['id']);

        $html = <<<HTML
            <style>
                #{$id} {
                    background-color: {$bg_color};
                    color: {$fg_color};
                }

                #{$id}:hover {
                    background-color: {$fg_hover_color};
                    border: 1px solid {$fg_hover_border};
                }

                .theme-dark #{$id} {
                    background-color: {$fg_color};
                    color: {$bg_color};
                }

                .theme-dark #{$id}:hover {
                    background-color: {$fg_hover_color};
                    color: {$fg_color};
                    border: 1px solid {$fg_hover_border};
                }
            </style>
            <a {$href}
               id="{$id}"
               class="card big-number $class"
               data-bs-toggle="tooltip" data-bs-placement="top" title="{$alt}">
                <span class="content">$formatted_number</span>
                <div class="label">{$label}</div>
                <i class="main-icon {$icon}"></i>
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

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color = Toolbox::getFgColor($p['color']);

        $class = $p['class'];
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $numbers_html = "";
        $i = 0;
        foreach ($p['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $entry = array_merge($default_entry, $entry);

            $href = strlen($entry['url'])
                ? 'href="' . \htmlescape($entry['url']) . '"'
                : "";

            $color = isset($entry['color'])
                ? 'style="color: ' . \htmlescape($entry['color']) . ';"'
                : "";

            $color2 = isset($entry['color'])
                ? 'style="color: ' . \htmlescape(Toolbox::getFgColor($entry['color'], 20)) . ';"'
                : "";

            $formatted_number = Toolbox::shortenNumber($entry['number']);

            $icon = \htmlescape($entry['icon']);
            $label = \htmlescape($entry['label']);

            $numbers_html .= <<<HTML
                <a {$href} class="line line-{$i}">
                    <span class="content" {$color}>$formatted_number</span>
                    <i class="icon {$icon}" {$color2}></i>
                    <span class="label" {$color2}>{$label}</span>
                </a>
HTML;
            $i++;
        }

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata'];
        if ($nodata) {
            $numbers_html = "<span class='line empty-card no-data'>
               <span class='content'>
                  <i class='icon ti ti-alert-triangle'></i>
               </span>
               <span class='label'>" . __s('No data found') . "</span>
            <span>";
        }

        $rand = (int) $p['rand'];

        $palette_style = "";
        if ($p['use_gradient']) {
            $palette = self::getGradientPalette($p['color'], $i, false);
            foreach ($palette['names'] as $index => $letter) {
                $bgcolor   = $palette['colors'][$index];
                $bgcolor_h = Toolbox::getFgColor($bgcolor, 10);
                $color     = Toolbox::getFgColor($bgcolor);

                $palette_style .= "
                    #chart-{$rand} .line-$letter {
                        background-color: $bgcolor;
                        color: $color;
                    }

                    #chart-{$rand} .line-$letter:hover {
                        background-color: $bgcolor_h;
                        font-weight: bold;
                    }
                ";
            }
        }


        $label = \htmlescape($p['label']);
        $alt = \htmlescape($p['alt']);
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                {$palette_style}

                #chart-{$rand} {
                    background-color: {$bg_color};
                    color: {$fg_color};
                }

                .theme-dark #chart-{$rand} {
                    background-color: {$fg_color};
                    color: {$bg_color};
                }
            </style>

            <div class="card $class"
                 id="chart-{$rand}"
                 title="{$alt}">
                <div class="scrollable">
                    <div class="table">
                        {$numbers_html}
                    </div>
                </div>
                <span class="main-label">{$label}</span>
                <i class="main-icon {$icon}" style="color: {$fg_color}"></i>
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
            'legend'       => false,
            'labels'       => false,
            'donut'        => false,
            'half'         => false,
            'use_gradient' => false,
            'palette'      => '',
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($default, $params);
        $p['cache_key'] ??= $p['rand'];
        $default_entry = [
            'url'    => '',
            'icon'   => '',
            'label'  => '',
            'number' => '',
        ];

        $nb_slices = min($p['limit'], count($p['data']));
        array_splice($p['data'], $nb_slices);

        $nodata   = isset($p['data']['nodata']) && $p['data']['nodata'];

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color      = Toolbox::getFgColor($p['color']);
        $dark_bg_color = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color = Toolbox::getFgColor($p['color'], 40);

        $chart_id = Toolbox::slugify("chart-{$p['cache_key']}");

        $class = "pie";
        $class .= $p['half'] ? " half" : "";
        $class .= $p['donut'] ? " donut" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $no_data_html = "";
        if ($nodata) {
            $no_data_html = "<span class='empty-card no-data'>
               <div>" . __s('No data found') . "</div>
            <span>";
        }

        $nb_series = min($p['limit'], count($p['data']));

        $label = \htmlescape($p['label']);
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                #{$chart_id} {
                    background-color: {$bg_color};
                    color: {$fg_color}
                }

                .theme-dark #{$chart_id} {
                    background-color: {$dark_bg_color};
                    color: {$dark_fg_color};
                }
            </style>
            <div class="card g-chart {$class}" id="{$chart_id}">
                <div class="chart ct-chart">{$no_data_html}</div>
                <span class="main-label">{$label}</span>
                <i class="main-icon {$icon}"></i>
            </div>
HTML;

        if ($nodata) {
            return $html;
        }

        $series = [];
        $total = 0;
        foreach ($p['data'] as $entry) {
            $entry = array_merge($default_entry, $entry);
            $total += $entry['number'];

            $series[] = [
                'name'  => $entry['label'],
                'value' => $entry['number'],
                'url'   => $entry['url'],
            ];
        }

        $colors = self::getPalette($p['palette'], $nb_series);
        if ($p['use_gradient']) {
            $palette = self::getGradientPalette(
                $p['color'],
                $nb_series,
                false
            );
            $colors = $palette['colors'];
        }

        $options = [
            'animationDuration' => self::$animation_duration,
            'tooltip'           => [
                'trigger'      => 'item',
                'appendToBody' => true,
            ],
            'toolbox' => [
                'show'    => false,
                'feature' => [
                    'dataView'    => [
                        'show'     => true,
                        'readOnly' => true,
                        'title'    => __('View data'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                    'saveAsImage' => [
                        'show'  => true,
                        'title' => __('Save as image'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                ],
            ],
            'series' => [
                [
                    'type'              => 'pie',
                    'color'             => $colors,
                    'avoidLabelOverlap' => true,
                    'data'              => $series,
                    'radius'            => '80%',
                    'selectedMode'      => 'single',
                    'selectedOffset'    => 10,
                    'startAngle'        => 180,
                    'label'             => [
                        'show'  => false,
                        'color' => $fg_color,
                        'edgeDistance' => '25%',
                    ],
                    'labelLine'         => [
                        'showAbove' => true,
                    ],
                ],
            ],
        ];

        if ($p['legend']) {
            $options['legend'] = [
                'show' => true,
                'left' => 'left',
            ];
        }

        if ($p['donut']) {
            $options['series'][0]['radius'] = ['50%', '95%'];
            $options['series'][0]['itemStyle'] = [
                'borderRadius' => 2,
                'borderColor'  => 'rgba(255, 255, 255, 0.5)',
                'borderWidth'  => 2,
            ];

            // append total to center of donut
            $options['title'] = [
                'text'      => Toolbox::shortenNumber($total, 1, false),
                'left'      => 'center',
                'top'       => 'center',
                'textStyle' => [
                    'color'      => $fg_color,
                    'fontWeight' => 'lighter',
                ],
            ];

            if ($p['legend']) {
                $options['series'][0]['radius'] = ['30%', '60%'];
            }
        }

        if ($p['labels']) {
            $options['series'][0]['label']['show'] = true;
            $options['series'][0]['radius'] = '60%';

            if ($p['donut']) {
                $options['series'][0]['radius'] = ['30%', '60%'];
            }
        }

        if ($p['half']) {
            $options['series'][0]['center'] = ['50%', '100%'];
            $options['series'][0]['data'][] = [
                'name'  => '',
                'value' => $total,
            ];
        }

        $twig_params = [
            'chart_id' => $chart_id,
            'options' => $options,
        ];
        // language=Twig
        $js = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <script type="module">
                (async () => {
                    await import('/js/modules/Dashboard/Dashboard.js');

                    const target = GLPI.Dashboard.getActiveDashboard() ?
                        GLPI.Dashboard.getActiveDashboard().element.find('#{{ chart_id }} .chart')
                        : $('#{{ chart_id }} .chart');
                    const myChart = echarts.init(target[0]);
                    myChart.setOption({{ options|json_encode|raw }});
                    myChart
                        .on('click', function (params) {
                            const data_url = _.get(params, 'data.url', '');
                            if (data_url.length > 0) {
                                window.location.href = data_url;
                            }
                        });

                    target.on('mouseover', () => {
                        myChart.setOption({'toolbox': {'show': true}});
                    }).on('mouseout', () => {
                        myChart.setOption({'toolbox': {'show': false}});
                    });
                })();
            </script>
TWIG, $twig_params);


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

        // simple bar graphs are always multiple lines
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
     * - bool   'distributed': do we want a distributed chart
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
            'palette'      => '',
            'point_labels' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($defaults, $params);

        $p['cache_key'] ??= $p['rand'];
        $chart_id = Toolbox::slugify('chart_' . $p['cache_key']);

        $nb_labels = min($p['limit'], count($labels));

        if ($p['distributed']) {
            array_splice($labels, $nb_labels);
        } else {
            array_splice($labels, 0, -$nb_labels);
        }
        if ($p['multiple']) {
            foreach ($series as &$tmp_serie) {
                if (isset($tmp_serie['data'])) {
                    array_splice($tmp_serie['data'], 0, -$nb_labels);
                }
            }
        } else {
            if ($p['distributed']) {
                array_splice($series, $nb_labels);
            } else {
                array_splice($series[0], 0, -$nb_labels);
            }
            $series = [
                [
                    'data' => $series,
                ],
            ];
        }

        $nb_series = count($series);
        $palette = self::getPalette($p['palette'], $nb_series);
        if ($p['use_gradient']) {
            $nb_colors = $p['distributed'] ? $nb_labels : $nb_series;
            $palette = self::getGradientPalette(
                $p['color'],
                $nb_colors
            )['colors'];
        }

        $echarts_series = [];
        $serie_i = 0;
        foreach ($series as $value) {
            $serie = [
                'name'            => $value['name'] ?? "",
                'type'            => 'bar',
                'color'           => $palette[$serie_i] ?? Toolbox::getFgColor($p['color']),
                'data'            => $value['data'],
                'legendHoverLink' => true,
            ];

            if ($p['stacked']) {
                $serie['stack'] = 'total';
            }

            if ($p['point_labels']) {
                $serie['label'] = [
                    'show'      => true,
                    'overflow'  => 'truncate',
                    'color'    => $p['stacked'] ? '#000' : 'inherit',
                    'position' => $p['horizontal']
                        ? 'right'
                        : ($p['stacked'] ? 'insideTop' : 'top'),
                ];
            }

            $echarts_series[] = $serie;
            $serie_i++;
        }

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color        = Toolbox::getFgColor($p['color']);
        $dark_bg_color   = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = Toolbox::getFgColor($p['color'], 40);

        $class = "bar";
        $class .= $p['horizontal'] ? " horizontal" : "";
        $class .= $p['distributed'] ? " distributed" : "";
        $class .= $nb_series <= 10 ? " tab10" : "";
        $class .= $nb_series > 10 ? " tab20" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata']
                || count($series) == 0;
        $no_data_html = "";
        if ($nodata) {
            $no_data_html = "<span class='empty-card no-data'>
               <div>" . __s('No data found') . "</div>
            <span>";
        }

        $label = \htmlescape($p['label']);
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                #{$chart_id} {
                    background-color: {$bg_color};
                    color: {$fg_color}
                }

                .theme-dark #{$chart_id} {
                    background-color: {$dark_bg_color};
                    color: {$dark_fg_color};
                }
            </style>

            <div class="card g-chart $class" id="{$chart_id}">
                <div class="chart ct-chart">$no_data_html</div>
                <span class="main-label">{$label}</span>
                <i class="main-icon {$icon}"></i>
            </div>
HTML;

        $options = [
            'animationDuration' => self::$animation_duration,
            'tooltip'           => [
                'trigger'      => 'axis',
                'appendToBody' => true,
                'axisPointer'  => [
                    'type' => 'shadow',
                ],
            ],
            'grid'              => [
                'left'         => '20',
                'right'        => $p['horizontal'] ? '40' : '25',
                'bottom'       => '20',
                'top'          => $p['legend'] ? '40' : '20',
                'containLabel' => true,
            ],
            'toolbox' => [
                'show'    => false,
                'feature' => [
                    'dataView'    => [
                        'show'     => true,
                        'readOnly' => true,
                        'title'    => __('View data'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                    'saveAsImage' => [
                        'show'  => true,
                        'title' => __('Save as image'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                ],
            ],
            'series' => $echarts_series,
            'xAxis'  => [
                'type' => 'category',
                'data' => $labels,
                'splitLine' => [
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                    'show' => true,
                ],
            ],
            'yAxis' => [
                'type' => 'value',
                'splitLine' => [
                    'lineStyle' => [
                        'type' => 'dashed',
                    ],
                    'show' => true,
                ],
            ],
        ];

        if ($p['horizontal']) {
            $options['xAxis'] = array_merge($options['xAxis'], [
                'type' => 'value',
            ]);
            $options['yAxis'] = array_merge($options['yAxis'], [
                'type' => 'category',
                'data' => $labels,
                'axisLabel' => [
                    'overflow' => 'truncate',
                    'width'    => 100,
                    'rotate'   => 30,
                ],
            ]);
        }

        if ($p['legend']) {
            $options['legend'] = [
                'show' => true,
                'left' => 'left',
            ];
        }

        $twig_params = [
            'chart_id' => $chart_id,
            'options' => $options,
            'palette' => $palette,
            'distributed' => $p['distributed'],
            'horizontal' => $p['horizontal'],
        ];
        // language=Twig
        $js = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <script type="module">
                (async () => {
                    await import('/js/modules/Dashboard/Dashboard.js');

                    const target = GLPI.Dashboard.getActiveDashboard() ?
                        GLPI.Dashboard.getActiveDashboard().element.find('#{{ chart_id }} .chart')
                        : $('#{{ chart_id }} .chart');
                    const chart_options = {{ options|json_encode|raw }};
                    const palette = {{ palette|json_encode|raw }};
                    $.each(chart_options.series, function (index, serie) {
                        if ({{ distributed ? 'true' : 'false' }}) {
                            serie['itemStyle'] = {
                                ...serie['itemStyle'],
                                'color': (param) => palette[param.dataIndex]
                            }
                        }
                        serie['label'] = {
                            ...serie['label'],
                            'formatter': (param) => param.data.value == 0 ? '' : param.data.value
                        };
                    });
                    if ({{ horizontal ? 'true' : 'false' }}) {
                        chart_options['xAxis'] = {
                            ...chart_options['xAxis'],
                            'axisLabel': {
                                'formatter': (value) => {
                                    if (value < 1e3) {
                                        return value;
                                    } else if (value < 1e6) {
                                        return value / 1e3 + "K";
                                    } else {
                                        return value / 1e6 + "M";
                                    }
                                }
                            }
                        };
                    }

                    const myChart = echarts.init(target[0]);
                    myChart.setOption(chart_options);
                    myChart
                        .on('click', function (params) {
                            const data_url = _.get(params, 'data.url', '');
                            if (data_url.length > 0) {
                                window.location.href = data_url;
                            }
                        });

                    target.on('mouseover', () => {
                        myChart.setOption({'toolbox': {'show': true}});
                    }).on('mouseout', () => {
                        myChart.setOption({'toolbox': {'show': false}});
                    });
                })();
            </script>
TWIG, $twig_params);

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

        // simple line graphs are always multiple lines
        $series = [
            [
                'name' => $params['label'],
                'data'  => $series,
            ],
        ];

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
            'palette'      => '',
            'show_points'  => true,
            'point_labels' => false,
            'line_width'   => 4,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($defaults, $params);
        $p['cache_key'] ??= $p['rand'];

        $chart_id = Toolbox::slugify('chart_' . $p['cache_key']);

        $nb_series = count($series);
        $nb_labels = min($p['limit'], count($labels));
        array_splice($labels, 0, -$nb_labels);

        foreach ($series as &$tmp_serie) {
            if (isset($tmp_serie['data'])) {
                array_splice($tmp_serie['data'], 0, -$nb_labels);
            }
        }

        $palette = self::getPalette($p['palette'], $nb_series);
        if ($p['use_gradient']) {
            $palette = self::getGradientPalette(
                $p['color'],
                $nb_series
            )['colors'];
        }

        $echarts_series = [];
        $serie_i = 0;
        foreach ($series as $serie) {
            $echart_serie = [
                'name'            => $serie['name'],
                'type'            => 'line',
                'color'           => $palette[$serie_i] ?? Toolbox::getFgColor($p['color']),
                'data'            => $serie['data'],
                'smooth'          => 0.4,
                'lineStyle'       => [
                    'width'  => $p['line_width'],
                ],
                'symbol'         => 'none',
                'legendHoverLink' => true,
            ];

            if ($p['area']) {
                $echart_serie['areaStyle'] = [
                    'opacity' => 0.1,
                ];
            }

            if ($p['show_points']) {
                $echart_serie['symbolSize'] = 8;
            }

            if ($p['point_labels']) {
                $echart_serie['label'] = [
                    'show'      => true,
                    'distance'  => 1,
                    'color'     => 'inherit',
                ];
            }

            $echarts_series[] = $echart_serie;
            $serie_i++;
        }

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color        = Toolbox::getFgColor($p['color']);
        $dark_bg_color   = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = Toolbox::getFgColor($p['color'], 40);

        $class = "line";
        $class .= $p['area'] ? " area" : "";
        $class .= $p['multiple'] ? " multiple" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $label = \htmlescape($p['label']);
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                #{$chart_id} {
                    background-color: {$bg_color};
                    color: {$fg_color}
                }

                .theme-dark #{$chart_id} {
                    background-color: {$dark_bg_color};
                    color: {$dark_fg_color};
                }
            </style>

            <div class="card g-chart $class" id="{$chart_id}">
                <div class="chart ct-chart"></div>
                <span class="main-label">{$label}</span>
                <i class="main-icon {$icon}"></i>
            </div>
HTML;

        $options = [
            'animationDuration' => self::$animation_duration,
            'tooltip'           => [
                'trigger'      => 'axis',
                'appendToBody' => true,
            ],
            'grid'              => [
                'left'         => '3%',
                'right'        => '4%',
                'bottom'       => '3%',
                'containLabel' => true,
            ],
            'toolbox' => [
                'show'    => false,
                'feature' => [
                    'dataView'    => [
                        'show'     => true,
                        'readOnly' => true,
                        'title' => __('View data'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                    'saveAsImage' => [
                        'show'  => true,
                        'title' => __('Save as image'),
                        'emphasis' => [
                            'iconStyle' => [
                                'color' => $dark_fg_color,
                                'textBackgroundColor' => $dark_bg_color,
                                'textPadding' => 5,
                            ],
                        ],
                    ],
                ],
            ],
            'xAxis'  => [
                'type'        => 'category',
                'data'        => $labels,
                'boundaryGap' => false, // avoid spacing on the left and right
            ],
            'yAxis' => [
                'type' => 'value',
            ],
            'series' => $echarts_series,
        ];

        if ($p['legend']) {
            $options['legend'] = [
                'show' => true,
                'left' => 'left',
            ];
        }

        $twig_params = [
            'chart_id' => $chart_id,
            'options' => $options,
            'show_points' => $p['show_points'],
            'point_labels' => $p['point_labels'],
        ];
        // language=Twig
        $js = TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <script type="module">
                (async () => {
                    await import('/js/modules/Dashboard/Dashboard.js');

                    const target = GLPI.Dashboard.getActiveDashboard() ?
                        GLPI.Dashboard.getActiveDashboard().element.find('#{{ chart_id }} .chart')
                        : $('#{{ chart_id }} .chart');
                    const chart_options = {{ options|json_encode|raw }};

                    $.each(chart_options.series, function (index, serie) {
                        if ({{ show_points ? 'true' : 'false' }}) {
                            serie['symbol'] = (value) => value > 0 ? 'circle': 'none';
                        }
                        if ({{ point_labels ? 'true' : 'false' }}) {
                            serie['label']['formatter'] = (param) => param.data.value == 0 ? '': param.data.value;
                        }
                    });

                    const myChart = echarts.init(target[0]);
                    myChart.setOption(chart_options);
                    myChart
                        .on('click', function (params) {
                            const data_url = _.get(params, 'data.url', '');
                            if (data_url.length > 0) {
                                window.location.href = data_url;
                            }
                        });

                    target.on('mouseover', () => {
                        myChart.setOption({'toolbox': {'show': true}});
                    }).on('mouseout', () => {
                        myChart.setOption({'toolbox': {'show': false}});
                    });

                })();
            </script>
TWIG, $twig_params);

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

        $ph           = __s("Type markdown text here");

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color     = Toolbox::getFgColor($p['color']);
        $border_color = Toolbox::getFgColor($p['color'], 10);

        // Parse markdown
        $md = new MarkdownRenderer();

        $html_content = RichText::getSafeHtml($md->disableHeadings()->render($p['markdown_content']));
        $md_content   = \htmlescape($p['markdown_content']);

        $html = <<<HTML
      <div
         class="card markdown"
         style="background-color: {$bg_color}; color: {$fg_color}; border-color: {$border_color}">

         <div class="html_content">{$html_content}</div>
         <textarea
            class="markdown_content"
            placeholder="{$ph}">{$md_content}</textarea>

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
            'filters'    => [],
        ];
        $p = array_merge($default, $params);

        $p['label'] = \htmlescape($p['label']);

        $id = "search-table-" . $p['rand'];

        $color = new Color($p['color']);
        $is_light = $color->isLight();

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color  = Toolbox::getFgColor($p['color'], $is_light ? 65 : 40);
        $fg_color2 = Toolbox::getFgColor($p['color'], 5);

        $href = strlen($p['url'])
            ? \sprintf('href="%s"', \htmlescape($p['url']))
            : "";

        $class = count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        // prepare search data
        $_GET['_in_modal'] = true;
        $params = [
            'criteria' => $p['s_criteria'],
            'reset'    => 'reset',
        ];

        ob_start();
        $params = Search::manageParams($p['itemtype'], $params, false);
        // remove parts of search list
        $params = array_merge($params, [
            'showmassiveactions' => false,
            'dont_flush'         => true,
            'show_pager'         => false,
            'show_footer'        => false,
            'no_sort'            => true,
            'list_limit'         => $p['limit'],
        ]);
        Search::showList($p['itemtype'], $params);

        $crawler = new Crawler(ob_get_clean());
        $search_result = $crawler->filter('.search-results')->outerHtml();

        $label = \htmlescape($p['label']);
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                #{$id} table th {
                    background: {$fg_color2};
                }
            </style>
            <div class="card search-table {$class}"
                 id="{$id}"
                 style="background-color: {$bg_color}; color: {$fg_color}">
                <div class='table-container'>
                    $search_result
                </div>
                <span class="main-label">
                    <a {$href}>{$label}</a>
                </span>
                <i class="main-icon {$icon}"></i>
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

        $bg_color         = $p['color'];
        if (
            preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+),?\s*([\d\.]+)?\)/i', $bg_color) !== 1
            && preg_match('/^#[A-F0-9]+$/i', $bg_color) !== 1
        ) {
            $bg_color = '#CCCCCC';
        }
        $fg_color = Toolbox::getFgColor($p['color']);
        $bg_color_2 = Toolbox::getFgColor($p['color'], 5);

        $class = $p['class'];
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $label = \htmlescape($p['label']);

        $i = 0;
        $list_html = "";
        foreach ($p['data'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entry = array_merge($default_entry, $entry);

            $href = strlen($entry['url'])
                ? 'href="' . \htmlescape($entry['url']) . '"'
                : "";

            $author = strlen($entry['author'])
                ? "<i class='ti ti-user'></i>&nbsp;" . \htmlescape($entry['author'])
                : "";

            $content_size = strlen($entry['content']);
            $content = strlen($entry['content'])
                ? RichText::getEnhancedHtml($entry['content'])
                    . (
                        $content_size > 300
                        ? "<p class='read_more'><span class='read_more_button'>...</span></p>"
                        : ""
                    )
                : "";

            $date = \htmlescape($p['date']);

            $list_html .= <<<HTML
                <li class="line"><a {$href}>
                    <span class="label">{$label}</span>
                    <div class="content long_text">{$content}</div>
                    <span class="author">{$author}</span>
                    <span class="date">{$date}</span>
                </a></li>
HTML;
            $i++;
        }

        $nodata = isset($p['data']['nodata']) && $p['data']['nodata'];
        if ($nodata) {
            $list_html = "
                <span class='line empty-card no-data'>
                    <span class='content'>
                        <i class='icon ti ti-alert-triangle'></i>
                    </span>
                    <span class='label'>" . __s('No data found') . "</span>
                <span>
            ";
        }

        $view_all = strlen($p['url'])
            ? "<a href='" . \htmlescape($p['url']) . "'><i class='ti ti-eye' title='" . __s("See all") . "'></i></a>"
            : "";

        $rand = (int) $p['rand'];
        $icon = \htmlescape($p['icon']);
        $class = \htmlescape($class);

        $html = <<<HTML
            <style>
                #chart-{$rand} .line {
                    background-color: $bg_color_2;
                }

                #chart-{$rand} .ti-eye {
                    color: {$fg_color};
                }
            </style>

            <div class="card {$class}"
                 id="chart-{$rand}"
                 title="{$p['alt']}"
                 style="background-color: {$bg_color}; color: {$fg_color}">
                <div class='scrollable'>
                    <ul class='list'>
                        {$list_html}
                    </ul>
                </div>
                <span class="main-label">
                    {$label}
                    $view_all
                </span>
                <i class="main-icon {$icon}" style="color: {$fg_color}"></i>
            </div>
HTML;

        $js = <<<JAVASCRIPT
            $(function () {
                // init readmore controls
                read_more();

                // set dates in relative format
                $('#chart-{$rand} .date').each(function() {
                    var line_date = $(this).html();
                    var rel_date = relativeDate(line_date);

                    $(this).html(rel_date).attr('title', line_date);
                });
            });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        return $html . $js;
    }


    public static function getPalette(string $palette_name, int $nb_series = 0): array
    {
        $palette_obj = new Palette($palette_name);
        return $palette_obj->getColors($nb_series);
    }


    /**
     * Get a non-gradient palette based on the number of series
     * @param int $nb_series
     *
     * @return array of hex color strings
     */
    public static function getDefaultPalette(int $nb_series = 10): array
    {
        $palette = [];

        $palettes = Palette::getAllPalettes();
        $default10 = $palettes['tab10'];
        $default20 = $palettes['tab20'];

        while (count($palette) < $nb_series) {
            $current_nb_series = abs(count($palette) - $nb_series);
            $new_palette = $current_nb_series <= 10 ? $default10 : $default20;

            $palette = array_merge($palette, $new_palette);
        }

        return $palette;
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
            $names[$i - 1] = chr(97 + ($i - 1) % 26);

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
}
