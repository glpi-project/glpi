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

use Glpi\Application\Environment;
use Glpi\Application\ResourcesChecker;
use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Glpi\Kernel\Kernel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

define('GLPI_URI', getenv('GLPI_URI') ?: 'http://localhost');

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

define('FIXTURE_DIR', __DIR__ . "/../tests/fixtures");

// Check the resources state before trying to be sure that the tests are executed with up-to-date dependencies.
require_once dirname(__DIR__) . '/src/Glpi/Application/ResourcesChecker.php';
(new ResourcesChecker(dirname(__DIR__)))->checkResources();

// Make sure cached content like twig template is cleared before running the tests.
// It seems calling $cache_manager->resetAllCaches(); mess up with the kernel
// leading to some issues. It is safer to use the console directly as it goes
// throught another process.
exec(sprintf("php %s cache:clear --env='testing'", realpath(GLPI_ROOT . '/bin/console')));

global $GLPI_CACHE;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel(Environment::TESTING->value);
$kernel->boot();

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    echo("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --env=testing ...\n\n");
    exit(1);
}
if (Update::isUpdateMandatory()) {
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

# TODO: register a proper PSR4 autoloader for these files.
include_once __DIR__ . '/GLPITestCase.php';
include_once __DIR__ . '/DbTestCase.php';
include_once __DIR__ . '/CsvTestCase.php';
include_once __DIR__ . '/FrontBaseClass.php';
include_once __DIR__ . '/RuleBuilder.php';
include_once __DIR__ . '/InventoryTestCase.php';
include_once __DIR__ . '/abstracts/AbstractCommonItilObject_ItemTest.php';
include_once __DIR__ . '/abstracts/CommonITILRecurrentTest.php';
//include_once __DIR__ . '/functional/Glpi/ContentTemplates/Parameters/AbstractParameters.php';
include_once __DIR__ . '/AbstractRightsDropdown.php';
include_once __DIR__ . '/CommonDropdown.php';
include_once __DIR__ . '/HLAPITestCase.php';
require_once __DIR__ . '/functional/Glpi/Form/Condition/ConditionHandler/AbstractConditionHandler.php';
include_once __DIR__ . '/functional/CommonITILTaskTestCase.php';

loadDataset();

$tu_oauth_client = new OAuthClient();
$tu_oauth_client->getFromDBByCrit(['name' => 'Test OAuth Client']);
define('TU_OAUTH_CLIENT_ID', $tu_oauth_client->fields['identifier']);
define('TU_OAUTH_CLIENT_SECRET', $tu_oauth_client->fields['secret']);
