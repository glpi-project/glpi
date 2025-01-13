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

use Symfony\Component\HttpFoundation\Request;

final class HtmlErrorDisplayHandler implements ErrorDisplayHandler
{
    private static ?Request $currentRequest = null;

    public static function setCurrentRequest(Request $request): void
    {
        self::$currentRequest = $request;
    }

    public function canOutput(string $log_level, string $env): bool
    {
        if (!self::$currentRequest) {
            return false;
        }
        $is_dev_env         = $env === \GLPI::ENV_DEVELOPMENT;
        $is_debug_mode      = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE;

        if (
            !$is_dev_env             // error messages are always displayed in development environment
            && !$is_debug_mode        // error messages are always displayed in debug mode
        ) {
            return false;
        }

        /**
         * @see Request::initializeFormats
         */
        $type = self::$currentRequest->getPreferredFormat();

        return $type === 'html';
    }

    public function displayErrorMessage(string $error_type, string $message, string $log_level, mixed $env): void
    {
        echo '<div class="alert alert-important alert-danger glpi-debug-alert" style="z-index:10000">'
            . '<span class="b">' . \htmlescape($error_type) . ': </span>' . \htmlescape($message) . '</div>';
    }
}
