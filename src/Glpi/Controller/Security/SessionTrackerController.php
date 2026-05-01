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
        $users_id = $request->query->getInt('users_id', 0);
        $filters = [
            'user' => $request->query->get('user', ''),
            'status' => $request->query->get('status', 'active'),
            'type' => $request->query->get('type', 'all'),
            'ip' => $request->query->get('ip', ''),
        ];
        $start = $request->query->getInt('start', 0);

        if ($users_id !== Session::getLoginUserID() && !Session::haveRight('config', UPDATE)) {
            throw new AccessDeniedHttpException();
        }
        if ($users_id > 0) {
            unset($filters['user']);
        }

        $sessionTracker = new SessionTracker();
        Html::header(...SessionTracker::getHeaderParameters());
        $sessionTracker->showSessionList($users_id, $filters, $start);
        Html::footer();
        return new Response();
    }

    #[Route(
        path: "/Security/Sessions/{session_token_hash}/Revoke",
        name: "security_sessions_revoke",
        methods: ["POST"],
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function revokeSession(Request $request): Response
    {
        //TODO
        return new Response();
    }

    #[Route(
        path: "/Security/Sessions/All/Revoke",
        name: "security_sessions_revokeall",
        methods: ["POST"],
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function revokeAllSessions(Request $request): Response
    {
        //TODO
        return new Response();
    }
}
