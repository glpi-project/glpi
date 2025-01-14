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

namespace Glpi\Application;

use Glpi\Error\ErrorHandler;
use Glpi\Error\StackTraceFormatter;
use Psr\Log\LogLevel;

/**
 * @deprecated Use a proper "trigger_error" call instead of these functions. It will then be logged AND displayed to the end-user.
 */
class ErrorUtils
{
    /**
     * @deprecated
     */
    public static function logException(\Throwable $exception): void
    {
        $trace = StackTraceFormatter::getTraceAsString($exception->getTrace());

        ErrorHandler::getCurrentLogger()->log(
            LogLevel::CRITICAL,
            '  *** ' . \sprintf('Uncaught Exception %s', \get_class($exception)) . ': ' . \sprintf('%s in %s at line %s', $exception->getMessage(), $exception->getFile(), $exception->getLine()) . (!empty($trace) ? "\n" . $trace : '')
        );
    }

    /**
     * @deprecated
     */
    public static function outputExceptionMessage(\Throwable $exception): void
    {
        ErrorHandler::displayErrorMessage(
            \sprintf('Exception %s', \get_class($exception)),
            \sprintf('%s in %s at line %s', $exception->getMessage(), $exception->getFile(), $exception->getLine()),
            LogLevel::CRITICAL,
        );
    }
}
