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
        $kb = new KnowbaseItem();
        $this->assertGreaterThan(
            0,
            (int) $kb->add([
                'name'     => $this->getUniqueString(),
                'answer'   => 'KB answer',
                'is_faq'   => 0,
                'users_id' => getItemByTypeName(User::class, TU_USER, true),
            ])
        );
        return $kb;
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

        $favorite = new KnowbaseItem_Favorite();
        $this->assertGreaterThan(
            0,
            (int) $favorite->add([
                'knowbaseitems_id' => $kb->getID(),
                'users_id'         => getItemByTypeName(User::class, TU_USER, true),
            ])
        );

        $this->assertTrue(KnowbaseItem_Favorite::isFavoriteForCurrentUser($kb->getID()));
    }

    public function testIsFavoriteReturnsFalseAfterRemoving(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $criteria = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ];

        $favorite = new KnowbaseItem_Favorite();
        $this->assertGreaterThan(0, (int) $favorite->add($criteria));

        $favorite->deleteByCriteria($criteria);

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

        $favorite = new KnowbaseItem_Favorite();
        $this->assertGreaterThan(0, (int) $favorite->add($criteria));

        try {
            $favorite->add($criteria);
        } catch (\RuntimeException) {
            // Expected: DB unique constraint prevents duplicate
        }

        $this->assertSame(
            1,
            (int) countElementsInTable(KnowbaseItem_Favorite::getTable(), $criteria)
        );
    }

    public function testPurgingKnowbaseItemDeletesFavorites(): void
    {
        $this->login();
        $kb = $this->createKbItem();

        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $favorite = new KnowbaseItem_Favorite();
        $this->assertGreaterThan(
            0,
            (int) $favorite->add([
                'knowbaseitems_id' => $kb->getID(),
                'users_id'         => $users_id,
            ])
        );

        $this->assertTrue($kb->delete(['id' => $kb->getID()], true));

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

        $user = new User();
        $this->assertGreaterThan(
            0,
            (int) $user->add([
                'name'     => $this->getUniqueString(),
                'password' => 'test',
                'password2' => 'test',
            ])
        );
        $users_id = $user->getID();

        $favorite = new KnowbaseItem_Favorite();
        $this->assertGreaterThan(
            0,
            (int) $favorite->add([
                'knowbaseitems_id' => $kb->getID(),
                'users_id'         => $users_id,
            ])
        );

        $this->assertTrue($user->delete(['id' => $users_id], true));

        $this->assertSame(
            0,
            (int) countElementsInTable(KnowbaseItem_Favorite::getTable(), [
                'users_id' => $users_id,
            ])
        );
    }
}
