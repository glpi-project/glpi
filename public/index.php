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
use Glpi\Application\ResourcesChecker;
use Glpi\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (version_compare(PHP_VERSION, '8.2.0', '<') || version_compare(PHP_VERSION, '8.5.999', '>')) {
    exit('PHP version must be between 8.2 and 8.5.');
}

// Check the resources state before trying to instanciate the Kernel.
// It must be done here as this check must be done even when the Kernel
// cannot be instanciated due to missing dependencies.
require_once dirname(__DIR__) . '/src/Glpi/Application/ResourcesChecker.php';
(new ResourcesChecker(dirname(__DIR__)))->checkResources();

require_once dirname(__DIR__) . '/vendor/autoload.php';

// When the PHP built-in server is used, if a valid resource is requested (e.g. `/front/ticket.php`),
// `$_SERVER['SCRIPT_NAME']` will match the requested file instead of being `/index.php`.
//
// To make the Symfony request prefix/path computation working as expected, it is necessary to fix these values.
// See https://github.com/symfony-cli/symfony-cli/blob/b5c22ed3d10c79784cbb7a771af94f683e8f1795/local/php/php_builtin_server.go#L53-L57
$self_script = DIRECTORY_SEPARATOR . basename(__FILE__);
if (php_sapi_name() === 'cli-server' && $_SERVER['SCRIPT_NAME'] !== $self_script) {
    $_SERVER['DOCUMENT_ROOT']   = __DIR__;
    $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . $self_script;
    $_SERVER['SCRIPT_NAME']     = $self_script;
    $_SERVER['PHP_SELF']        = $self_script;
}
unset($self_script);

$kernel = new Kernel();

$request = Request::createFromGlobals();

$response = $kernel->handle($request);

$kernel->sendResponse($request, $response);

$kernel->terminate($request, $response);
