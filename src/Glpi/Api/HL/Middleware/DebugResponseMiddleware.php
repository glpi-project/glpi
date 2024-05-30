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

namespace Glpi\Api\HL\Middleware;

use Glpi\Api\HL\RoutePath;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GuzzleHttp\Psr7\Utils;
use Symfony\Component\DomCrawler\Crawler;

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
        $use_mode = isset($_SESSION['glpi_use_mode']) ? (int) $_SESSION['glpi_use_mode'] : \Session::NORMAL_MODE;
        if ($use_mode !== \Session::DEBUG_MODE) {
            $next($input);
            return;
        }
        $outputs = [];
        // Go through all output buffers
        while (ob_get_level() > 0) {
            $outputs[] = ob_get_clean();
        }
        $debug_messages = [];
        // If the output matches an HTML debug alert, extract the inner text and add it to the array
        foreach ($outputs as $output) {
            if (!is_string($output)) {
                continue;
            }
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
                $msg = htmlentities($debug_message, ENT_QUOTES, 'UTF-8');
                $header_value .= '"' . $msg . '",';
            }
            // remove the last comma from the header value
            $header_value = rtrim($header_value, ',');
            $input->response = $input->response->withHeader('X-Debug-Messages', $header_value);
        }

        // Pretty print JSON responses
        if ($input->response instanceof JSONResponse) {
            $content = $input->response->getBody();
            $pretty_print_json = json_encode(json_decode($content), JSON_PRETTY_PRINT);
            $input->response = $input->response->withBody(Utils::streamFor($pretty_print_json));
        }

        // Call the next middleware
        $next($input);
    }
}
