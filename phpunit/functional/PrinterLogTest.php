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

/* Test for inc/printerlog.class.php */

class PrinterLogTest extends DbTestCase
{
    public function testGetMetrics()
    {
        $printer = new \Printer();
        $printers_id = $printer->add([
            'name'   => 'Inventoried printer',
            'entities_id'  => 0
        ]);
        $this->assertGreaterThan(0, $printers_id);

        $now = new \DateTime();

        $log = new \PrinterLog();

        $cdate1 = clone $now;
        $cdate1->sub(new \DateInterval('P14M'));
        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 5132,
            'bw_pages' => 3333,
            'color_pages' => 1799,
            'rv_pages' => 4389,
            'scanned' => 7846,
            'date' => $cdate1->format('Y-m-d')
        ];
        $this->assertGreaterThan(0, $log->add($input));

        $cdate2 = clone $now;
        $cdate2->sub(new \DateInterval('P6M'));
        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 6521,
            'bw_pages' => 4100,
            'color_pages' => 2151,
            'rv_pages' => 5987,
            'scanned' => 15542,
            'date' => $cdate2->format('Y-m-d')
        ];
        $this->assertGreaterThan(0, $log->add($input));

        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 9299,
            'bw_pages' => 6258,
            'color_pages' => 3041,
            'rv_pages' => 7654,
            'scanned' => 28177,
            'date' => $now->format('Y-m-d')
        ];
        $this->assertGreaterThan(0, $log->add($input));

       //per default, get 1Y old, first not included
        $this->assertCount(2, $log->getMetrics($printer));

       //change filter to include first one
        $this->assertCount(3, $log->getMetrics($printer, ['date' => ['>=', $cdate1->format('Y-m-d')]]));
    }
}
