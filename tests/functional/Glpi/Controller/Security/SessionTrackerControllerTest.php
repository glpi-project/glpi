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

namespace tests\units\Glpi\Controller\Security;

use Glpi\Controller\Security\SessionTrackerController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SessionTrackerControllerTest extends DbTestCase
{
    private function getRequest(string $login_session_uid): Request
    {
        $request = Request::create('/Security/Session/' . $login_session_uid . '/Revoke', 'POST');
        $request->attributes->set('login_session_uid', $login_session_uid);
        return $request;
    }

    public function testRevokeOwnSession(): void
    {
        global $DB;

        $this->login('normal', 'normal');
        $login_session_uid = $DB->request([
            'SELECT' => 'login_session_uid',
            'FROM'   => 'glpi_users_sessions',
            'WHERE'  => ['users_id' => $_SESSION['glpiID']],
            'ORDER'  => ['created_at DESC'],
        ])->current()['login_session_uid'];

        $controller = new SessionTrackerController($this->getDbConnection());
        $response = $controller->revokeSession($this->getRequest($login_session_uid));

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame(0, countElementsInTable('glpi_users_sessions', ['login_session_uid' => $login_session_uid]));
    }

    public function testRevokeUnknownSessionIsDenied(): void
    {
        $this->login('normal', 'normal');

        $controller = new SessionTrackerController($this->getDbConnection());

        $this->expectException(AccessDeniedHttpException::class);
        $controller->revokeSession($this->getRequest('00000000000000000000000000000000'));
    }
}
