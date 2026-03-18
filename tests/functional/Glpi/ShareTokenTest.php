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

use Glpi\Security\ShareTokenManager;
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

    public function testCreateToken(): void
    {
        $kb = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $this->assertInstanceOf(ShareToken::class, $token);
        $this->assertSame(KnowbaseItem::class, $token->fields['itemtype']);
        $this->assertSame($kb->getID(), (int) $token->fields['items_id']);
        $this->assertSame(1, (int) $token->fields['is_active']);
        $this->assertSame(64, strlen($token->fields['token']));
        $this->assertTrue(ctype_xdigit($token->fields['token']));
    }

    public function testCreateTokenWithName(): void
    {
        $kb = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'My share link');

        $this->assertInstanceOf(ShareToken::class, $token);
        $this->assertSame('My share link', $token->fields['name']);
    }

    public function testCreateTokenSetsCurrentUser(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $this->assertSame(\Session::getLoginUserID(), (int) $token->fields['users_id']);
    }

    public function testValidateTokenReturnsItemInfo(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $result = ShareTokenManager::validateToken($token->fields['token']);

        $this->assertNotNull($result);
        $this->assertSame(KnowbaseItem::class, $result['itemtype']);
        $this->assertSame($kb->getID(), $result['items_id']);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $result = ShareTokenManager::validateToken('nonexistent_token_string');

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForInactiveToken(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        ShareToken::toggleActive($token->getID());

        $result = ShareTokenManager::validateToken($token->fields['token']);

        $this->assertNull($result);
    }

    public function testValidateTokenReturnsNullForExpiredToken(): void
    {
        global $DB;

        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $DB->update(ShareToken::getTable(), [
            'date_expiration' => '2020-01-01 00:00:00',
        ], [
            'id' => $token->getID(),
        ]);

        $result = ShareTokenManager::validateToken($token->fields['token']);

        $this->assertNull($result);
    }

    public function testValidateTokenAcceptsNullExpiration(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());

        $result = ShareTokenManager::validateToken($token->fields['token']);

        $this->assertNotNull($result);
    }

    public function testToggleActive(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $this->assertSame(1, (int) $token->fields['is_active']);

        $this->assertTrue(ShareToken::toggleActive($token->getID()));
        $token->getFromDB($token->getID());
        $this->assertSame(0, (int) $token->fields['is_active']);

        $this->assertTrue(ShareToken::toggleActive($token->getID()));
        $token->getFromDB($token->getID());
        $this->assertSame(1, (int) $token->fields['is_active']);
    }

    public function testToggleActiveReturnsFalseForInvalidId(): void
    {
        $this->assertFalse(ShareToken::toggleActive(999999));
    }

    public function testRegenerateToken(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $original_token_string = $token->fields['token'];

        $updated = ShareToken::regenerateToken($token->getID());

        $this->assertInstanceOf(ShareToken::class, $updated);
        $this->assertNotSame($original_token_string, $updated->fields['token']);
        $this->assertSame(64, strlen($updated->fields['token']));
        $this->assertTrue(ctype_xdigit($updated->fields['token']));
        $this->assertSame($token->getID(), $updated->getID());
    }

    public function testRegenerateTokenInvalidatesOldToken(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $old_token_string = $token->fields['token'];

        ShareToken::regenerateToken($token->getID());

        $this->assertNull(ShareTokenManager::validateToken($old_token_string));
    }

    public function testRegenerateTokenReturnsFalseForInvalidId(): void
    {
        $this->assertFalse(ShareToken::regenerateToken(999999));
    }

    public function testGetTokensForItem(): void
    {
        $kb = $this->createKnowbaseItem();
        ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'Token A');
        ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'Token B');

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
        ShareToken::createToken(KnowbaseItem::class, $kb1->getID());
        ShareToken::createToken(KnowbaseItem::class, $kb2->getID());

        $tokens = ShareToken::getTokensForItem(KnowbaseItem::class, $kb1->getID());

        $this->assertCount(1, $tokens);
        $this->assertSame($kb1->getID(), (int) $tokens[0]['items_id']);
    }

    public function testGrantAndHasSessionAccess(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $token_string = $token->fields['token'];

        unset($_SESSION[ShareTokenManager::SESSION_KEY]);
        ShareTokenManager::resetValidationCache();

        $this->assertFalse(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb->getID()));

        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb->getID(), $token_string);

        $this->assertTrue(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb->getID()));
    }

    public function testSessionAccessIsIsolatedPerItem(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb1->getID());

        unset($_SESSION[ShareTokenManager::SESSION_KEY]);
        ShareTokenManager::resetValidationCache();

        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb1->getID(), $token->fields['token']);

        $this->assertTrue(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb1->getID()));
        $this->assertFalse(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb2->getID()));
    }

    public function testGetAccessibleItemsReturnsGrantedItems(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();

        $token1 = ShareToken::createToken(KnowbaseItem::class, $kb1->getID());
        $token2 = ShareToken::createToken(KnowbaseItem::class, $kb2->getID());

        unset($_SESSION[ShareTokenManager::SESSION_KEY]);
        ShareTokenManager::resetValidationCache();

        $this->assertSame([], ShareTokenManager::getAccessibleItems());

        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb1->getID(), $token1->fields['token']);
        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb2->getID(), $token2->fields['token']);

        $accessible = ShareTokenManager::getAccessibleItems();
        $this->assertArrayHasKey(KnowbaseItem::class, $accessible);
        $this->assertArrayHasKey($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertArrayHasKey($kb2->getID(), $accessible[KnowbaseItem::class]);
    }

    public function testGetAccessibleItemsExcludesRevokedTokens(): void
    {
        $this->login();
        $kb1 = $this->createKnowbaseItem();
        $kb2 = $this->createKnowbaseItem();

        $token1 = ShareToken::createToken(KnowbaseItem::class, $kb1->getID());
        $token2 = ShareToken::createToken(KnowbaseItem::class, $kb2->getID());

        unset($_SESSION[ShareTokenManager::SESSION_KEY]);
        ShareTokenManager::resetValidationCache();

        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb1->getID(), $token1->fields['token']);
        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb2->getID(), $token2->fields['token']);

        // Revoke token2
        ShareToken::toggleActive($token2->getID());
        ShareTokenManager::resetValidationCache();

        $accessible = ShareTokenManager::getAccessibleItems();
        $this->assertArrayHasKey($kb1->getID(), $accessible[KnowbaseItem::class]);
        $this->assertArrayNotHasKey($kb2->getID(), $accessible[KnowbaseItem::class] ?? []);
    }

    public function testRevokedTokenDeniesSessionAccess(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $token_string = $token->fields['token'];

        unset($_SESSION[ShareTokenManager::SESSION_KEY]);
        ShareTokenManager::grantSessionAccess(KnowbaseItem::class, $kb->getID(), $token_string);

        $this->assertTrue(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb->getID()));

        // Revoke the token
        ShareToken::toggleActive($token->getID());

        // Must deny access immediately — this is the bug we're fixing
        ShareTokenManager::resetValidationCache();
        $this->assertFalse(ShareTokenManager::hasSessionAccess(KnowbaseItem::class, $kb->getID()));
    }

    public function testDeleteToken(): void
    {
        $kb = $this->createKnowbaseItem();
        $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
        $token_string = $token->fields['token'];
        $token_id = $token->getID();

        $this->assertTrue($token->delete(['id' => $token_id], true));

        $this->assertNull(ShareTokenManager::validateToken($token_string));
        $this->assertCount(0, ShareToken::getTokensForItem(KnowbaseItem::class, $kb->getID()));
    }

    public function testMultipleTokensPerItem(): void
    {
        $kb = $this->createKnowbaseItem();

        $token1 = ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'Link 1');
        $token2 = ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'Link 2');
        $token3 = ShareToken::createToken(KnowbaseItem::class, $kb->getID(), 'Link 3');

        $this->assertNotSame($token1->fields['token'], $token2->fields['token']);
        $this->assertNotSame($token2->fields['token'], $token3->fields['token']);

        $this->assertNotNull(ShareTokenManager::validateToken($token1->fields['token']));
        $this->assertNotNull(ShareTokenManager::validateToken($token2->fields['token']));
        $this->assertNotNull(ShareTokenManager::validateToken($token3->fields['token']));
    }

    public function testTokenUniqueness(): void
    {
        $kb = $this->createKnowbaseItem();
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $token = ShareToken::createToken(KnowbaseItem::class, $kb->getID());
            $tokens[] = $token->fields['token'];
        }

        $this->assertCount(10, array_unique($tokens));
    }
}
