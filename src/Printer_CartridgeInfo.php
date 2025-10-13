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
use Glpi\Inventory\Asset\Cartridge;

use function Safe\preg_match;

class Printer_CartridgeInfo extends CommonDBChild
{
    public static $itemtype        = 'Printer';
    public static $items_id        = 'printers_id';
    public $dohistory              = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Cartridge inventoried information', 'Cartridge inventoried information', $nb);
    }

    public function getInfoForPrinter(Printer $printer)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [
                self::$items_id => $printer->fields['id'],
            ],
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

        $asset = new Cartridge($printer);
        $tags = $asset->knownTags();
        $entries = [];

        foreach ($info as $row) {
            $property   = $row['property'];
            $value      = $row['value'];

            preg_match("/^toner(\w+.*$)/", $property, $matches);
            $bar_color = $matches[1] ?? 'green';
            $text_color = ($bar_color === "black") ? 'white' : 'black';

            if (str_contains($value, 'pages')) {
                $pages = str_replace('pages', '', $value);
                $value = sprintf(
                    _x('%1$s remaining page', '%1$s remaining pages', $pages),
                    $pages
                );
            } elseif ($value === 'OK') {
                $value = __('OK');
            }

            if (is_numeric($value)) {
                $progressbar_data = [
                    'percent'           => $value,
                    'percent_text'      => $value,
                    'background-color'  => htmlescape($bar_color),
                    'text-color'        => $text_color,
                    'text'              => '',
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
                $out = htmlescape($value);
            }
            $entries[] = [
                'property' => $tags[$property]['name'] ?? $property,
                'value'    => $out,
            ];
        }

        if (count($entries)) {
            TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'super_header' => self::getTypeName(Session::getPluralNumber()),
                'columns' => [
                    'property' => __('Property'),
                    'value' => __('Value'),
                ],
                'formatters' => [
                    'value' => 'raw_html',
                ],
                'entries' => $entries,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => false,
            ]);
        }
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id' => strtolower(self::getType()),
            'name' => self::getTypeName(1),
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
                'jointype' => 'child',
            ],
            'additionalfields'  => ['property', 'value'],
            'forcegroupby'      => true,
            'aggregate'         => true,
            'searchtype'        => ['contains'],
            'nosort'            => true,
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
                'jointype' => 'child',
            ],
            'additionalfields'  => ['property', 'value'],
            'forcegroupby'      => true,
            'aggregate'         => true,
            'searchtype'        => ['contains'],
            'nosort'            => true,
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
     * @return string|null
     */
    private static function createCartridgeInformationBadge(array $data, string $type): ?string
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

        if (isset($data['property'], $data['value']) && str_starts_with($data['property'], $type)) {
            $color = str_replace($type, '', $data['property']);
            $twig_params = [
                'color_translated' => $color_translations[$color] ?? ucwords($color),
                'color' => $color_aliases[$color] ?? $color,
                'status' => is_numeric($data['value']) ? $data['value'] . '%' : $data['value'],
            ];
            // language=Twig
            return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <span class="badge bg-{{ color }} text-{{ color }}-fg fw-bold">
                    {{ color_translated }} : {{ status }}
                </span>
TWIG, $twig_params);
        }

        return null;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        $printer = new Printer();
        if (str_starts_with($field, '_virtual_')) {
            $type = preg_match('/_virtual_(.*)_percent/', $field, $matches) ? $matches[1] : '';
            $badges = array_filter(array_map(
                static fn($data) => self::createCartridgeInformationBadge($data, $type),
                $options['raw_data']['Printer_' . $printer->getSearchOptionIDByField('field', $field)]
            ));

            if ($badges) {
                // language=Twig
                return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    <div class="d-flex flex-wrap gap-1">
                        {% for badge in badges %}
                            {{ badge|raw }}
                        {% endfor %}
                    </div>
TWIG, ['badges' => $badges]);
            }
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
