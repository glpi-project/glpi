<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * @var mixed $rulecollection
 */

use Glpi\Controller\LegacyFileLoadController;
use Glpi\Controller\Rule\RuleListController;

if (!($this instanceof LegacyFileLoadController) || !($rulecollection instanceof RuleCollection)) {
    throw new LogicException('$rulecollection must be an instance of RuleCollection || Not in the context of a LegacyFileLoadController');
}

Toolbox::deprecated(\sprintf(
    'Requiring legacy file "' . __FILE__ . '" files is deprecated. You can safely remove the %s file and use the new `%s` route, dedicated for rules.',
    debug_backtrace()[0]['file'] ?? 'including',
    'rules.list',
));

$request = $this->getRequest(); // @phpstan-ignore method.private
$request->attributes->set('class', $rulecollection::getRuleClassName());

$controller = new RuleListController();
$response = $controller($request);
$response->send();
