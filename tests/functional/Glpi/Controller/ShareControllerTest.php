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

use Glpi\Controller\ShareAccessController;
use Glpi\Controller\ShareViewerController;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use User;

final class ShareControllerTest extends DbTestCase
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

    public function testShareAccessControllerAnonymousRedirectHasReferrerPolicyHeader(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $this->logOut();

        $controller = new ShareAccessController();
        $response = $controller->__invoke($token->fields['token']);

        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function testShareAccessControllerAnonymousRedirectRespectsRootDoc(): void
    {
        global $CFG_GLPI;

        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $this->logOut();

        $saved_root_doc = $CFG_GLPI['root_doc'];
        $CFG_GLPI['root_doc'] = '/glpi';
        try {
            $controller = new ShareAccessController();
            $response = $controller->__invoke($token->fields['token']);

            $location = $response->headers->get('Location');
            $this->assertStringStartsWith(
                '/glpi/Share/View/',
                $location,
                'Anonymous redirect must include root_doc prefix for subdirectory installs'
            );
        } finally {
            $CFG_GLPI['root_doc'] = $saved_root_doc;
        }
    }

    public function testShareAccessControllerAuthenticatedRedirectHasReferrerPolicyHeader(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $controller = new ShareAccessController();
        $response = $controller->__invoke($token->fields['token']);

        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function testShareViewerControllerResponseHasReferrerPolicyHeader(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $this->logOut();

        $request = Request::create(
            '/Share/View/KnowbaseItem/' . $kb->getID(),
            'GET',
            ['t' => $token->fields['token']]
        );

        $controller = new ShareViewerController();
        $response = $controller->__invoke($request, KnowbaseItem::class, $kb->getID());

        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }
}
