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

require_once(__DIR__ . '/_check_webserver_config.php');

/**
 * @var mixed $this
 * @var mixed $dropdown
 */

use Glpi\Controller\GenericListController;
use Glpi\Controller\LegacyFileLoadController;

if (!($this instanceof LegacyFileLoadController) || !($dropdown instanceof CommonDropdown)) {
    throw new LogicException();
}

Toolbox::deprecated(\sprintf(
    'Requiring legacy dropdown files is deprecated. You can safely remove the `%s` file in order to make the `%s` controller used instead.',
    debug_backtrace()[0]['file'] ?? 'including',
    GenericListController::class,
));

$request = $this->getRequest(); // @phpstan-ignore method.private
$request->attributes->set('class', $dropdown::class);

$controller = new GenericListController();
$response = $controller($request);
$response->send();
