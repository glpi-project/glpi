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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Progress\ProgressStorage;
use Glpi\Progress\StoredProgressIndicator;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Toolbox;

class InstallController extends AbstractController
{
    public const PROGRESS_KEY_INIT_DATABASE = 'init_database';

    public function __construct(
        private readonly ProgressStorage $progress_storage,
    ) {
    }

    #[Route("/install/init_database", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function start_inserts(): Response
    {
        if (!isset($_SESSION['can_process_install'])) {
            throw new AccessDeniedHttpException();
        }

        $progress_indicator = new StoredProgressIndicator($this->progress_storage, self::PROGRESS_KEY_INIT_DATABASE);

        return new StreamedResponse(function () use ($progress_indicator) {
            try {
                Toolbox::createSchema($_SESSION["glpilanguage"], null, $progress_indicator);
            } catch (\Throwable $e) {
                $progress_indicator->fail();
                // Try to remove the config file, to be able to restart the process.
                @unlink(GLPI_CONFIG_DIR . '/config_db.php');
            }
        });
    }
}
