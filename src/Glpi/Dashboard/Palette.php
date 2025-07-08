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

use Glpi\Plugin\Hooks;
use Plugin;

final class Palette
{
    private $colors = [];

    private const DEFAULT = "tab10";
    private const PALETTES = [
        'tab10' => [
            "#4e79a7",
            "#f28e2c",
            "#e15759",
            "#76b7b2",
            "#59a14f",
            "#edc949",
            "#af7aa1",
            "#ff9da7",
            "#9c755f",
            "#bab0ab",
        ],
        'pastel1' => [
            '#fbb4ae',
            '#b3cde3',
            '#ccebc5',
            '#decbe4',
            '#fed9a6',
            '#ffffcc',
            '#e5d8bd',
            '#fddaec',
            '#f2f2f2',
        ],
        'pastel2' => [
            '#b3e2cd',
            '#fdcdac',
            '#cbd5e8',
            '#f4cae4',
            '#e6f5c9',
            '#fff2ae',
            '#f1e2cc',
            '#cccccc',
        ],
        'pastel3' => [
            "#fd7f6f",
            "#7eb0d5",
            "#b2e061",
            "#bd7ebe",
            "#ffb55a",
            "#ffee65",
            "#beb9db",
            "#fdcce5",
            "#8bd3c7",
        ],
        'paired' => [
            '#a6cee3',
            '#1f78b4',
            '#b2df8a',
            '#33a02c',
            '#fb9a99',
            '#e31a1c',
            '#fdbf6f',
            '#ff7f00',
            '#cab2d6',
            '#6a3d9a',
            '#ffff99',
            '#b15928',
        ],
        'retro_metro' => [
            "#ea5545",
            "#f46a9b",
            "#ef9b20",
            "#edbf33",
            "#ede15b",
            "#bdcf32",
            "#87bc45",
            "#27aeef",
            "#b33dc6",
        ],
        'accent' => [
            '#7fc97f',
            '#beaed4',
            '#fdc086',
            '#ffff99',
            '#386cb0',
            '#f0027f',
            '#bf5b16',
            '#666666',
        ],
        'dark2' => [
            '#1b9e77',
            '#d95f02',
            '#7570b3',
            '#e7298a',
            '#66a61e',
            '#e6ab02',
            '#a6761d',
            '#666666',
        ],
        'set1' => [
            '#e41a1c',
            '#377eb8',
            '#4daf4a',
            '#984ea3',
            '#ff7f00',
            '#ffff33',
            '#a65628',
            '#f781bf',
            '#999999',
        ],
        'set2' => [
            '#66c2a5',
            '#fc8d62',
            '#8da0cb',
            '#e78ac3',
            '#a6d854',
            '#ffd92f',
            '#e5c494',
            '#b3b3b3',
        ],
        'set3' => [
            '#8dd3c7',
            '#ffffb3',
            '#bebada',
            '#fb8072',
            '#80b1d3',
            '#fdb462',
            '#b3de69',
            '#fccde5',
            '#d9d9d9',
            '#bc80bd',
            '#ccebc5',
            '#ffed6f',
        ],
        'tab20' => [
            '#1f77b4',
            '#aec7e8',
            '#ff7f0e',
            '#ffbb78',
            '#2ca02c',
            '#98df8a',
            '#d62728',
            '#ff9896',
            '#9467bd',
            '#c5b0d5',
            '#8c564b',
            '#c49c94',
            '#e377c2',
            '#f7b6d2',
            '#7f7f7f',
            '#c7c7c7',
            '#bcbd22',
            '#dbdb8d',
            '#17becf',
            '#9edae5',
        ],
        'tab20b' => [
            '#393b79',
            '#5254a3',
            '#6b6ecf',
            '#9c9ede',
            '#637939',
            '#8ca252',
            '#b5cf6b',
            '#cedb9c',
            '#8c6d31',
            '#bd9e39',
            '#e7ba52',
            '#e7cb94',
            '#843c39',
            '#ad494a',
            '#d6616b',
            '#e7969c',
            '#7b4173',
            '#a55194',
            '#ce6dbd',
            '#de9ed6',
        ],
        'tab20c' => [
            '#3182bd',
            '#6baed6',
            '#9ecae1',
            '#c6dbef',
            '#e6550d',
            '#fd8d3c',
            '#fdae6b',
            '#fdd0a2',
            '#31a354',
            '#74c476',
            '#a1d99b',
            '#c7e9c0',
            '#756bb1',
            '#9e9ac8',
            '#bcbddc',
            '#dadaeb',
            '#636363',
            '#969696',
            '#bdbdbd',
            '#d9d9d9',
        ],
        'blue_to_red' => [
            "#1984c5",
            "#22a7f0",
            "#63bff0",
            "#a7d5ed",
            "#e2e2e2",
            "#e1a692",
            "#de6e56",
            "#e14b31",
            "#c23728",
        ],
        'pink_foam' => [
            "#54bebe",
            "#76c8c8",
            "#98d1d1",
            "#badbdb",
            "#dedad2",
            "#e4bcad",
            "#df979e",
            "#d7658b",
            "#c80064",
        ],
        'salmon_to_aqua' => [
            "#e27c7c",
            "#a86464",
            "#6d4b4b",
            "#503f3f",
            "#333333",
            "#3c4e4b",
            "#466964",
            "#599e94",
            "#6cd4c5",
        ],
        'black_to_pink' => [
            "#2e2b28",
            "#3b3734",
            "#474440",
            "#54504c",
            "#6b506b",
            "#ab3da9",
            "#de25da",
            "#eb44e8",
            "#ff80ff",
        ],
        'grey_to_red' => [
            "#d7e1ee",
            "#cbd6e4",
            "#bfcbdb",
            "#b3bfd1",
            "#a4a2a8",
            "#df8879",
            "#c86558",
            "#b04238",
            "#991f17",
        ],
        'blues' => [
            "#0000b3",
            "#0010d9",
            "#0020ff",
            "#0040ff",
            "#0060ff",
            "#0080ff",
            "#009fff",
            "#00bfff",
            "#00ffff",
        ],
    ];


    public static function getAllPalettes(): array
    {
        $palettes = self::PALETTES;
        $more_palettes = Plugin::doHookFunction(Hooks::DASHBOARD_PALETTES);

        if (is_array($more_palettes)) {
            $palettes = array_merge($palettes, $more_palettes);
        }

        return $palettes;
    }


    public function __construct(string $palette_name)
    {
        $palettes = self::getAllPalettes();
        $current = array_key_exists($palette_name, $palettes)
            ? $palette_name
            : self::DEFAULT; // Palette not exists (was probably defined by a plugin). Fallback to default palette.
        $this->colors = $palettes[$current];
    }


    public function getColors(int $nb_series = 10): array
    {
        $palette = [];
        while (count($palette) < $nb_series) {
            $palette = array_merge($palette, $this->colors);
        }

        return $palette;
    }
}
