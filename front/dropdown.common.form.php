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
 */

use Glpi\Controller\DropdownFormController;
use Glpi\Controller\LegacyFileLoadController;

if (!($this instanceof LegacyFileLoadController) || !($dropdown instanceof CommonDropdown)) {
    throw new LogicException();
}

\Toolbox::deprecated(\sprintf(
    'Requiring legacy dropdown files is deprecated. You can safely remove the %s file and use the new `%s` route, dedicated for dropdowns.',
    debug_backtrace()[0]['file'] ?? 'including',
    'glpi_dropdown_form',
));

DropdownFormController::loadDropdownForm($this->request, $dropdown, $options ?? []);
