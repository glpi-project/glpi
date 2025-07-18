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

/** @var int|bool|null $AJAX_INCLUDE */
global $AJAX_INCLUDE;
if (isset($AJAX_INCLUDE)) {
    trigger_error(
        'The global `$AJAX_INCLUDE` variable has no effect anymore.',
        E_USER_WARNING
    );
}

/** @var string|null $SECURITY_STRATEGY */
global $SECURITY_STRATEGY;
if (isset($SECURITY_STRATEGY)) {
    throw new RuntimeException('The global `$SECURITY_STRATEGY` variable has no effect anymore.');
}

/**
 * @var mixed|null $USEDBREPLICATE
 * @var mixed|null $DBCONNECTION_REQUIRED
 */
global $USEDBREPLICATE, $DBCONNECTION_REQUIRED;
if (isset($USEDBREPLICATE) || isset($DBCONNECTION_REQUIRED)) {
    trigger_error(
        'The global `$USEDBREPLICATE` and `$DBCONNECTION_REQUIRED` variables has no effect anymore. Use "DBConnection::getReadConnection()" to get the most apporpriate connection for read only operations.',
        E_USER_WARNING
    );
}

/**
 * @var mixed|null $PLUGINS_EXCLUDED
 * @var mixed|null $PLUGINS_INCLUDED
 */
global $PLUGINS_EXCLUDED, $PLUGINS_INCLUDED;
if (isset($PLUGINS_EXCLUDED) || isset($PLUGINS_INCLUDED)) {
    trigger_error(
        'The global `$PLUGINS_EXCLUDED` and `$PLUGINS_INCLUDED` variables has no effect anymore.',
        E_USER_WARNING
    );
}

/**
 * @var mixed|null $skip_db_check
 */
global $skip_db_check;
if (isset($skip_db_check)) {
    trigger_error(
        'The global `$skip_db_check` variable has no effect anymore.',
        E_USER_WARNING
    );
}

/**
 * @var mixed|null $dont_check_maintenance_mode
 */
global $dont_check_maintenance_mode;
if (isset($dont_check_maintenance_mode)) {
    trigger_error(
        'The global `$dont_check_maintenance_mode` variable has no effect anymore.',
        E_USER_WARNING
    );
}
