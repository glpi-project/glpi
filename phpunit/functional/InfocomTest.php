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

namespace tests\units;

use Cartridge;
use Consumable;
use DbTestCase;
use Glpi\Asset\Capacity\HasInfocomCapacity;
use Glpi\Features\Clonable;
use Infocom;
use PHPUnit\Framework\Attributes\DataProvider;
use Toolbox;

class InfocomTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new \Glpi\Asset\Capacity(name: HasInfocomCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['infocom_types'] as $itemtype) {
            if (in_array($itemtype, [Cartridge::class, Consumable::class], true)) {
                continue;
            }

            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Infocom$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new \Glpi\Asset\Capacity(name: HasInfocomCapacity::class)]);

        foreach ($CFG_GLPI['infocom_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Infocom::class, $item->getCloneRelations(), $itemtype);
        }
    }

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


    #[DataProvider('dataLinearAmortise')]
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

    /**
     * Test that alerts are raised for non-deleted items that have warranties that are about to expire.
     * @return void
     */
    public function testExpireCronAlerts()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();
        $root_entity = $this->getTestRootEntity();
        $computer = new \Computer();
        $infocom = new \Infocom();

        $this->assertTrue($root_entity->update([
            'id' => $root_entity->getID(),
            'use_infocoms_alert' => 1,
            'send_infocoms_alert_before_delay' => 10, // 10 days
        ]));

        $deleted_id = $computer->add([
            'name' => 'Deleted test',
            'entities_id' => $root_entity->getID(),
            'is_deleted' => 1,
        ]);
        $deleted_expired_infocom_id = $infocom->add([
            'itemtype' => 'Computer',
            'items_id' => $deleted_id,
            'warranty_date' => date('Y-m-d', strtotime('-1 year')),
            'warranty_duration' => 10, // 10 months
        ]);

        $deleted_id2 = $computer->add([
            'name' => 'Deleted test',
            'entities_id' => $root_entity->getID(),
            'is_deleted' => 1,
        ]);
        $deleted_infocom_id = $infocom->add([
            'itemtype' => 'Computer',
            'items_id' => $deleted_id2,
            'warranty_date' => date('Y-m-d', strtotime('-1 year')),
            'warranty_duration' => 14, // 14 months
        ]);

        $not_deleted_id = $computer->add([
            'name' => 'Not deleted test',
            'entities_id' => $root_entity->getID(),
        ]);
        $not_deleted_infocom_id = $infocom->add([
            'itemtype' => 'Computer',
            'items_id' => $not_deleted_id,
            'warranty_date' => date('Y-m-d', strtotime('-1 year')),
            'warranty_duration' => 10, // 10 months
        ]);

        $CFG_GLPI["use_notifications"] = true;
        \Infocom::cronInfocom();
        $alerts = array_values(getAllDataFromTable(\Alert::getTable(), [
            'itemtype' => 'Infocom',
            'items_id' => [$deleted_infocom_id, $deleted_expired_infocom_id, $not_deleted_infocom_id],
        ]));

        $this->assertCount(2, $alerts);
        $this->assertSame($not_deleted_infocom_id, $alerts[0]['items_id']);
        $this->assertSame($deleted_expired_infocom_id, $alerts[1]['items_id']);
    }
}
