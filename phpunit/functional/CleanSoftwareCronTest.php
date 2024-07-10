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

use DbTestCase;

class CleanSoftwareCronTest extends DbTestCase
{
    public function testRun()
    {
        global $DB;

        $this->login();

        $software = new \Software();
        $software_version = new \SoftwareVersion();

        // Delete all existing software and versions
        $always_true = [
            new \QueryExpression('1 = 1')
        ];
        $this->assertTrue($software->deleteByCriteria($always_true, 1));
        $this->assertTrue($software_version->deleteByCriteria($always_true, 1));

        // verify all deleted
        $this->assertSame(
            0,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \Software::getTable()
            ])->current()['cpt']
        );
        $this->assertSame(
            0,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \SoftwareVersion::getTable()
            ])->current()['cpt']
        );

        // Create 100 software with 10 versions each
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);
        for ($i = 0; $i < 100; $i++) {
            $software->add([
                'name' => "Software $i",
                'entities_id' => $entities_id,
            ]);
            $softwareId = $software->getID();
            $this->assertGreaterThan(0, $softwareId);
            for ($j = 0; $j < 10; $j++) {
                $this->assertGreaterThan(
                    0,
                    $software_version->add([
                        'name' => "Version $j",
                        'softwares_id' => $softwareId,
                    ])
                );
            }
        }

        // verify all created
        $this->assertSame(
            100,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \Software::getTable()
            ])->current()['cpt']
        );
        $this->assertSame(
            1000,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \SoftwareVersion::getTable()
            ])->current()['cpt']
        );

        // Run cron
        \CleanSoftwareCron::run(5);
        // Verify only 5 versions were deleted and no software
        $this->assertSame(
            100,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \Software::getTable()
            ])->current()['cpt']
        );
        $this->assertSame(
            995,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \SoftwareVersion::getTable()
            ])->current()['cpt']
        );

        // Run cron again
        \CleanSoftwareCron::run(990);
        // Verify only 990 versions were deleted and no software
        $this->assertSame(
            100,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \Software::getTable()
            ])->current()['cpt']
        );
        $this->assertSame(
            5,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \SoftwareVersion::getTable()
            ])->current()['cpt']
        );

        // Run cron again
        \CleanSoftwareCron::run(50);
        // All versions should be deleted now and 45 software should be deleted as well
        $this->assertSame(
            55,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \Software::getTable(),
                'WHERE' => [
                    'is_deleted' => 0 // cleanup only trashes software, not purges them
                ]
            ])->current()['cpt']
        );
        $this->assertSame(
            0,
            (int)$DB->request([
                'COUNT' => 'cpt',
                'FROM' => \SoftwareVersion::getTable()
            ])->current()['cpt']
        );
    }
}
