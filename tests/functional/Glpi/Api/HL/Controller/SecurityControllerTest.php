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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Api\HL\Controller\SecurityController;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Glpi\Security\SessionTracker;
use Glpi\Tests\HLAPITestCase;
use User;

class SecurityControllerTest extends HLAPITestCase
{
    public function testGetMyLoginSessions(): void
    {
        $this->assertEquals(0, countElementsInTable('glpi_users_sessionhistories'));
        $this->loginWeb();
        $this->assertEquals(1, countElementsInTable('glpi_users_sessionhistories'));

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->api->call(new Request('GET', '/Security/LoginSession/My'), function ($call) {
            $call->response
                ->isOK()
                ->matchesSchema(SecurityController::getKnownSchemas('3.0.0')['LoginSession'])
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                });
        });

        $this->loginWeb('post-only', 'postonly');
        $this->assertEquals(2, countElementsInTable('glpi_users_sessionhistories'));

        // Should only see their own session
        $this->api->call(new Request('GET', '/Security/LoginSession/My'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('post-only', $content[0]['user']['username']);
                });
        });

        $this->loginWeb();
        $this->api->call(new Request('GET', '/Security/LoginSession/My'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(2, $content);
                    $this->assertEquals(TU_USER, $content[0]['user']['username']);
                    $this->assertEquals(TU_USER, $content[1]['user']['username']);
                });
        });
    }

    public function testGetUserLoginSessions(): void
    {
        $this->assertEquals(0, countElementsInTable('glpi_users_sessionhistories'));
        $this->loginWeb('post-only', 'postonly');
        $this->loginWeb();

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $postonly_users_id = getItemByTypeName(User::class, 'post-only', true);
        $tu_users_id = getItemByTypeName(User::class, TU_USER, true);

        // Super-admin can see all sessions
        $this->api->call(new Request('GET', '/Security/LoginSession/' . $tu_users_id), function ($call) {
            $call->response
                ->isOK()
                ->matchesSchema(SecurityController::getKnownSchemas('3.0.0')['LoginSession'])
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals(TU_USER, $content[0]['user']['username']);
                });
        });
        $this->api->call(new Request('GET', '/Security/LoginSession/' . $postonly_users_id), function ($call) {
            $call->response
                ->isOK()
                ->matchesSchema(SecurityController::getKnownSchemas('3.0.0')['LoginSession'])
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('post-only', $content[0]['user']['username']);
                });
        });

        // Non-super-admin cannot see other users' sessions
        $this->loginWeb('post-only', 'postonly');
        $this->api->call(new Request('GET', '/Security/LoginSession/' . $postonly_users_id), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(2, $content);
                    $this->assertEquals('post-only', $content[0]['user']['username']);
                    $this->assertEquals('post-only', $content[1]['user']['username']);
                });
        });
        $this->api->call(new Request('GET', '/Security/LoginSession/' . $tu_users_id), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(0, $content);
                });
        });
    }

    public function testGetAllLoginSessions(): void
    {
        $this->assertEquals(0, countElementsInTable('glpi_users_sessionhistories'));
        $this->loginWeb('post-only', 'postonly');
        $this->loginWeb();

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        // Super-admin can see all sessions
        $this->api->call(new Request('GET', '/Security/LoginSession/All'), function ($call) {
            $call->response
                ->isOK()
                ->matchesSchema(SecurityController::getKnownSchemas('3.0.0')['LoginSession'])
                ->jsonContent(function ($content) {
                    $this->assertCount(2, $content);
                    $this->assertEquals('post-only', $content[0]['user']['username']);
                    $this->assertEquals(TU_USER, $content[1]['user']['username']);
                });
        });

        // Non-super-admin cannot see all sessions
        $this->loginWeb('post-only', 'postonly');
        $this->api->call(new Request('GET', '/Security/LoginSession/All'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(2, $content);
                    $this->assertEquals('post-only', $content[0]['user']['username']);
                    $this->assertEquals('post-only', $content[1]['user']['username']);
                });
        });
    }

    public function testRevokeSession(): void
    {
        $this->loginWeb('post-only', 'postonly');
        $this->loginWeb();
        $this->loginWeb('post-only', 'postonly');
        $this->loginWeb();

        $postonly_users_id = getItemByTypeName(User::class, 'post-only', true);
        $tu_users_id = getItemByTypeName(User::class, TU_USER, true);

        $sessions = array_values(getAllDataFromTable('glpi_users_sessionhistories', ['logout_reason' => null]));
        $this->assertCount(4, $sessions);

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        // Super-admin can revoke any session. Try to revoke one for the post-only user and one for the super-admin user
        $this->assertEquals($postonly_users_id, $sessions[0]['users_id']);
        $this->assertEquals($tu_users_id, $sessions[1]['users_id']);
        $postonly_session_id = $sessions[0]['login_session_uid'];
        $tu_session_id = $sessions[1]['login_session_uid'];

        $this->api->call(new Request('DELETE', '/Security/LoginSession/' . $postonly_session_id), function ($call) {
            $call->response->isOK();
        });
        $this->api->call(new Request('DELETE', '/Security/LoginSession/' . $tu_session_id), function ($call) {
            $call->response->isOK();
        });

        $this->assertCount(2, getAllDataFromTable('glpi_users_sessionhistories', ['logout_reason' => null]));

        $this->loginWeb('post-only', 'postonly');
        $this->assertEquals($postonly_users_id, $sessions[2]['users_id']);
        $this->assertEquals($tu_users_id, $sessions[3]['users_id']);
        $postonly_session_id = $sessions[2]['login_session_uid'];
        $tu_session_id = $sessions[3]['login_session_uid'];

        // Non-super-admin can only revoke their own sessions. Try to revoke one for the post-only user and one for the super-admin user
        $this->api->call(new Request('DELETE', '/Security/LoginSession/' . $postonly_session_id), function ($call) {
            $call->response->isOK();
        });
        $this->api->call(new Request('DELETE', '/Security/LoginSession/' . $tu_session_id), function ($call) {
            $call->response->isAccessDenied();
        });

        // A super-admin session should remain, as well as the latest post-only session
        $remaining_sessions = array_values(getAllDataFromTable('glpi_users_sessionhistories', ['logout_reason' => null]));
        $this->assertCount(2, $remaining_sessions);
        $this->assertEquals($tu_users_id, $remaining_sessions[0]['users_id']);
        $this->assertEquals($postonly_users_id, $remaining_sessions[1]['users_id']);

        // there should be 3 sessions with an admin logout reason
        $this->assertCount(3, getAllDataFromTable('glpi_users_sessionhistories', [
            'logout_reason' => SessionTracker::REVOKE_REASON_ADMIN,
        ]));
    }
}
