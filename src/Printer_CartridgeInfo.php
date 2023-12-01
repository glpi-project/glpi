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

use Glpi\Application\View\TemplateRenderer;

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
        /** @var \DBmysql $DB */
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

        $tab[] = [
            'id'                => 1400,
            'table'             => self::getTable(),
            'field'             => "_virtual_toner_percent",
            'name'              => __('Toner percentage'),
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

        $tab[] = [
            'id'                => 1401,
            'table'             => self::getTable(),
            'field'             => "_virtual_drum_percent",
            'name'              => __('Drum percentage'),
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

        return $tab;
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Create a badge for a specific type of cartridge information
     *
     * @param array $data
     * @param string $type
     */
    private static function createCartridgeInformationBadge($data, $type): ?string
    {
        $color_aliases = [
            'magenta'   => 'purple',
        ];
        $color_translations = [
            'black'         => __('Black'),
            'cyan'          => __('Cyan'),
            'magenta'       => __('Magenta'),
            'yellow'        => __('Yellow'),
        ];

        if (
            is_array($data)
            && isset($data['property'])
            && isset($data['value'])
            && str_starts_with($data['property'], $type)
        ) {
            $color = str_replace($type, '', $data['property']);
            $templateContent = <<<TWIG
                <span class="badge bg-{{ color }} text-{{ color }}-fg fw-bold">
                    {{ color_translated }} : {{ status }}
                </span>
            TWIG;

            return TemplateRenderer::getInstance()->renderFromStringTemplate($templateContent, [
                'color_translated' => $color_translations[$color] ?? ucwords($color),
                'color' => $color_aliases[$color] ?? $color,
                'status' => is_numeric($data['value']) ? $data['value'] . '%' : $data['value']
            ]);
        }

        return null;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        $printer = new Printer();
        if (str_starts_with($field, '_virtual_')) {
            $type = preg_match('/_virtual_(.*)_percent/', $field, $matches) ? $matches[1] : '';
            $badges = array_filter(array_map(
                function ($data) use ($type) {
                    return self::createCartridgeInformationBadge($data, $type);
                },
                $options['raw_data']['Printer_' . $printer->getSearchOptionIDByField('field', $field)]
            ));

            if ($badges) {
                $templateContent = <<<TWIG
                    <div class="d-flex flex-wrap gap-1">
                        {% for badge in badges %}
                            {{ badge|raw }}
                        {% endfor %}
                    </div>
                TWIG;

                return TemplateRenderer::getInstance()->renderFromStringTemplate($templateContent, [
                    'badges' => $badges
                ]);
            }
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
