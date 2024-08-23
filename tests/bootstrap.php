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

use Glpi\Application\ErrorHandler;
use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

ini_set('display_errors', 'On'); // Ensure errors happening during test suite bootstraping are always displayed
error_reporting(E_ALL);

define('GLPI_ROOT', __DIR__ . '/../');
define('GLPI_CONFIG_DIR', getenv('GLPI_CONFIG_DIR') ?: __DIR__ . '/config');
define('GLPI_VAR_DIR', getenv('GLPI_VAR_DIR') ?: __DIR__ . '/files');
define('GLPI_URI', getenv('GLPI_URI') ?: 'http://localhost:8088');
define('GLPI_STRICT_DEPRECATED', true); //enable strict depreciations

define(
    'PLUGINS_DIRECTORIES',
    [
        GLPI_ROOT . '/plugins',
        GLPI_ROOT . '/tests/fixtures/plugins',
    ]
);

define(
    'GLPI_SERVERSIDE_URL_ALLOWLIST',
    [
        '/^(https?|feed):\/\/[^@:]+(\/.*)?$/', // default allowlist entry
        '/^file:\/\/.*\.ics$/', // calendar mockups
    ]
);

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

global $CFG_GLPI, $GLPI_CACHE;

include(GLPI_ROOT . "/inc/based_config.php");

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    die("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --config-dir=" . GLPI_CONFIG_DIR . " ...\n\n");
}

\Glpi\Tests\BootstrapUtils::initVarDirectories();

include_once __DIR__ . '/../inc/includes.php';

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
   // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
   // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

// Errors/exceptions that are not explicitely validated by `$this->error()` or `$this->exception` asserter will already make test fails.
// There is no need to pollute the output with error messages.
ini_set('display_errors', 'Off');
ErrorHandler::getInstance()->disableOutput();
// To prevent errors caught by `error` asserter to also generate logs, unregister GLPI error handler.
// Errors that are pushed directly to logs (SQL errors/warnings for instance) will still have to be explicitely
// validated by `$this->has*LogRecord*()` asserters, otherwise it will make make test fails.
set_error_handler(null);

include_once __DIR__ . '/GLPITestCase.php';
include_once __DIR__ . '/DbTestCase.php';
include_once __DIR__ . '/CsvTestCase.php';
include_once __DIR__ . '/APIBaseClass.php';
include_once __DIR__ . '/FrontBaseClass.php';
include_once __DIR__ . '/RuleBuilder.php';
include_once __DIR__ . '/functional/Glpi/ContentTemplates/Parameters/AbstractParameters.php';

// check folder exists instead of class_exists('\GuzzleHttp\Client'), to prevent global includes
if (file_exists(__DIR__ . '/../vendor/autoload.php') && !file_exists(__DIR__ . '/../vendor/guzzlehttp/guzzle')) {
    die("\nDevelopment dependencies not found\n\nrun: composer install -o\n\n");
}

loadDataset();
