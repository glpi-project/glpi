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

namespace tests\units\Glpi\Dashboard;

use DbTestCase;

/* Test for inc/dashboard/widget.class.php */

class Widget extends DbTestCase
{
    public function testGetAllTypes()
    {
        $types = \Glpi\Dashboard\Widget::getAllTypes();

        $this->array($types)->isNotEmpty();
        foreach ($types as $specs) {
            $this->array($specs)
            ->hasKeys(['label', 'function', 'image']);
        }
    }


    protected function palettes()
    {
        return [
            [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 4,
                'revert'    => true,
                'expected'  => [
                    'names'  => [0, 1, 2, 3],
                    'colors' => [
                        '#a6a6a6',
                        '#808080',
                        '#595959',
                        '#333333',
                    ],
                ]
            ], [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 4,
                'revert'    => false,
                'expected'  => [
                    'names'  => [0, 1, 2, 3],
                    'colors' => [
                        '#595959',
                        '#808080',
                        '#a6a6a6',
                        '#cccccc',
                    ],
                ]
            ], [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 1,
                'revert'    => true,
                'expected'  => [
                    'names'  => ['a'],
                    'colors' => [
                        '#999999',
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider palettes
     */
    public function testGetGradientPalette(
        string $bg_color,
        int $nb_series,
        bool $revert,
        array $expected
    ) {
        $this->array(\Glpi\Dashboard\Widget::getGradientPalette($bg_color, $nb_series, $revert))
           ->isEqualTo($expected);
    }
}
