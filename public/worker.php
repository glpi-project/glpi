<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * FrankenPHP Worker Mode entry point.
 *
 * This script is designed to run GLPI in FrankenPHP's persistent worker mode,
 * keeping the Symfony kernel booted across requests for better performance.
 *
 * @see https://frankenphp.dev/docs/worker/
 */

use Glpi\Application\ResourcesChecker;
use Glpi\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

// Version check must be first
if (version_compare(PHP_VERSION, '8.2.0', '<') || version_compare(PHP_VERSION, '8.5.999', '>')) {
    exit('PHP version must be between 8.2 and 8.5.');
}

// Check resources before Kernel instantiation
require_once dirname(__DIR__) . '/src/Glpi/Application/ResourcesChecker.php';
(new ResourcesChecker(dirname(__DIR__)))->checkResources();

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Boot kernel once, outside the worker loop
$kernel = new Kernel();
$kernel->boot();

// Track requests for periodic kernel refresh
$requestCount = 0;
$maxRequests = (int) ($_SERVER['GLPI_WORKER_MAX_REQUESTS'] ?? 500);

// Request handler callback
$handleRequest = static function () use (&$kernel, &$requestCount, $maxRequests): void {
    $request = Request::createFromGlobals();

    try {
        $response = $kernel->handle($request);

        $kernel->sendResponse($request, $response);

        $kernel->terminate($request, $response);
    } finally {
        // Periodic kernel refresh to prevent memory leaks
        if (++$requestCount >= $maxRequests) {
            $kernel->shutdown();
            $kernel = new Kernel();
            $kernel->boot();
            $requestCount = 0;
        }

        // Perform garbage collection periodically
        if ($requestCount % 50 === 0) {
            gc_collect_cycles();
        }
    }
};

// Worker loop - handles requests persistently
while (frankenphp_handle_request($handleRequest)) {
}
