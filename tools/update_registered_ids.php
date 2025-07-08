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

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

require dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new \Glpi\Kernel\Kernel();
$kernel->boot();

$registeredid = new RegisteredID();
$manufacturer = new Manufacturer();
foreach (
    ['PCI' => 'http://pciids.sourceforge.net/v2.2/pci.ids',
        'USB' => 'http://www.linux-usb.org/usb.ids',
    ] as $type => $URL
) {
    echo "Processing : $type\n";
    foreach (file($URL) as $line) {
        if ($line[0] == '#') {
            continue;
        }
        $line = rtrim($line);
        if (empty($line)) {
            continue;
        }
        if ($line[0] != '\t') {
            $id   = strtolower(substr($line, 0, 4));
            $name = trim(substr($line, 4));
            if (
                $registeredid->getFromDBByCrit([
                    'itemtype'     => 'Manufacturer',
                    'name'         => $id,
                    'device_type'  => $type,
                ])
            ) {
                $manufacturer->getFromDB($registeredid->fields['items_id']);
            } else {
                if (!$manufacturer->getFromDBByCrit(['name' => $name])) {
                    $input = ['name' => $name];
                    $manufacturer->add($input);
                }
                $input = ['itemtype'    => $manufacturer->getType(),
                    'items_id'    => $manufacturer->getID(),
                    'device_type' => $type,
                    'name'        => $id,
                ];
                $registeredid->add($input);
            }
            continue;
        }
        // if (($line[0] == "\t") && ($line[1] != '\t'))  {
        //    $line = trim($line);
        //    $id   = strtolower(substr($line, 0, 4));
        //    $name = trim(substr($line, 4));
        //    continue;
        // }
    }
}
