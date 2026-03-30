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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Favorite;
use User;

final class KnowbaseItem_FavoriteTest extends DbTestCase
{
    private function createKbItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'     => $this->getUniqueString(),
            'answer'   => 'KB answer',
            'is_faq'   => 0,
            'users_id' => getItemByTypeName(User::class, TU_USER, true),
        ]);
    }

    public function testIsFavoriteReturnsFalseByDefault(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $this->assertFalse(KnowbaseItem_Favorite::isFavoriteForCurrentUser($kb->getID()));
    }

    public function testIsFavoriteReturnsTrueAfterAdding(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $this->createItem(KnowbaseItem_Favorite::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => getItemByTypeName(User::class, TU_USER, true),
        ]);

        $this->assertTrue(KnowbaseItem_Favorite::isFavoriteForCurrentUser($kb->getID()));
    }

    public function testIsFavoriteReturnsFalseAfterRemoving(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $favorite = $this->createItem(KnowbaseItem_Favorite::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $this->deleteItem(KnowbaseItem_Favorite::class, $favorite->getID());

        $this->assertFalse(KnowbaseItem_Favorite::isFavoriteForCurrentUser($kb->getID()));
    }

    public function testAddingFavoriteTwiceDoesNotCreateDuplicate(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $criteria = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ];

        $this->createItem(KnowbaseItem_Favorite::class, $criteria);

        $this->expectException(\RuntimeException::class);
        $duplicate = new KnowbaseItem_Favorite();
        $duplicate->add($criteria);
    }

    public function testPurgingKnowbaseItemDeletesFavorites(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->createItem(KnowbaseItem_Favorite::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $this->deleteItem(KnowbaseItem::class, $kb->getID(), true);

        $this->assertSame(
            0,
            (int) countElementsInTable(KnowbaseItem_Favorite::getTable(), [
                'knowbaseitems_id' => $kb->getID(),
            ])
        );
    }

    public function testPurgingUserDeletesFavorites(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $user = $this->createItem(User::class, [
            'name'      => $this->getUniqueString(),
            'password'  => 'test',
            'password2' => 'test',
        ], ['password', 'password2']);
        $users_id = $user->getID();

        $this->createItem(KnowbaseItem_Favorite::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $this->deleteItem(User::class, $users_id, true);

        $this->assertSame(
            0,
            (int) countElementsInTable(KnowbaseItem_Favorite::getTable(), [
                'users_id' => $users_id,
            ])
        );
    }
}
