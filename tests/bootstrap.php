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

use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Glpi\Kernel\Kernel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

define('GLPI_URI', getenv('GLPI_URI') ?: 'http://localhost:80');
define('GLPI_STRICT_DEPRECATED', true); //enable strict depreciations

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

global $GLPI_CACHE;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel('testing');
$kernel->boot();

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    echo("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --env=testing ...\n\n");
    exit(1);
}
if (!defined('SKIP_UPDATES') && !Update::isDbUpToDate()) {
    echo 'The GLPI codebase has been updated. The update of the GLPI database is necessary.' . PHP_EOL;
    exit(1);
}

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
   // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
   // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

// To prevent errors caught by `error` asserter to also generate logs, unregister GLPI error handler.
// Errors that are pushed directly to logs (SQL errors/warnings for instance) will still have to be explicitly
// validated by `$this->has*LogRecord*()` asserters, otherwise it will make test fails.
set_error_handler(null);

include_once __DIR__ . '/GLPITestCase.php';
include_once __DIR__ . '/DbTestCase.php';
include_once __DIR__ . '/FrontBaseClass.php';
include_once __DIR__ . '/RuleBuilder.php';

loadDataset();

$tu_oauth_client = new OAuthClient();
$tu_oauth_client->getFromDBByCrit(['name' => 'Test OAuth Client']);
define('TU_OAUTH_CLIENT_ID', $tu_oauth_client->fields['identifier']);
define('TU_OAUTH_CLIENT_SECRET', $tu_oauth_client->fields['secret']);
