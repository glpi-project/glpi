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

/* Test for inc/alert.class.php */

class AlertTest extends DbTestCase
{
    public function testAddDelete()
    {
        $alert = new \Alert();
        $nb    = countElementsInTable($alert->getTable());
        $comp  = getItemByTypeName('Computer', '_test_pc01');
        $date  = '2016-09-01 12:34:56';

        // Add
        $id = $alert->add([
            'itemtype' => $comp->getType(),
            'items_id' => $comp->getID(),
            'type'     => \Alert::END,
            'date'     => $date,
        ]);
        $this->assertGreaterThan(0, $id);
        $this->assertGreaterThan($nb, countElementsInTable($alert->getTable()));

        // Getters
        $this->assertFalse(\Alert::alertExists($comp->getType(), $comp->getID(), \Alert::NOTICE));
        $this->assertSame($id, (int) \Alert::alertExists($comp->getType(), $comp->getID(), \Alert::END));
        $this->assertSame($date, \Alert::getAlertDate($comp->getType(), $comp->getID(), \Alert::END));

        // Display
        ob_start();
        \Alert::displayLastAlert($comp->getType(), $comp->getID());
        $output = ob_get_clean();
        $this->assertSame(sprintf('Alert sent on %s', \Html::convDateTime($date)), $output);

        // Delete
        $this->assertTrue($alert->clear($comp->getType(), $comp->getID(), \Alert::END));
        $this->assertSame(0, countElementsInTable($alert->getTable()));

        // Still true, nothing to delete but no error
        $this->assertTrue($alert->clear($comp->getType(), $comp->getID(), \Alert::END));
    }
}
