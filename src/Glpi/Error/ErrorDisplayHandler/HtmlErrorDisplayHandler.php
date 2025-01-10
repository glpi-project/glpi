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
        return self::$currentRequest !== null;
    }

    public function displayErrorMessage(string $error_type, string $message, string $log_level, mixed $env): void
    {
        $req = self::$currentRequest;

        $types = $req->getAcceptableContentTypes();

        if (in_array('text/html', $types)) {
            echo '<div class="alert alert-important alert-danger glpi-debug-alert" style="z-index:10000">'
                . '<span class="b">' . \htmlescape($error_type) . ': </span>' . \htmlescape($message) . '</div>';
        } else {
            \trigger_error('Cannot display error in HTTP context for requests that do not accept HTML as a response.', \E_USER_WARNING);
        }
    }
}
