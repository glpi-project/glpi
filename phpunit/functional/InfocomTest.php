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

namespace tests\units;

/* Test for inc/infocom.class.php */

class InfocomTest extends \GLPITestCase
{
    public static function dataLinearAmortise()
    {
        return [
            [
                100000,        //value
                5,             //duration
                '2017-12-31',  //end exercise date
                '2009-12-25',  //buy date
                '2010-03-04',  //use date
                [  //expected
                    2010 => [
                        'start_value' => 100000.0,
                        'value' => 83500.0,
                        'annuity' => 16500.0
                    ],
                    2011 => [
                        'start_value' => 83500.0,
                        'value' => 63500.0,
                        'annuity' => 20000.0
                    ],
                    2012 => [
                        'start_value' => 63500.0,
                        'value' => 43500.0,
                        'annuity' => 20000.0
                    ],
                    2013 => [
                        'start_value' => 43500.0,
                        'value' => 23500.0,
                        'annuity' => 20000.0
                    ],
                    2014 => [
                        'start_value' => 23500.0,
                        'value' => 3500.0,
                        'annuity' => 20000.0
                    ],
                    2015 => [
                        'start_value' => 3500.0,
                        'value' => 0.0,
                        'annuity' => 3500.0
                    ],
                    date('Y') => [
                        'start_value' => 0.0,
                        'value' => 0,
                        'annuity' => 0
                    ]
                ], [  //old format
               //empty for this one.
                ]
            ],

            [
                10000,         //value
                4,             //duration
                '2017-05-01',  //end exercise date
                '2009-07-22',  //buy date
                '2010-08-02',  //use date
                [  //expected
                    2010 => [
                        'start_value' => 10000.0,
                        'value' => 8125.0,
                        'annuity' => 1875.0
                    ],
                    2011 => [
                        'start_value' => 8125.0,
                        'value' => 5625.0,
                        'annuity' => 2500.0
                    ],
                    2012 => [
                        'start_value' => 5625.0,
                        'value' => 3125.0,
                        'annuity' => 2500.0
                    ],
                    2013 => [
                        'start_value' => 3125.0,
                        'value' => 625.0,
                        'annuity' => 2500.0
                    ],
                    2014 => [
                        'start_value' => 625.0,
                        'value' => 0.0,
                        'annuity' => 625.0
                    ],
                    2015 => [
                        'start_value' => 0.0,
                        'value' => 0,
                        'annuity' => 0
                    ],
                    date('Y') => [
                        'start_value' => 0.0,
                        'value' => 0,
                        'annuity' => 0
                    ]
                ], [  //old format
               //empty for this one.
                ]
            ],
            [
                10000,                        //value
                4,                            //duration
                '2017-05-01',                 //end exercise date
                (date('Y') - 2) . '-07-22',   //buy date
                (date('Y') - 2) . '-08-02',   //use date
                [  //expected
                    (date('Y') - 2) => [
                        'start_value' => 10000.0,
                        'value' => 8125.0,
                        'annuity' => 1875.0
                    ],
                    (date('Y') - 1) => [
                        'start_value' => 8125.0,
                        'value' => 5625.0,
                        'annuity' => 2500.0
                    ],
                    date('Y') => [
                        'start_value' => 5625.0,
                        'value' => 3125.0,
                        'annuity' => 2500.0
                    ]
                ], [  //old format
                    'annee'     => [
                        (int)(date('Y') - 2),
                        (int)(date('Y') - 1),
                        (int)date('Y')
                    ],
                    'annuite'   => [
                        1875.0,
                        2500.0,
                        2500.0
                    ],
                    'vcnetdeb'  => [
                        10000.0,
                        8125.0,
                        5625.0
                    ],
                    'vcnetfin'  => [
                        8125.0,
                        5625.0,
                        3125.0
                    ]
                ]
            ]

        ];
    }


    /**
     * @dataProvider dataLinearAmortise
     */
    public function testLinearAmortise($value, $duration, $fiscaldate, $buydate, $usedate, $expected, $oldmft)
    {
        $amortise = \Infocom::linearAmortise(
            $value,
            $duration,
            $fiscaldate,
            $buydate,
            $usedate
        );
        foreach ($expected as $year => $values) {
            $this->assertSame($values, $amortise[$year]);
        }
        if (count($oldmft)) {
            $this->assertSame($oldmft, \Infocom::mapOldAmortiseFormat($amortise, false));
        }
    }
}
