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

namespace Glpi\Controller\Security;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\SessionTracker;
use Html;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class SessionTrackerController extends AbstractController
{
    #[Route(
        path: "/front/Security/SessionList",
        name: "security_session_list",
        methods: ["GET"],
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function showSessionList(Request $request): Response
    {
        $sessionTracker = new SessionTracker();
        Html::header(...SessionTracker::getHeaderParameters());
        $sessionTracker->showSessionList();
        Html::footer();
        return new Response();
    }

    #[Route(
        path: "/Security/Sessions",
        name: "security_sessions",
        methods: ["GET"],
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function getSessions(Request $request): Response
    {
        $users_id = $request->query->getInt('users_id', 0);

        if ($users_id !== Session::getLoginUserID() && !Session::haveRight('config', UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        return new JsonResponse(
            (new SessionTracker())->getSessions($users_id)
        );
    }
}
