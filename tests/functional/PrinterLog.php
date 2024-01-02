<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class PrinterLog extends DbTestCase
{
    public function testGetMetrics()
    {
        $printer = new \Printer();
        $printers_id = $printer->add([
            'name'   => 'Inventoried printer',
            'entities_id'  => 0
        ]);
        $this->integer($printers_id)->isGreaterThan(0);

        $_SESSION['glpi_currenttime'] = '2023-10-10 10:10:10';
        $now = new \DateTime(\Session::getCurrentTime());

        $log = new \PrinterLog();

        $cdate1 = (new \DateTime(\Session::getCurrentTime()))->modify('-14 months');
        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 5132,
            'bw_pages' => 3333,
            'color_pages' => 1799,
            'rv_pages' => 4389,
            'scanned' => 7846,
            'date' => $cdate1->format('Y-m-d')
        ];
        $this->integer($log->add($input))->isGreaterThan(0);

        $cdate2 = (new \DateTime(\Session::getCurrentTime()))->modify('-6 months');
        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 6521,
            'bw_pages' => 4100,
            'color_pages' => 2151,
            'rv_pages' => 5987,
            'scanned' => 15542,
            'date' => $cdate2->format('Y-m-d')
        ];
        $this->integer($log->add($input))->isGreaterThan(0);

        $cdate3 = (new \DateTime(\Session::getCurrentTime()))->modify('first day of previous month');
        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 3464,
            'bw_pages' => 2154,
            'color_pages' => 1310,
            'rv_pages' => 548,
            'scanned' => 4657,
            'date' => $cdate3->format('Y-m-d')
        ];
        $this->integer($log->add($input))->isGreaterThan(0);

        $input = [
            'printers_id' => $printers_id,
            'total_pages' => 9299,
            'bw_pages' => 6258,
            'color_pages' => 3041,
            'rv_pages' => 7654,
            'scanned' => 28177,
            'date' => $now->format('Y-m-d')
        ];
        $this->integer($log->add($input))->isGreaterThan(0);

        //per default, get 1Y old, first not included
        $this->array($log->getMetrics($printer))->hasSize(1);
        $this->array($log->getMetrics($printer)[$printer->getID()])->hasSize(3);

        //same with start_date parameter
        $this->array($log->getMetrics($printer, start_date: $cdate1))->hasSize(1);
        $this->array($log->getMetrics($printer, start_date: $cdate1)[$printer->getID()])->hasSize(4);
        //same with interval parameter
        $this->array($log->getMetrics($printer, interval: 'P14M'))->hasSize(1);
        $this->array($log->getMetrics($printer, interval: 'P14M')[$printer->getID()])->hasSize(4);

        //use end_date parameter to exclude last report
        $this->array($log->getMetrics($printer, end_date: $now)[$printer->getID()])->hasSize(3);
        $this->array($log->getMetrics($printer, start_date: $cdate1, end_date: $now->sub(new \DateInterval('P1D')))[$printer->getID()])->hasSize(3);

        $datex = new \DateTime(\Session::getCurrentTime());
        for ($i = 0; $i < 21; $i++) {
            $datex->sub(new \DateInterval('P1D'));
            $input = [
                'printers_id' => $printers_id,
                'total_pages' => 9299,
                'bw_pages' => 6258,
                'color_pages' => 3041,
                'rv_pages' => 7654,
                'scanned' => 28177,
                'date' => $datex->format('Y-m-d')
            ];
            $this->integer($log->add($input))->isGreaterThan(0);
        }

        // check working of daily format
        $this->array($log->getMetrics($printer, format: 'daily', interval: 'P2M'))->hasSize(1);
        $this->array($log->getMetrics($printer, interval: 'P2M', format: 'daily')[$printer->getID()])->hasSize(23);

        // check working of weekly format
        $this->array($log->getMetrics($printer, format: 'weekly', interval: 'P28D'))->hasSize(1);
        $this->array($log->getMetrics($printer, interval: 'P28D', format: 'weekly')[$printer->getID()])->hasSize(4);

        // check working of monthly format
        $this->array($log->getMetrics($printer, format: 'monthly', interval: 'P2Y'))->hasSize(1);
        $this->array($log->getMetrics($printer, format: 'monthly', interval: 'P2Y')[$printer->getID()])->hasSize(4);

        // check working of yearly format
        $this->array($log->getMetrics($printer, format: 'yearly', interval: 'P2Y'))->hasSize(1);
        $this->array($log->getMetrics($printer, format: 'yearly', interval: 'P2Y')[$printer->getID()])->hasSize(2);
    }
}
