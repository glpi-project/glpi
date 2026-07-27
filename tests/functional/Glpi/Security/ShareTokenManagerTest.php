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

namespace tests\units\Glpi\Security;

use Glpi\Security\ShareTokenManager;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use User;

final class ShareTokenManagerTest extends DbTestCase
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

    public function testHasActiveTokenReflectsState(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $manager = new ShareTokenManager();

        $this->assertFalse($manager->hasActiveToken(KnowbaseItem::class, $kb->getID()));

        $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        $this->assertTrue($manager->hasActiveToken(KnowbaseItem::class, $kb->getID()));
    }

    public function testGetActiveTokenReturnsDecryptedRowOrNull(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $manager = new ShareTokenManager();

        $this->assertNull($manager->getActiveToken(KnowbaseItem::class, $kb->getID()));

        $token = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        $active = $manager->getActiveToken(KnowbaseItem::class, $kb->getID());
        $this->assertNotNull($active);
        $this->assertSame(
            $manager->decryptToken((string) $token->fields['token']),
            $active['token'],
        );
    }

    public function testGetTokenReturnsRowRegardlessOfActiveState(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $manager = new ShareTokenManager();

        $this->assertNull($manager->getToken(KnowbaseItem::class, $kb->getID()));

        $token = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);

        $active = $manager->getToken(KnowbaseItem::class, $kb->getID());
        $this->assertNotNull($active);
        $this->assertSame($token->getID(), (int) $active['id']);
        $this->assertSame(
            $manager->decryptToken((string) $token->fields['token']),
            $active['token'],
        );

        $this->updateItem(ShareToken::class, $token->getID(), ['is_active' => 0]);

        $inactive = $manager->getToken(KnowbaseItem::class, $kb->getID());
        $this->assertNotNull($inactive);
        $this->assertSame($token->getID(), (int) $inactive['id']);
        $this->assertSame(0, (int) $inactive['is_active']);
        $this->assertSame(
            $manager->decryptToken((string) $token->fields['token']),
            $inactive['token'],
        );
    }
}
