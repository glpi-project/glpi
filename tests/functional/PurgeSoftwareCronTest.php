<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\DBAL\QueryExpression;

class PurgeSoftwareCronTest extends DbTestCase
{
    public function testRun()
    {
        global $DB;

        $this->login();

        $software = new \Software();
        $software_version = new \SoftwareVersion();

        // Clean the tables
        $always_true = [ new QueryExpression('1 = 1') ];
        $software->deleteByCriteria($always_true, 1);
        $software_version->deleteByCriteria($always_true, 1);

        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create 5 software eligible for purge (is_deleted = 1, without versions)
        for ($i = 0; $i < 5; $i++) {
            $software->add([
                'name'        => "Purge Eligible Software $i",
                'entities_id' => $entities_id,
                'is_deleted'  => 1,
            ]);
        }

        // Create 5 software not eligible (is_deleted = 1, with an associated version)
        for ($i = 0; $i < 5; $i++) {
            $software->add([
                'name'        => "Non-Purge Software $i",
                'entities_id' => $entities_id,
                'is_deleted'  => 1,
            ]);
            $softwareId = $software->getID();
            $software_version->add([
                'name'         => "Version for Non-Purge Software $i",
                'softwares_id' => $softwareId,
            ]);
        }

        // Verify that 10 software exist
        $this->assertSame(
            10,
            (int) $DB->request([
                'COUNT' => 'cpt',
                'FROM'  => \Software::getTable(),
            ])->current()['cpt']
        );


        $purgeTask = new \PurgeSoftwareTask();
        // Execute the purge with a limit of 3
        $purged = $purgeTask->run(3);
        $this->assertSame(3, $purged);
        $this->assertSame(
            7,
            (int) $DB->request([
                'COUNT' => 'cpt',
                'FROM'  => \Software::getTable(),
            ])->current()['cpt']
        );

        // Execute the purge for the remaining 10, expecting 2 purged items (because only 2 eligible software remain)
        $purged = $purgeTask->run(10);
        $this->assertSame(2, $purged);
        // There should remain 5 non-eligible software (those with an associated version)
        $this->assertSame(
            5,
            (int) $DB->request([
                'COUNT' => 'cpt',
                'FROM'  => \Software::getTable(),
            ])->current()['cpt']
        );
    }
}
