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

namespace tests\units\Glpi\Controller;

use CommonDBTM;
use Glpi\Controller\ShareAccessController;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Security\ShareTokenManager;
use Glpi\ShareableInterface;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use User;

final class ShareAccessControllerTest extends DbTestCase
{
    private function createKnowbaseItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'users_id'    => getItemByTypeName(User::class, TU_USER, true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => $this->getUniqueString(),
            'answer'      => '<p>Test content</p>',
        ]);
    }

    private function createToken(ShareableInterface&CommonDBTM $item): ShareToken
    {
        return $this->createItem(ShareToken::class, [
            'itemtype'  => $item::class,
            'items_id'  => $item->getID(),
            'name'      => $this->getUniqueString(),
            'is_active' => 1,
        ]);
    }

    public function testAnonymousRedirectHasReferrerPolicyHeader(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);
        $this->logOut();

        $plain = (new ShareTokenManager())->decryptToken((string) $token->fields['token']);
        $request = Request::create(
            '/Share/' . $plain,
            'GET',
        );

        $controller = new ShareAccessController();
        $response = $controller->__invoke($request, $plain);

        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function testAuthenticatedRedirectHasReferrerPolicyHeader(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);

        $plain = (new ShareTokenManager())->decryptToken((string) $token->fields['token']);
        $request = Request::create(
            '/Share/' . $plain,
            'GET',
        );

        $controller = new ShareAccessController();
        $response = $controller->__invoke($request, $plain);

        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function testUnknownTokenIsRejected(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $unknown = str_repeat('a', 64);
        $request = Request::create('/Share/' . $unknown, 'GET');
        (new ShareAccessController())->__invoke($request, $unknown);
    }
}
