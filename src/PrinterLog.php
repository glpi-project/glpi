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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset;
use Glpi\Dashboard\Widget;
use Safe\DateTime;

use function Safe\strtotime;

/**
 * Store printer metrics
 */
class PrinterLog extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = false;

    public static function getTypeName($nb = 0)
    {
        return __('Page counters');
    }

    public static function getIcon()
    {
        return 'ti ti-chart-line';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        $array_ret = [];

        /** @var Printer|Asset $item */
        if (in_array($item::class, $CFG_GLPI['printer_types'])) {
            $cnt = countElementsInTable([static::getTable()], [static::$items_id => $item->getField('id')]);
            $array_ret[] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $cnt, $item::getType());
        }
        return $array_ret;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        /** @var Printer|Asset $item */
        if (in_array($item::class, $CFG_GLPI['printer_types']) && $item->getID() > 0) {
            $printerlog = new self();
            $printerlog->showMetrics($item);
            return true;
        }
        return false;
    }

    /**
     * Get metrics
     *
     * @param array|Printer|Asset $printers Printer instance
     * @param array         $user_filters User filters
     * @param string        $interval     Date interval string (e.g. 'P1Y' for 1 year)
     * @param DateTime|null $start_date   Start date for the metrics range
     * @param DateTime      $end_date     End date for the metrics range
     * @param string        $format       Format for the metrics data ('dynamic', 'daily', 'weekly', 'monthly', 'yearly')
     *
     * @return array An array of printer metrics data
     */
    final public static function getMetrics(
        array|Printer|Asset $printers,
        array $user_filters = [],
        string $interval = 'P1Y',
        ?\DateTime $start_date = null,
        \DateTime $end_date = new DateTime(),
        string $format = 'dynamic'
    ): array {
        global $DB;

        if ($printers && !is_array($printers)) {
            $printers = [$printers];
        }

        if (!$start_date) {
            $start_date = new DateTime(Session::getCurrentTime());
            $start_date->sub(new DateInterval($interval));
        }

        $filters = [
            ['date' => ['>=', $start_date->format('Y-m-d')]],
            ['date' => ['<=', $end_date->format('Y-m-d')]],
        ];
        $filters = array_merge($filters, $user_filters);

        $series = [];
        if (count($printers) > 1) {
            foreach ($printers as $printer) {
                $series += self::getMetrics(
                    $printer,
                    $user_filters,
                    $interval,
                    $start_date,
                    $end_date,
                    $format
                );
            }
        } else {
            $printer = $printers[0];

            $iterator = $DB->request([
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'itemtype' => $printer::class,
                    'items_id'  => $printer->fields['id'],
                ] + $filters,
                'ORDER'  => 'date ASC',
            ]);

            $series = iterator_to_array($iterator, false);

            if ($format == 'dynamic') {
                // Reduce the data to 25 points
                $count = count($series);
                $max_size = 25;
                if ($count > $max_size) {
                    // Keep one row every X entry using modulo
                    $modulo = round($count / $max_size);
                    $series = array_filter(
                        $series,
                        fn($k) => (($count - ($k + 1)) % $modulo) == 0,
                        ARRAY_FILTER_USE_KEY
                    );
                }
            } else {
                $formats = [
                    'daily' => 'Ymd', // Reduce the data to one point per day max
                    'weekly' => 'YoW', // Reduce the data to one point per week max
                    'monthly' => 'Ym', // Reduce the data to one point per month max
                    'yearly' => 'Y', // Reduce the data to one point per year max
                ];

                $series = array_filter(
                    $series,
                    function ($k) use ($series, $format, $formats) {
                        if (!isset($series[$k + 1])) {
                            return true;
                        }

                        $current_date = date($formats[$format], strtotime($series[$k]['date']));
                        $next_date = date($formats[$format], strtotime($series[$k + 1]['date']));
                        return $current_date !== $next_date;
                    },
                    ARRAY_FILTER_USE_KEY
                );
            }

            $series = [$printer->getID() => array_values($series)];
        }

        return $series;
    }

    /**
     * Display form for agent
     *
     * @param Printer|Asset $printer Printer instance
     */
    public function showMetrics(Printer|Asset $printer)
    {
        $printers = array_map(
            fn($id) => Printer::getById($id),
            array_reduce(array_merge(
                explode(',', $_GET['compare_printers'] ?? ''),
                [$printer->getID()]
            ), fn($acc, $id) => !empty($id) && !in_array($id, $acc, false) ? array_merge($acc, [$id]) : $acc, [])
        );
        $compare_printer_stat = $_GET['compare_printer_stat'] ?? 'total_pages';
        $is_comparison = count($printers) > 1;

        $raw_metrics = [];
        $format = $_GET['date_format'] ?? 'dynamic';
        if (isset($_GET['date_interval'])) {
            $raw_metrics = self::getMetrics(
                $printers,
                interval: $_GET['date_interval'],
                format: $format,
            );
        } elseif (isset($_GET['date_start']) && isset($_GET['date_end'])) {
            $raw_metrics = self::getMetrics(
                $printers,
                start_date: new DateTime($_GET['date_start']),
                end_date: new DateTime($_GET['date_end']),
                format: $format,
            );
        } else {
            $raw_metrics = self::getMetrics(
                $printers,
                format: $format,
            );
        }

        // build graph data
        $params = [
            'label'         => static::getTypeName(),
            'icon'          => Printer::getIcon(),
            'apply_filters' => [],
        ];

        $series = [];
        $labels = [];

        // Formatter to display the date (months names) in the correct language
        // Dates will be displayed as "d MMM YYYY":
        // d = short day number (1, 12, ...)
        // MMM = short month name (jan, feb, ...)
        // YYYY = full year (2021, 2022, ...)
        // Note that PHP use ISO 8601 Date Output here which is different from
        // the "Constants for PHP Date Output" used in others functions
        // See https://framework.zend.com/manual/1.12/en/zend.date.constants.html#zend.date.constants.selfdefinedformats
        $fmt = new IntlDateFormatter(
            $_SESSION['glpilanguage'] ?? 'en_GB',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            null,
            'd MMM YYYY'
        );

        // Adds missing dates to the labels array and null values to the series data array
        // for comparison printers if the date is not present in the metrics array.
        foreach ($raw_metrics as $printer_id => $metrics) {
            foreach ($metrics as $metric) {
                if (!in_array($metric['date'], $labels)) {
                    $labels[] = $metric['date'];
                    if ($is_comparison) {
                        foreach ($printers as $printer) {
                            $series[$printer->getID()]['data'][] = null;
                        }
                    }
                }
            }
        }

        // Sort the labels array
        sort($labels);

        // Loops through the raw metrics and creates a series array for each printer or metric.
        // If $is_comparison is true, it sets the name and data for the comparison printer.
        // Otherwise, it sets the name and data for each metric key with a positive value.
        foreach ($raw_metrics as $printer_id => $metrics) {
            if ($is_comparison) {
                $series[$printer_id]['name'] = Printer::getById($printer_id)->fields['name'];
            }

            // Keep values if at least 1 label is greater than 0
            $valuesum = array_sum($metrics);
            foreach ($metrics as $metric) {
                if ($is_comparison) {
                    $series[$printer_id]['data'][array_search($metric['date'], $labels, false)] = $metric[$compare_printer_stat];
                } else {
                    foreach ($metric as $key => $value) {
                        $label = static::getLabelFor($key);
                        if ($label && $valuesum > 0) {
                            $series[$key]['name'] = $label;
                            $series[$key]['data'][] = $value;
                        }
                    }
                }
            }
        }

        // Loops through the series and replace null values with the previous value
        if ($is_comparison) {
            foreach ($series as $key => $data) {
                $previous_value = null;
                foreach ($data['data'] as $k => $value) {
                    if ($value === null) {
                        $series[$key]['data'][$k] = $previous_value;
                    } else {
                        $previous_value = $value;
                    }
                }
            }
        }
        // If the metric has a value of 0 for all dates, remove it from the data set
        foreach ($series as $key => $value) {
            if (array_sum($value['data']) == 0) {
                unset($series[$key]);
            }
        }
        $bar_conf = [
            'data'  => [
                'labels' => array_map(fn($date) => $fmt->format(new DateTime($date)), $labels), // Format the labels array
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
            'color' => '#ffffff',
            'distributed' => false,
            'show_points' => true,
            'line_width'  => 2,
        ];

        // display the printer graph buttons component
        TemplateRenderer::getInstance()->display('components/printer_graph_buttons.html.twig', [
            'start_date' => $_GET['date_start'] ?? '',
            'end_date'   => $_GET['date_end'] ?? '',
            'interval'   => $_GET['date_interval'] ?? 'P1Y',
            'format'     => $format,
            'export_url' => '/front/printerlogcsv.php?' . Toolbox::append_params([
                'id' => array_map(fn($printer) => $printer->getID(), $printers),
                'start' => $_GET['date_start'] ?? '',
                'end'   => $_GET['date_end'] ?? '',
                'interval'   => $_GET['date_interval'] ?? 'P1Y',
                'format'     => $format,
                'statistic' => $compare_printer_stat,
            ]),
            'compare_printers' => array_map(fn($printer) => $printer->getID(), $printers),
            'compare_printer_stat' => $compare_printer_stat,
        ]);

        // display graph
        echo "<div class='dashboard printer_barchart pt-2' data-testid='pages_barchart'>";
        echo Widget::multipleAreas($bar_conf);
        echo "</div>";
    }

    /**
     * Get the label for a given column of glpi_printerlogs.
     * To be used when displaying the printed pages graph.
     *
     * @param string $key
     *
     * @return null|string null if the key didn't match any valid field
     */
    public static function getLabelFor($key): ?string
    {
        switch ($key) {
            case 'total_pages':
                return __('Total pages');
            case 'bw_pages':
                return __('Black & White pages');
            case 'color_pages':
                return __('Color pages');
            case 'scanned':
                return __('Scans');
            case 'rv_pages':
                return __('Recto/Verso pages');
            case 'prints':
                return __('Prints');
            case 'bw_prints':
                return __('Black & White prints');
            case 'color_prints':
                return __('Color prints');
            case 'copies':
                return __('Copies');
            case 'bw_copies':
                return __('Black & White copies');
            case 'color_copies':
                return __('Color copies');
            case 'faxed':
                return __('Fax');
        }

        return null;
    }
}
