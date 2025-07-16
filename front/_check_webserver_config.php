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

use Glpi\Kernel\Kernel;

if (!class_exists(Kernel::class, autoload: false)) {
    // `Glpi\Kernel\Kernel` class will exists if the request was processed by the `/public/index.php` file,
    // and will not be found otherwise.
    header('HTTP/1.1 404 Not Found');
    readfile(__DIR__ . '/../index.html'); // @phpstan-ignore theCodingMachineSafe.function (vendor libs are not yet loaded)
    exit(); // @phpstan-ignore glpi.forbidExit (Script execution should be stopped to prevent further errors)
}
