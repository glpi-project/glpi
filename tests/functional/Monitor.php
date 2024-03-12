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

/* Test for inc/monitor.class.php */

class Monitor extends DbTestCase
{
    private static function getMonitorFields($id, $date)
    {
        return [
            'id' => $id,
            'entities_id' => 0,
            'name' => '_test_monitor01',
            'date_mod' => $date,
            'contact' => null,
            'contact_num' => null,
            'users_id_tech' => 0,
            'groups_id_tech' => 0,
            'comment' => null,
            'serial' => null,
            'otherserial' => null,
            'size' => '0.00',
            'have_micro' => 0,
            'have_speaker' => 0,
            'have_subd' => 0,
            'have_bnc' => 0,
            'have_dvi' => 0,
            'have_pivot' => 0,
            'have_hdmi' => 0,
            'have_displayport' => 0,
            'locations_id' => 0,
            'monitortypes_id' => 0,
            'monitormodels_id' => 0,
            'manufacturers_id' => 0,
            'is_global' => 0,
            'is_deleted' => 0,
            'is_template' => 0,
            'template_name' => null,
            'users_id' => 0,
            'groups_id' => 0,
            'states_id' => 0,
            'ticket_tco' => '0.0000',
            'is_dynamic' => 0,
            'autoupdatesystems_id' => 0,
            'date_creation' => $date,
            'is_recursive' => 0,
            'uuid' => null,
        ];
    }

    private function getNewMonitor()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $data = [
            'name'         => '_test_monitor01',
            'entities_id'  => 0
        ];

        $monitor = new \Monitor();
        $added = $monitor->add($data);
        $this->integer((int)$added)->isGreaterThan(0);

        $monitor = getItemByTypeName('Monitor', '_test_monitor01');

        $expected = Monitor::getMonitorFields($added, $date);
        $this->array($monitor->fields)->isEqualTo($expected);
        return $monitor;
    }

    public function testBasicMonitor()
    {
        $monitor = $this->getNewMonitor();
    }

    public function testClone()
    {
        $monitor = $this->getNewMonitor();

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        $added = $monitor->clone();
        $this->integer((int)$added)->isGreaterThan(0);

        $clonedMonitor = new \Monitor();
        $this->boolean($clonedMonitor->getFromDB($added))->isTrue();

        $expected = Monitor::getMonitorFields($added, $date);

        $this->string($clonedMonitor->fields['name'])->isEqualTo("$expected[name] (copy)");
        unset($clonedMonitor->fields['name'], $expected['name']);

        $this->array($clonedMonitor->fields)->isEqualTo($expected);
    }

    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $monitor = $this->createItem(\Monitor::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]);
        $monitors_id_1 = $monitor->fields['id'];
        $this->array($monitor->fields['groups_id'])->containsValues([1, 2]);
        $this->array($monitor->fields['groups_id_tech'])->containsValues([3, 4]);

        $monitor = $this->createItem(\Monitor::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => null,
            'groups_id_tech' => null,
        ]);
        $monitors_id_2 = $monitor->fields['id'];
        $this->array($monitor->fields['groups_id'])->isEmpty();
        $this->array($monitor->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $monitor->getFromDB($monitors_id_1);
        $this->boolean($monitor->update([
            'id' => $monitors_id_1,
            'groups_id' => null,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($monitor->fields['groups_id'])->isEmpty();
        $this->array($monitor->fields['groups_id_tech'])->isEmpty();

        $monitor->getFromDB($monitors_id_2);
        $this->boolean($monitor->update([
            'id' => $monitors_id_2,
            'groups_id' => [5, 6],
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($monitor->fields['groups_id'])->containsValues([5, 6]);
        $this->array($monitor->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($monitor->update([
            'id' => $monitors_id_2,
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($monitor->fields['groups_id'])->containsValues([1, 2]);
        $this->array($monitor->fields['groups_id_tech'])->containsValues([3, 4]);
    }

    /**
     * Test the loading assets which still have integer values for groups_id and groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     * @return void
     */
    public function testLoadOldItemsSingleGroup()
    {
        /** @var \DBmysql $DB */
        global $DB;
        $monitor = $this->createItem(\Monitor::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $monitors_id = $monitor->fields['id'];

        // Manually set the groups_id and groups_id_tech fields to an integer value
        $DB->update(
            'glpi_monitors',
            [
                'groups_id' => 1,
                'groups_id_tech' => 2,
            ],
            [
                'id' => $monitors_id,
            ]
        );
        $monitor->getFromDB($monitors_id);
        $this->array($monitor->fields['groups_id'])->containsValues([1]);
        $this->array($monitor->fields['groups_id_tech'])->containsValues([2]);

        // Manually set the groups_id and groups_id_tech fields to 0
        $DB->update(
            'glpi_monitors',
            [
                'groups_id' => 0,
                'groups_id_tech' => 0,
            ],
            [
                'id' => $monitors_id,
            ]
        );
        $monitor->getFromDB($monitors_id);
        $this->array($monitor->fields['groups_id'])->isEmpty();
        $this->array($monitor->fields['groups_id_tech'])->isEmpty();

        // Manually set the groups_id and groups_id_tech fields to NULL (allowed by the DB schema)
        $DB->update(
            'glpi_monitors',
            [
                'groups_id' => null,
                'groups_id_tech' => null,
            ],
            [
                'id' => $monitors_id,
            ]
        );
        $monitor->getFromDB($monitors_id);
        $this->array($monitor->fields['groups_id'])->isEmpty();
        $this->array($monitor->fields['groups_id_tech'])->isEmpty();
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $monitor = new \Monitor();
        $this->array($monitor->fields['groups_id'])->isEmpty();
        $this->array($monitor->fields['groups_id_tech'])->isEmpty();
    }
}
