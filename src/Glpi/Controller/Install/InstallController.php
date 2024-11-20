<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Controller\Install;

use Glpi\Cache\CacheManager;
use Glpi\Http\Firewall;
use Glpi\Http\HeaderlessStreamedResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Toolbox;
use Glpi\Controller\AbstractController;
use Glpi\Progress\ProgressChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class InstallController extends AbstractController
{
    private const STORED_PROGRESS_KEY = 'install_db_inserts';

    public function __construct(
        private readonly ProgressChecker $progressChecker,
    ) {
    }

    #[Route("/install/database_setup/start_db_inserts", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function start_inserts(): Response
    {
        $progressChecker = $this->progressChecker;

        $this->progressChecker->startProgress(self::STORED_PROGRESS_KEY);

        return new StreamedResponse(function () use ($progressChecker) {
            try {
                $progress_callback = static function (?int $current = null, ?int $max = null, ?string $data = null) use ($progressChecker) {
                    $progress = $progressChecker->getCurrentProgress(self::STORED_PROGRESS_KEY);
                    $progress->current += $current ?? 1;
                    $progress->max = (int) $max;
                    $progress->data .= $data ? ("\n" . $data) : '';
                    $progressChecker->save($progress);
                };
                Toolbox::createSchema($_SESSION["glpilanguage"], null, $progress_callback);
            } catch (\Throwable $e) {
                echo "<p>"
                    . sprintf(
                        __('An error occurred during the database initialization. The error was: %s'),
                        '<br />' . $e->getMessage()
                    )
                    . "</p>";
                @unlink(GLPI_CONFIG_DIR . '/config_db.php'); // try to remove the config file, to be able to restart the process
            } finally {
                $this->progressChecker->endProgress(self::STORED_PROGRESS_KEY);
            }
        });
    }

    #[Route("/install/database_setup/check_progress", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function check_progress(): Response
    {
        if (!$this->progressChecker->hasProgress(self::STORED_PROGRESS_KEY)) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse($this->progressChecker->getCurrentProgress(self::STORED_PROGRESS_KEY));
    }
}
