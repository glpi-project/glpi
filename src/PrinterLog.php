<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Dashboard\Widget;

/**
 * Store printer metrics
 */
class PrinterLog extends CommonDBChild
{
    public static $itemtype        = 'Printer';
    public static $items_id        = 'printers_id';
    public $dohistory              = false;


    /**
     * Get name of this type by language of the user connected
     *
     * @param integer $nb number of elements
     *
     * @return string name of this type
     */
    public static function getTypeName($nb = 0)
    {
        return __('Page counters');
    }

    /**
     * Get the tab name used for item
     *
     * @param CommonGLPI $item the item object
     * @param integer $withtemplate 1 if is a template form
     * @return string|array name of the tab
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        $array_ret = [];

        if ($item instanceof Printer) {
            $cnt = countElementsInTable([static::getTable()], [static::$items_id => $item->getField('id')]);
            $array_ret[] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $cnt);
        }
        return $array_ret;
    }


    /**
     * Display the content of the tab
     *
     * @param CommonGLPI $item
     * @param integer $tabnum number of the tab to display
     * @param integer $withtemplate 1 if is a template form
     * @return boolean
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (get_class($item) == Printer::class && $item->getID() > 0) {
            $printerlog = new self();
            $printerlog->showMetrics($item);
            return true;
        }
        return false;
    }

    /**
     * Get metrics
     *
     * @param Printer $printer      Printer instance
     * @param array   $user_filters User filters
     *
     * @return array
     */
    public function getMetrics(Printer $printer, $user_filters = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $bdate = new DateTime();
        $bdate->sub(new DateInterval('P1Y'));
        $filters = [
            'date' => ['>', $bdate->format('Y-m-d')]
        ];
        $filters = array_merge($filters, $user_filters);

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'printers_id'  => $printer->fields['id']
            ] + $filters
        ]);

        $series = iterator_to_array($iterator, false);

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

        return $series;
    }

    /**
     * Display form for agent
     *
     * @param Printer $printer Printer instance
     */
    public function showMetrics(Printer $printer)
    {
        $raw_metrics = $this->getMetrics($printer);

       //build graph data
        $params = [
            'label'         => $this->getTypeName(),
            'icon'          => Printer::getIcon(),
            'apply_filters' => [],
        ];

        $series = [];
        $labels = [];

        // Formatter to display the date (months names) in the correct language
        // Dates will be displayed as "d MMMM":
        // d = short day number (1, 12, ...)
        // MMM = short month name (jan, feb, ...)
        // Note that PHP use ISO 8601 Date Output here which is different from
        // the "Constants for PHP Date Output" used in others functions
        // See https://framework.zend.com/manual/1.12/en/zend.date.constants.html#zend.date.constants.selfdefinedformats
        $fmt = new IntlDateFormatter(
            $_SESSION['glpilanguage'] ?? 'en_GB',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            null,
            null,
            'd MMM'
        );

        foreach ($raw_metrics as $metrics) {
            $date = new DateTime($metrics['date']);
            $labels[] = $fmt->format($date);
            unset($metrics['id'], $metrics['date'], $metrics['printers_id']);

            // Keep values if at least 1 label is greater than 0
            $valuesum = array_sum($metrics);
            foreach ($metrics as $key => $value) {
                $label = $this->getLabelFor($key);
                if ($label && $valuesum > 0) {
                    $series[$key]['name'] = $label;
                    $series[$key]['data'][] = $value;
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
                'labels' => $labels,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
            'color' => '#ffffff',
            'distributed' => false
        ];

       //display graph
        echo "<div class='dashboard printer_barchart'>";
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
    private function getLabelFor($key): ?string
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
