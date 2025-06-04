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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Progress\ProgressStorage;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\System\Requirement\DatabaseTablesEngine;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;
use Migration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;
use Update;
use Glpi\Progress\StoredProgressIndicator;

use function Safe\fastcgi_finish_request;
use function Safe\ini_set;
use function Safe\ob_end_clean;
use function Safe\session_write_close;

class InstallController extends AbstractController
{
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

        return $this->getProgressIndicatorResponse(
            $progress_indicator,
            function () use ($progress_indicator) {
                try {
                    Toolbox::createSchema($_SESSION["glpilanguage"], null, $progress_indicator);
                } catch (\Throwable $exception) {
                    // Try to remove the config file, to be able to restart the process.
                    @unlink(GLPI_CONFIG_DIR . '/config_db.php');

                    throw $exception; // Forward the exception
                }
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
            throw new \RuntimeException('Missing database configuration file.');
        } else {
            include_once(GLPI_CONFIG_DIR . '/config_db.php');
            if (!\class_exists(DB::class)) {
                throw new \RuntimeException('Invalid database configuration file.');
            }
        }

        $progress_indicator = $this->progress_storage->spawnProgressIndicator();
        $logger             = $this->logger;

        return $this->getProgressIndicatorResponse(
            $progress_indicator,
            function () use ($logger, $progress_indicator) {
                /** @var \DBmysql $DB */
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

    private function getProgressIndicatorResponse(
        StoredProgressIndicator $progress_indicator,
        callable $inner_callable
    ): Response {
        ini_set('max_execution_time', '300'); // Allow up to 5 minutes to prevent unexpected timeout
        session_write_close(); // Prevent the session file lock to block the progress check requests

        // Be sure to disable the output buffering.
        // It is necessary to make the `flush()` works as expected.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return new StreamedResponse(
            function () use ($progress_indicator, $inner_callable) {
                echo $progress_indicator->getStorageKey();

                // Send headers and content.
                // The browser will consider that the response is complete due to the `Connection: close` header
                // and will not have to wait for operation to finish to consider the request as ended.
                flush();

                if (\function_exists('fastcgi_finish_request')) {
                    // In PHP-FPM context, it indicates to the client (Apache, Nginx, ...)
                    // that the request is finished.
                    fastcgi_finish_request();
                }

                // Prevent the request to be terminated by the client.
                \ignore_user_abort(true);

                try {
                    $inner_callable();
                } catch (\Throwable $e) {
                    $progress_indicator->fail();
                }
            },
            headers: [
                'Content-Type'   => 'text/html',
                'Content-Length' => \strlen($progress_indicator->getStorageKey()),
                'Cache-Control'  => 'no-cache,no-store',
                'Pragma'         => 'no-cache',
                'Connection'     => 'close',
            ]
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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql|null $DB
         */
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
