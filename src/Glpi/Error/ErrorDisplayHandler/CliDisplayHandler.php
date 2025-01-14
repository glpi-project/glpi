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

namespace Glpi\Error\ErrorDisplayHandler;

final class CliDisplayHandler implements ErrorDisplayHandler
{
    public function canOutput(string $log_level, string $env): bool
    {
        if (defined('TU_USER')) {
            // Our test suite il already checking logs to ensure that there is no unexpected error triggerred.
            // Displaying of error messages is then disabled to not pollute the test suite results.
            return false;
        }

        return \isCommandLine();
    }

    public function displayErrorMessage(string $error_type, string $message, string $log_level, mixed $env): void
    {
        echo \sprintf('%s: %s', $error_type, $message) . PHP_EOL;
    }
}
