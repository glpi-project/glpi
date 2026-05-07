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

final class ShareTokenTest extends DbTestCase
{
    private function createKnowbaseItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'users_id'    => getItemByTypeName(User::class, TU_USER, true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => 'KB article for sharing test',
            'answer'      => 'Some content',
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

    public function testCreateToken(): void
    {
        $this->login();

        $kb = $this->createKnowbaseItem();

        $token = new ShareToken();
        $id = $token->add([
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        $this->assertNotFalse($id);

        $this->assertSame(KnowbaseItem::class, $token->fields['itemtype']);
        $this->assertSame($kb->getID(), (int) $token->fields['items_id']);
        $this->assertSame(1, (int) $token->fields['is_active']);
        $this->assertSame(64, strlen($token->fields['token']));
        $this->assertTrue(ctype_xdigit($token->fields['token']));
        $this->assertSame(\Session::getLoginUserID(), (int) $token->fields['users_id']);
    }

    public function testGetTokensForItem(): void
    {
        $kb = $this->createKnowbaseItem();
        $this->createToken($kb);
        $this->createToken($kb);

        $tokens = ShareToken::getTokensForItem(KnowbaseItem::class, $kb->getID());

        $this->assertCount(2, $tokens);
    }

    public function testGetTokensForItemReturnsEmptyForNoTokens(): void
    {
        $kb = $this->createKnowbaseItem();

        $tokens = ShareToken::getTokensForItem(KnowbaseItem::class, $kb->getID());

        $this->assertCount(0, $tokens);
    }

    public function testGetTokensForItemDoesNotReturnOtherItemTokens(): void
    {
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();
        $this->createToken($kb1);
        $this->createToken($kb2);

        $tokens = ShareToken::getTokensForItem(KnowbaseItem::class, $kb1->getID());

        $this->assertCount(1, $tokens);
        $this->assertSame($kb1->getID(), (int) $tokens[0]['items_id']);
    }

    public function testGrantAndHasSessionAccess(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);

        $token_manager = new ShareTokenManager();

        $this->assertFalse($token_manager->hasSessionAccess(KnowbaseItem::class, $kb->getID()));

        $token_manager->grantSessionAccess($token->fields['token']);

        $this->assertTrue($token_manager->hasSessionAccess(KnowbaseItem::class, $kb->getID()));
    }

    public function testSessionAccessIsIsolatedPerItem(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();
        $token = $this->createToken($kb1);

        $token_manager = new ShareTokenManager();

        $token_manager->grantSessionAccess($token->fields['token']);

        $this->assertTrue($token_manager->hasSessionAccess(KnowbaseItem::class, $kb1->getID()));
        $this->assertFalse($token_manager->hasSessionAccess(KnowbaseItem::class, $kb2->getID()));
    }

    public function testGetAccessibleItemsReturnsGrantedItems(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();
        $token1 = $this->createToken($kb1);
        $token2 = $this->createToken($kb2);

        $token_manager = new ShareTokenManager();

        $this->assertSame([], $token_manager->getAccessibleItems());

        $token_manager->grantSessionAccess($token1->fields['token']);
        $token_manager->grantSessionAccess($token2->fields['token']);

        $accessible = $token_manager->getAccessibleItems();
        $this->assertArrayHasKey(KnowbaseItem::class, $accessible);
        $this->assertContains($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertContains($kb2->getID(), $accessible[KnowbaseItem::class]);
    }

    public function testGetAccessibleItemsExcludesRevokedTokens(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();
        $token1 = $this->createToken($kb1);
        $token2 = $this->createToken($kb2);

        $token_manager = new ShareTokenManager();

        $_SESSION['glpi_currenttime'] = '2026-05-05 12:00:00';
        $token_manager->grantSessionAccess($token1->fields['token']);
        $token_manager->grantSessionAccess($token2->fields['token']);

        $accessible = $token_manager->getAccessibleItems();
        $this->assertArrayHasKey(KnowbaseItem::class, $accessible);
        $this->assertContains($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertContains($kb2->getID(), $accessible[KnowbaseItem::class]);

        // Revoke token2
        $this->updateItem(ShareToken::class, $token2->getID(), ['is_active' => 0]);

        // Access state is updated once the 5 minutes authorization window is passed
        $_SESSION['glpi_currenttime'] = '2026-05-05 12:04:59';
        $accessible = $token_manager->getAccessibleItems();
        $this->assertArrayHasKey(KnowbaseItem::class, $accessible);
        $this->assertContains($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertContains($kb2->getID(), $accessible[KnowbaseItem::class]);

        $_SESSION['glpi_currenttime'] = '2026-05-05 12:05:01';
        $accessible = $token_manager->getAccessibleItems();
        $this->assertArrayHasKey(KnowbaseItem::class, $accessible);
        $this->assertContains($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertNotContains($kb2->getID(), $accessible[KnowbaseItem::class]);
    }

    public function testRevokedTokenDeniesSessionAccess(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);

        $token_manager = new ShareTokenManager();

        $_SESSION['glpi_currenttime'] = '2026-05-05 12:00:00';
        $token_manager->grantSessionAccess($token->fields['token']);

        $this->assertTrue($token_manager->hasSessionAccess(KnowbaseItem::class, $kb->getID()));

        // Revoke the token
        $this->updateItem(ShareToken::class, $token->getID(), ['is_active' => 0]);

        // Denies access once the 5 minutes authorization window is passed
        $_SESSION['glpi_currenttime'] = '2026-05-05 12:04:59';
        $this->assertTrue($token_manager->hasSessionAccess(KnowbaseItem::class, $kb->getID()));
        $_SESSION['glpi_currenttime'] = '2026-05-05 12:05:01';
        $this->assertFalse($token_manager->hasSessionAccess(KnowbaseItem::class, $kb->getID()));
    }

    public function testDeleteToken(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = $this->createToken($kb);

        $token_manager = new ShareTokenManager();

        $token_string = $token->fields['token'];
        $token_id = $token->getID();

        $this->assertTrue($token->delete(['id' => $token_id], true));

        $this->assertNull($token_manager->grantSessionAccess($token_string));
        $this->assertCount(0, ShareToken::getTokensForItem(KnowbaseItem::class, $kb->getID()));
    }

    public function testMultipleTokensPerItem(): void
    {
        $kb = $this->createKnowbaseItem();

        $token_manager = new ShareTokenManager();

        $token1 = $this->createToken($kb);
        $token2 = $this->createToken($kb);
        $token3 = $this->createToken($kb);

        $this->assertNotSame($token1->fields['token'], $token2->fields['token']);
        $this->assertNotSame($token2->fields['token'], $token3->fields['token']);

        $this->assertNotNull($token_manager->grantSessionAccess($token1->fields['token']));
        $this->assertNotNull($token_manager->grantSessionAccess($token2->fields['token']));
        $this->assertNotNull($token_manager->grantSessionAccess($token3->fields['token']));
    }

    public function testTokenUniqueness(): void
    {
        $kb = $this->createKnowbaseItem();
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $token = $this->createToken($kb);
            $tokens[] = $token->fields['token'];
        }

        $this->assertCount(10, array_unique($tokens));
    }
}
