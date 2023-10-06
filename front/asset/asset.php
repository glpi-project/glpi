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

use Glpi\Asset\AssetDefinition;
use Glpi\Http\Response;
use Glpi\Search\SearchEngine;

include('../../inc/includes.php');

$definition = new AssetDefinition();
$classname  = array_key_exists('class', $_GET) && $definition->getFromDBBySystemName((string)$_GET['class'])
    ? $definition->getConcreteClassName()
    : null;

if ($classname === null || !class_exists($classname)) {
    Response::sendError(400, 'Bad request', Response::CONTENT_TYPE_TEXT_HTML);
}

Session::checkRight($classname::$rightname, READ);

Html::header($classname::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'assets', $classname);

SearchEngine::show($classname);

Html::footer();
