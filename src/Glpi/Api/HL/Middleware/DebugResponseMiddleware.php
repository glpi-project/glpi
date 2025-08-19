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

namespace Glpi\Api\HL\Middleware;

use Glpi\Http\JSONResponse;
use GuzzleHttp\Psr7\Utils;
use Safe\Exceptions\OutcontrolException;
use Session;
use Symfony\Component\DomCrawler\Crawler;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\ob_get_clean;

class DebugResponseMiddleware extends AbstractMiddleware implements ResponseMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        if (!isAPI()) {
            // If someone uses the Router in a non-API context, we don't want to mess with the body formatting or do anything else.
            // See Webhooks feature for an example of this non-API context usage.
            $next($input);
            return;
        }
        $use_mode = isset($_SESSION['glpi_use_mode']) ? (int) $_SESSION['glpi_use_mode'] : Session::NORMAL_MODE;
        if ($use_mode !== Session::DEBUG_MODE) {
            $next($input);
            return;
        }
        $outputs = [];
        // Go through all output buffers
        while (ob_get_level() > 0) {
            try {
                $outputs[] = ob_get_clean();
            } catch (OutcontrolException $e) {
                //just contineu, seems not an error.
            }
        }
        $debug_messages = [];
        // If the output matches an HTML debug alert, extract the inner text and add it to the array
        foreach ($outputs as $output) {
            $crawler = new Crawler($output);
            $node = $crawler->filter('div.glpi-debug-alert');
            if ($node->count() > 0) {
                $debug_messages[] = $node->text();
            }
        }
        // If there are debug messages, add them to the response
        if (count($debug_messages) > 0) {
            $header_value = '';
            foreach ($debug_messages as $debug_message) {
                // escape quotes in the message, quote the message, and add it to the header value, and append a comma to the end
                $msg = htmlescape($debug_message);
                $header_value .= '"' . $msg . '",';
            }
            // remove the last comma from the header value
            $header_value = rtrim($header_value, ',');
            $input->response = $input->response->withHeader('X-Debug-Messages', $header_value);
        }

        // Pretty print JSON responses
        if ($input->response instanceof JSONResponse) {
            $content = (string) $input->response->getBody();
            if (!empty($content)) {
                $pretty_print_json = json_encode(json_decode($content), JSON_PRETTY_PRINT);
                $input->response = $input->response->withBody(Utils::streamFor($pretty_print_json));
            }
        }

        // Call the next middleware
        $next($input);
    }
}
