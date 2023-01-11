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

namespace Glpi\Http;

use Toolbox;

/**
 * @since 10.0.0
 */
class Response
{
    /**
     * "application/json" content type.
     */
    const CONTENT_TYPE_JSON = 'application/json';

    /**
     * "text/html" content type.
     */
    const CONTENT_TYPE_TEXT_HTML = 'text/html';

    /**
     * "text/plain" content type.
     */
    const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';

    /**
     * Send the given HTTP code then die with the error message in the given format.
     *
     * @param int     $code          HTTP code to set for the response
     * @param string  $message       Error message to send
     * @param string  $content_type  Response content type
     *
     * @return void
     */
    public static function sendError(int $code, string $message, string $content_type = self::CONTENT_TYPE_JSON): void
    {

        switch ($content_type) {
            case self::CONTENT_TYPE_JSON:
                $output = json_encode(['message' => $message]);
                break;

            case self::CONTENT_TYPE_TEXT_HTML:
            default:
                $output = $message;
                break;
        }

        header(sprintf('Content-Type: %s; charset=UTF-8', $content_type), true, $code);

        Toolbox::logDebug($message);

        die($output);
    }
}
