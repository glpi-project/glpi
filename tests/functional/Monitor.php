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
}
