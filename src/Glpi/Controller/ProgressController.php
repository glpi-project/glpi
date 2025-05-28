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

use Glpi\Http\Firewall;
use Glpi\Progress\ProgressStorage;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProgressController extends AbstractController
{
    public function __construct(
        private readonly ProgressStorage $progress_storage,
    ) {}

    #[Route("/progress/check/{key}", methods: 'GET')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function check(string $key): Response
    {
        $progress = $this->progress_storage->getProgressIndicator($key);

        if ($progress === null) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'started_at'            => $progress->getStartedAt()->format('c'),
            'updated_at'            => $progress->getUpdatedAt()->format('c'),
            'ended_at'              => $progress->getEndedAt()?->format('c'),
            'failed'                => $progress->hasFailed(),
            'current_step'          => $progress->getCurrentStep(),
            'max_steps'             => $progress->getMaxSteps(),
            'progress_bar_message'  => $progress->getProgressBarMessage(),
            'messages'              => $progress->getMessages(),
        ]);
    }
}
