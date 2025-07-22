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

use Glpi\Application\Environment;
use Session;
use Symfony\Component\HttpFoundation\Request;

final class HtmlErrorDisplayHandler implements ErrorDisplayHandler
{
    private static ?Request $currentRequest = null;

    public static function setCurrentRequest(Request $request): void
    {
        self::$currentRequest = $request;
    }

    public function canOutput(): bool
    {
        if (\isCommandLine()) {
            return false;
        }

        $is_env_with_debug_tools = Environment::get()->shouldEnableExtraDevAndDebugTools();
        $is_debug_mode = isset($_SESSION['glpi_use_mode']) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        if (
            !$is_env_with_debug_tools // error messages are always displayed in environments with debug tools
            && !$is_debug_mode // error messages are always displayed in debug mode
        ) {
            return false;
        }

        // Need to fallback to `Request::createFromGlobals()` for errors that appears before the
        // `onRequest` event.
        $request = self::$currentRequest ?? Request::createFromGlobals();

        return $request->getPreferredFormat() === 'html';
    }

    public function displayErrorMessage(string $error_label, string $message, string $log_level): void
    {
        echo \sprintf(
            '<div class="alert alert-important alert-danger glpi-debug-alert"><span class="fw-bold">%s: </span>%s</div>',
            \htmlescape($error_label),
            \htmlescape($message)
        );
    }
}
