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

include(__DIR__ . '/../inc/includes.php');

$printers_id = false;
$networkports_id = false;

if ($printers_id !== false) {
    $metrics = new PrinterLog();

    $now = new DateTime();
    $begin = clone $now;
    $begin->sub(new DateInterval('P1Y'));

    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $interval, $now);

    $total_pages = 0;
    $bw_pages = 0;
    $color_pages = 0;
    $rv_pages = 0;
    $scanned = 0;

    foreach ($period as $dt) {
        $total = random_int(10, 95);
        $rvs = (int)round($total * random_int(70, 95) / 100);
        $bws = (int)round($total * random_int(55, 80) / 100);
        $colors = $total - $bws;
        $scans = random_int(20, 100);

        $total_pages += $total;
        $rv_pages += $rvs;
        $bw_pages += $bws;
        $color_pages += $colors;
        $scanned += $scans;

        $input = [
            'date'           => $dt->format('Y-m-d'),
            'total_pages'    => $total_pages,
            'bw_pages'       => $bw_pages,
            'color_pages'    => $color_pages,
            'scanned'        => $scanned,
            'rv_pages'       => $rv_pages,
            'printers_id'    => $printers_id
        ];
        $metrics->add($input, [], false);
    }
}

if ($networkports_id !== false) {
    $metrics = new NetworkPortMetrics();

    $now = new DateTime();
    $begin = clone $now;
    $begin->sub(new DateInterval('P1Y'));
    $mini = new DateTime('2020-01-01');
    if ($begin > $mini) {
        $begin = clone $mini;
    }

    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $interval, $now);

    foreach ($period as $dt) {
        $inbytes = random_int(0, 40000000000);
        $outbytes = random_int(0, 4000000000);
        $inerrors = random_int(0, 1500);
        $outerrors = random_int(0, 750);

        $input = [
            'date'            => $dt->format('Y-m-d'),
            'ifinbytes'       => $inbytes,
            'ifoutbytes'      => $outbytes,
            'ifinerrors'      => $inerrors,
            'ifouterrors'     => $outerrors,
            'networkports_id' => $networkports_id
        ];
        $metrics->add($input, [], false);
    }
}
