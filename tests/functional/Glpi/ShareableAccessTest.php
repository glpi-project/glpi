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

namespace tests\units\Glpi;

use CommonDBTM;
use Glpi\Security\ShareTokenManager;
use Glpi\ShareableInterface;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use User;

final class ShareableAccessTest extends DbTestCase
{
    private function createKnowbaseItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'users_id'    => getItemByTypeName(User::class, TU_USER, true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => 'Shared KB article',
            'answer'      => 'Content of shared article',
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

    public function testSharedAccessGrantsReadOnKnowbaseItem(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);
        $this->logOut();

        (new ShareTokenManager())->grantSessionAccess($token->fields['token']);

        $item = new KnowbaseItem();
        $this->assertTrue($item->can($kb->getID(), READ));
    }

    public function testSharedAccessDoesNotGrantUpdate(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);
        $this->logOut();

        (new ShareTokenManager())->grantSessionAccess($token->fields['token']);

        $item = new KnowbaseItem();
        $this->assertFalse($item->can($kb->getID(), UPDATE));
    }

    public function testSharedAccessDoesNotGrantDelete(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);
        $this->logOut();

        (new ShareTokenManager())->grantSessionAccess($token->fields['token']);

        $item = new KnowbaseItem();
        $this->assertFalse($item->can($kb->getID(), DELETE));
    }

    public function testNoSharedAccessWithoutSessionGrant(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $this->logOut();


        $item = new KnowbaseItem();
        $this->assertFalse($item->can($kb->getID(), READ));
    }

    public function testSharedAccessDoesNotLeakToOtherItems(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();
        $token = $this->createToken($kb1);
        $this->logOut();

        (new ShareTokenManager())->grantSessionAccess($token->fields['token']);

        $item = new KnowbaseItem();
        $this->assertTrue($item->can($kb1->getID(), READ));
        $this->assertFalse($item->can($kb2->getID(), READ));
    }

    public function testKnowbaseItemShareableInterface(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $this->assertInstanceOf(ShareableInterface::class, $kb);
        $this->assertNotEmpty($kb->getItemUrl());
        $this->assertSame('pages/tools/kb/shared_article.html.twig', $kb->getShareableViewTemplate());

        $params = $kb->getShareableViewParams();
        $this->assertArrayHasKey('title', $params);
        $this->assertArrayHasKey('content', $params);
        $this->assertArrayHasKey('item', $params);
        $this->assertSame('Shared KB article', $params['title']);
    }

    public function testCanManageSharingRequiresUpdateRight(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $this->assertTrue($kb->canManageSharing());
    }

    public function testFullTokenWorkflow(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);
        $this->logOut();

        $validated = (new ShareTokenManager())->grantSessionAccess($token->fields['token']);
        $this->assertNotNull($validated);

        $item = new KnowbaseItem();
        $this->assertTrue($item->can($kb->getID(), READ));
        $this->assertFalse($item->can($kb->getID(), UPDATE));
    }
}
