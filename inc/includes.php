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

/** @var int|bool|null $AJAX_INCLUDE */
global $AJAX_INCLUDE;
if (isset($AJAX_INCLUDE)) {
    throw new \RuntimeException('Using the global "$AJAX_INCLUDE" variable has been removed, and will have no effect if set in your controller files. Use "$this->setAjax()" from your controllers instead.');
}

/** @var string|null $SECURITY_STRATEGY */
global $SECURITY_STRATEGY;
if (isset($SECURITY_STRATEGY)) {
    throw new \RuntimeException('Using the global "$SECURITY_STRATEGY" variable has been removed, and will have no effect if set in your controller files. Use the Route attribute in your controller class instead.');
}
