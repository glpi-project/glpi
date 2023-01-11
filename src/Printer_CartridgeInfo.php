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

class Printer_CartridgeInfo extends CommonDBChild
{
    public static $itemtype        = 'Printer';
    public static $items_id        = 'printers_id';
    public $dohistory              = true;

    public static function getTypeName($nb = 0)
    {
        return _x('Cartridge inventoried information', 'Cartridge inventoried information', $nb);
    }

    public function getInfoForPrinter(Printer $printer)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                self::$items_id => $printer->fields['id']
            ]
        ]);

        $info = [];
        foreach ($iterator as $row) {
            $info[$row['id']] = $row;
        }

        return $info;
    }

    public function showForPrinter(Printer $printer)
    {
        $info = $this->getInfoForPrinter($printer);

        echo "<h3>" . $this->getTypeName(Session::getPluralNumber()) . "</h3>";

        echo "<table class='tab_cadre_fixehov'>";
        echo "<thead><tr><th>" . __('Property') . "</th><th>" . __('Value') . "</th></tr></thead>";

        $asset = new Glpi\Inventory\Asset\Cartridge($printer);
        $tags = $asset->knownTags();

        foreach ($info as $row) {
            $property   = $row['property'];
            $value      = $row['value'];

            preg_match("/^toner(\w+.*$)/", $property, $matches);
            $bar_color = $matches[1] ?? 'green';
            $text_color = ($bar_color == "black") ? 'white' : 'black';

            echo "<tr>";
            echo sprintf("<td>%s</td>", $tags[$property]['name'] ?? $property);

            if (strstr($value, 'pages')) {
                $pages = str_replace('pages', '', $value);
                $value = sprintf(
                    _x('%1$s remaining page', '%1$s remaining pages', $pages),
                    $pages
                );
            } else if ($value == 'OK') {
                $value = __('OK');
            }

            if (is_numeric($value)) {
                $progressbar_data = [
                    'percent'           => $value,
                    'percent_text'      => $value,
                    'background-color'  => $bar_color,
                    'text-color'        => $text_color,
                    'text'              => ''
                ];


                $out = <<<HTML
                    <span class='text-nowrap'>
                    {$progressbar_data['text']}
                    </span>
                    <div class="progress" style="height: 16px">
                        <div class="progress-bar progress-bar-striped" role="progressbar"
                            style="width: {$progressbar_data['percent']}%; background-color:
                            {$progressbar_data['background-color']}; color: {$progressbar_data['text-color']};"
                            aria-valuenow="{$progressbar_data['percent']}"
                            aria-valuemin="0" aria-valuemax="100">
                            {$progressbar_data['percent_text']}%
                        </div>

                    </div>
HTML;
            } else {
                $out = $value;
            }
            echo sprintf("<td>%s</td>", $out);

            echo "</tr>";
        }
        echo "</table>";
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id' => strtolower(self::getType()),
            'name' => self::getTypeName(1)
        ];

        $create_toner_percent_option = static function (int $ID, string $color_key, string $color_name): array {
            return [
                'id'                => (string) $ID,
                'table'             => self::getTable(),
                'field'             => "_virtual_toner_{$color_key}_percent",
                'name'              => sprintf(__('%s toner percentage'), $color_name),
                'datatype'          => 'specific',
                'massiveaction'     => false,
                'nosearch'          => true,
                'joinparams'        => [
                    'jointype' => 'child'
                ],
                'additionalfields'  => ['property', 'value'],
                'forcegroupby'      => true,
                'aggregate'         => true,
                'searchtype'        => ['contains'],
                'nosort'            => true
            ];
        };

        $tab[] = $create_toner_percent_option(1400, 'black', __('Black'));
        $tab[] = $create_toner_percent_option(1401, 'cyan', __('Cyan'));
        $tab[] = $create_toner_percent_option(1402, 'cyanlight', __('Light cyan'));
        $tab[] = $create_toner_percent_option(1403, 'magenta', __('Magenta'));
        $tab[] = $create_toner_percent_option(1404, 'magentalight', __('Light magenta'));
        $tab[] = $create_toner_percent_option(1405, 'yellow', __('Yellow'));
        $tab[] = $create_toner_percent_option(1406, 'grey', __('Grey'));
        $tab[] = $create_toner_percent_option(1407, 'darkgrey', __('Dark grey'));

        $create_drum_percent_option = static function (int $ID, string $color_key, string $color_name): array {
            return [
                'id'                => (string) $ID,
                'table'             => self::getTable(),
                'field'             => "_virtual_drum_{$color_key}_percent",
                'name'              => sprintf(__('%s drum percentage'), $color_name),
                'datatype'          => 'specific',
                'massiveaction'     => false,
                'nosearch'          => true,
                'joinparams'        => [
                    'jointype' => 'child'
                ],
                'additionalfields'  => ['property', 'value'],
                'forcegroupby'      => true,
                'aggregate'         => true,
                'searchtype'        => ['contains'],
                'nosort'            => true
            ];
        };

        $tab[] = $create_drum_percent_option(1408, 'black', __('Black'));
        $tab[] = $create_drum_percent_option(1409, 'cyan', __('Cyan'));
        $tab[] = $create_drum_percent_option(1410, 'cyanlight', __('Light cyan'));
        $tab[] = $create_drum_percent_option(1411, 'magenta', __('Magenta'));
        $tab[] = $create_drum_percent_option(1412, 'magentalight', __('Light magenta'));
        $tab[] = $create_drum_percent_option(1413, 'yellow', __('Yellow'));
        $tab[] = $create_drum_percent_option(1414, 'grey', __('Grey'));
        $tab[] = $create_drum_percent_option(1415, 'darkgrey', __('Dark grey'));

        return $tab;
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    private static function getProgressColorsForColor($color)
    {
        $fg_transparency_hex = '80';
        switch ($color) {
            case 'cyan':
            case 'light_cyan':
                return [
                    'fg' => '#00ffff' . $fg_transparency_hex,
                    'text' => 'inherit'
                ];
            case 'magenta':
            case 'light_magenta':
                return [
                    'fg' => '#ff00ff' . $fg_transparency_hex,
                    'text' => 'inherit'
                ];
            case 'yellow':
                return [
                    'fg' => '#ffff00' . $fg_transparency_hex,
                    'text' => 'inherit'
                ];
            case 'black':
            case 'grey':
            case 'darkgrey':
                return [
                    'fg' => '#303030' . $fg_transparency_hex,
                    'text' => 'inherit'
                ];
        }

        return null;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        $color_aliases = [
            'grey'      => 'gray',
            'darkgrey'  => 'darkgray'
        ];
        $printer = new Printer();
        if (str_starts_with($field, '_virtual_')) {
            $type = preg_match('/_virtual_(.*)_.*_percent/', $field, $matches) ? $matches[1] : '';
            $color = preg_match('/_virtual_' . $type . '_(.*)_percent/', $field, $matches) ? $matches[1] : '';
            $search_option_id = $printer->getSearchOptionIDByField('field', $field);
            $raw_search_opt_values = $options['raw_data']['Printer_' . $search_option_id];

            $get_percent_remaining = static function ($color, $raw_search_opt_values, $type) {
                $used_field = $type . $color;

                if ($raw_search_opt_values !== null) {
                    unset($raw_search_opt_values['count']);
                    foreach ($raw_search_opt_values as $raw_search_opt_value) {
                        if ($raw_search_opt_value['property'] === $used_field) {
                            return $raw_search_opt_value['value'];
                        }
                    }
                }
                return null;
            };

            $percent_remaining = $get_percent_remaining($color, $raw_search_opt_values, $type);

            //return value if not null and not numeric  "OK" "WARNING"
            if ($percent_remaining !== null && ! is_numeric($percent_remaining)) {
                return $percent_remaining;
            }

            if ($percent_remaining === null && array_key_exists($color, $color_aliases)) {
                $percent_remaining = $get_percent_remaining($color_aliases[$color], $raw_search_opt_values, $type);
            }

            if ($percent_remaining !== null) {
                return Html::progressBar('pb' . mt_rand(), [
                    'percent' => $percent_remaining,
                    'message' => $percent_remaining . '%',
                    'display' => false,
                    'create' => true,
                    'colors' => self::getProgressColorsForColor($color)
                ]);
            }
            // Need to return some non-empty value otherwise Search engine will throw errors.
            return null;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
