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

namespace Glpi\Controller;

use DB;
use Glpi\Cache\CacheManager;
use Glpi\Controller\Traits\AsyncOperationProgressControllerTrait;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Progress\ProgressStorage;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\System\Requirement\DatabaseTablesEngine;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;
use Migration;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;
use Update;

use function Safe\unlink;

class InstallController extends AbstractController
{
    use AsyncOperationProgressControllerTrait;

    public function __construct(
        private readonly ProgressStorage $progress_storage,
        private readonly LoggerInterface $logger
    ) {}

    #[Route("/Install/InitDatabase", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function initDatabase(): Response
    {
        if (!isset($_SESSION['can_process_install'])) {
            throw new AccessDeniedHttpException();
        }

        $progress_indicator = $this->progress_storage->spawnProgressIndicator();

        $this->progress_storage->registerFailureCallback(
            $progress_indicator->getStorageKey(),
            function () {
                // Try to remove the config file, to be able to restart the process.
                @unlink(GLPI_CONFIG_DIR . '/config_db.php');
            }
        );

        return $this->getProgressInitResponse(
            $progress_indicator,
            function () use ($progress_indicator) {
                Toolbox::createSchema($_SESSION["glpilanguage"], null, $progress_indicator);
            }
        );
    }

    #[Route("/Install/UpdateDatabase", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function updateDatabase(): Response
    {
        if (!isset($_SESSION['can_process_update'])) {
            throw new AccessDeniedHttpException();
        }

        if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
            throw new RuntimeException('Missing database configuration file.');
        } else {
            include_once(GLPI_CONFIG_DIR . '/config_db.php');
            if (!\class_exists(DB::class)) {
                throw new RuntimeException('Invalid database configuration file.');
            }
        }

        $progress_indicator = $this->progress_storage->spawnProgressIndicator();
        $logger             = $this->logger;

        return $this->getProgressInitResponse(
            $progress_indicator,
            function () use ($logger, $progress_indicator) {
                global $DB;
                $DB = new DB();
                $DB->disableTableCaching(); // Prevents issues on fieldExists upgrading from old versions

                $update = new Update($DB);
                $update->setMigration(new Migration(GLPI_VERSION, $progress_indicator));
                $update->setLogger($logger);

                $success = $update->doUpdates(
                    current_version: $update->getCurrents()['version'],
                    progress_indicator: $progress_indicator
                );

                if ($success === false) {
                    $progress_indicator->fail();
                }

                // Force cache cleaning to ensure it will not contain stale data
                (new CacheManager())->resetAllCaches();
            }
        );
    }

    /**
     * Internal route that displays the "install required" page.
     */
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function installRequired(): Response
    {
        return $this->render('install/install.install_required.html.twig');
    }

    /**
     * Internal route that displays the "update required" page.
     */
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function updateRequired(): Response
    {
        global $CFG_GLPI, $DB;

        $_SESSION['can_process_update'] = true;

        $requirements = (new RequirementsManager())->getCoreRequirementList($DB);
        $requirements->add(new DatabaseTablesEngine($DB));

        return $this->render(
            'install/update.need_update.html.twig',
            [
                'core_requirements' => $requirements,
                'is_stable_release' => VersionParser::isStableRelease(GLPI_VERSION),
                'is_dev_version'    => VersionParser::isDevVersion(GLPI_VERSION),
                'is_outdated'       => version_compare(
                    VersionParser::getNormalizedVersion($CFG_GLPI['version'] ?? '0.0.0-dev'),
                    VersionParser::getNormalizedVersion(GLPI_VERSION),
                    '>'
                ),
            ]
        );
    }
}
