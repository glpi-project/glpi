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

/**
 * Following variables have to be defined before inclusion of this file:
 * @var CommonDropdown $dropdown
 * @var LegacyFileLoadController $this
 * @var Request $request
 */

use Glpi\Controller\DropdownController;
use Glpi\Controller\LegacyFileLoadController;
use Symfony\Component\HttpFoundation\Request;

if (!($dropdown instanceof CommonDropdown)) {
    Html::displayErrorAndDie('');
}
if (!($this instanceof LegacyFileLoadController)) {
    die('Dropdown was not executed in the right context. Are you running GLPI 11.0 or above?');
}
if (!($request instanceof Request)) {
    die('Request variable is not available, did you run the dropdown in the right context?');
}

\Toolbox::deprecated(message: \sprintf(
    "Requiring legacy dropdown files is deprecated and will be removed in the future.\n" .
    "You can safely remove the %s file and use the new \"%s\" route, dedicated for dropdowns.",
    debug_backtrace()[0]['file'] ?? 'including',
    'glpi_dropdown',
), version: '11.0');

DropdownController::loadDropdown($request, $dropdown);
