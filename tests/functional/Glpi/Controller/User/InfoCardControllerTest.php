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

namespace tests\units\Glpi\Controller\User;

use Glpi\Controller\User\InfoCardController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use User;

#[Group('reauth')]
final class InfoCardControllerTest extends DbTestCase
{
    use ReAuthTrait;

    public function tearDown(): void
    {
        $this->restoreWebContext();

        parent::tearDown();
    }

    public function testInfoCardIsDisplayedWithoutReauthentication(): void
    {
        $this->login();
        $users_id = getItemByTypeName(User::class, 'normal', true);

        $this->fakeWebContext('/User/InfoCard/' . $users_id, xml_http_request: true);
        $this->setReauthenticated(false);

        $response = (new InfoCardController())->__invoke(Request::create('/User/InfoCard/' . $users_id), $users_id);

        $this->assertStringContainsString('normal', (string) $response->getContent());
    }

    public function testInfoCardIsDeniedWithoutReadRight(): void
    {
        $this->login('post-only', 'postonly');
        $users_id = getItemByTypeName(User::class, 'normal', true);

        $this->fakeWebContext('/User/InfoCard/' . $users_id, xml_http_request: true);
        $this->setReauthenticated(false);

        $this->expectException(AccessDeniedHttpException::class);

        (new InfoCardController())->__invoke(Request::create('/User/InfoCard/' . $users_id), $users_id);
    }
}
