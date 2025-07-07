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

namespace Glpi\Http;

use Toolbox;

use function Safe\json_encode;

/**
 * @since 10.0.0
 */
class Response extends \GuzzleHttp\Psr7\Response
{
    /**
     * "application/json" content type.
     */
    public const CONTENT_TYPE_JSON = 'application/json';

    /**
     * "text/html" content type.
     */
    public const CONTENT_TYPE_TEXT_HTML = 'text/html';

    /**
     * "text/plain" content type.
     */
    public const CONTENT_TYPE_TEXT_PLAIN = 'text/plain';

    /**
     * Send the given HTTP code then die with the error message in the given format.
     *
     * @param int     $code          HTTP code to set for the response
     * @param string  $message       Error message to send
     * @param string  $content_type  Response content type
     *
     * @return never
     *
     * @deprecated 11.0.0
     */
    public static function sendError(int $code, string $message, string $content_type = self::CONTENT_TYPE_JSON): never
    {
        Toolbox::deprecated('Response::sendError() is deprecated. Throw a `Glpi\Exception\Http\*HttpException` exception instead.');

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

        echo($output);
        exit(1); // @phpstan-ignore glpi.forbidExit (Deprecated scope)
    }

    /**
     * @deprecated 11.0.0
     */
    public function sendHeaders(): Response
    {
        if (headers_sent()) {
            return $this;
        }
        $headers = $this->getHeaders();
        foreach ($headers as $name => $values) {
            header(sprintf('%s: %s', $name, implode(', ', $values)), true);
        }
        http_response_code($this->getStatusCode()); // @phpstan-ignore glpi.forbidHttpResponseCode (Deprecated scope)
        return $this;
    }

    /**
     * @deprecated 11.0.0
     */
    public function sendContent(): Response
    {
        echo $this->getBody();
        return $this;
    }

    /**
     * @deprecated 11.0.0
     */
    public function send(): Response
    {
        return $this->sendHeaders()
            ->sendContent();
    }
}
