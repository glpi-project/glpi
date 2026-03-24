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

namespace test\units;

use Entity_KnowbaseItem;
use Glpi\Tests\DbTestCase;
use Group;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Profile;
use User;

class KnowbaseItemTargetUnicityTest extends DbTestCase
{
    private function createKbArticle(string $name): KnowbaseItem
    {
        /** @var KnowbaseItem $kb */
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'   => $name,
            'answer' => $name,
        ]);
        return $kb;
    }

    public function testKnowbaseItemUserFirstAddSucceeds(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        $link = new KnowbaseItem_User();
        $result = $link->add([
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testKnowbaseItemUserDuplicateAddFails(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $duplicate = new KnowbaseItem_User();
        $result = $duplicate->add([
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ]);

        $this->assertFalse($result);
    }

    public function testKnowbaseItemUserOnDifferentKbSucceeds(): void
    {
        $kb1 = $this->createKbArticle(__FUNCTION__ . '_1');
        $kb2 = $this->createKbArticle(__FUNCTION__ . '_2');
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb1->getID(),
            'users_id'         => $users_id,
        ]);

        $link2 = new KnowbaseItem_User();
        $result = $link2->add([
            'knowbaseitems_id' => $kb2->getID(),
            'users_id'         => $users_id,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testGroupKnowbaseItemFirstAddSucceeds(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $groups_id = $this->createItem(Group::class, ['name' => __FUNCTION__])->getID();

        $link = new Group_KnowbaseItem();
        $result = $link->add([
            'knowbaseitems_id' => $kb->getID(),
            'groups_id'        => $groups_id,
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testGroupKnowbaseItemDuplicateAddFails(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $groups_id = $this->createItem(Group::class, ['name' => __FUNCTION__])->getID();

        $this->createItem(Group_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb->getID(),
            'groups_id'        => $groups_id,
            'entities_id'      => 0,
        ]);

        $duplicate = new Group_KnowbaseItem();
        $result = $duplicate->add([
            'knowbaseitems_id' => $kb->getID(),
            'groups_id'        => $groups_id,
            'entities_id'      => 0,
        ]);

        $this->assertFalse($result);
    }

    public function testGroupKnowbaseItemOnDifferentKbSucceeds(): void
    {
        $kb1 = $this->createKbArticle(__FUNCTION__ . '_1');
        $kb2 = $this->createKbArticle(__FUNCTION__ . '_2');
        $groups_id = $this->createItem(Group::class, ['name' => __FUNCTION__])->getID();

        $this->createItem(Group_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb1->getID(),
            'groups_id'        => $groups_id,
            'entities_id'      => 0,
        ]);

        $link2 = new Group_KnowbaseItem();
        $result = $link2->add([
            'knowbaseitems_id' => $kb2->getID(),
            'groups_id'        => $groups_id,
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testKnowbaseItemProfileFirstAddSucceeds(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $profiles_id = getItemByTypeName(Profile::class, 'Admin', true);

        $link = new KnowbaseItem_Profile();
        $result = $link->add([
            'knowbaseitems_id' => $kb->getID(),
            'profiles_id'      => $profiles_id,
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testKnowbaseItemProfileDuplicateAddFails(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);
        $profiles_id = getItemByTypeName(Profile::class, 'Admin', true);

        $this->createItem(KnowbaseItem_Profile::class, [
            'knowbaseitems_id' => $kb->getID(),
            'profiles_id'      => $profiles_id,
            'entities_id'      => 0,
        ]);

        $duplicate = new KnowbaseItem_Profile();
        $result = $duplicate->add([
            'knowbaseitems_id' => $kb->getID(),
            'profiles_id'      => $profiles_id,
            'entities_id'      => 0,
        ]);

        $this->assertFalse($result);
    }

    public function testKnowbaseItemProfileOnDifferentKbSucceeds(): void
    {
        $kb1 = $this->createKbArticle(__FUNCTION__ . '_1');
        $kb2 = $this->createKbArticle(__FUNCTION__ . '_2');
        $profiles_id = getItemByTypeName(Profile::class, 'Admin', true);

        $this->createItem(KnowbaseItem_Profile::class, [
            'knowbaseitems_id' => $kb1->getID(),
            'profiles_id'      => $profiles_id,
            'entities_id'      => 0,
        ]);

        $link2 = new KnowbaseItem_Profile();
        $result = $link2->add([
            'knowbaseitems_id' => $kb2->getID(),
            'profiles_id'      => $profiles_id,
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testEntityKnowbaseItemFirstAddSucceeds(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);

        $link = new Entity_KnowbaseItem();
        $result = $link->add([
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }

    public function testEntityKnowbaseItemDuplicateAddFails(): void
    {
        $kb = $this->createKbArticle(__FUNCTION__);

        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => 0,
        ]);

        $duplicate = new Entity_KnowbaseItem();
        $result = $duplicate->add([
            'knowbaseitems_id' => $kb->getID(),
            'entities_id'      => 0,
        ]);

        $this->assertFalse($result);
    }

    public function testEntityKnowbaseItemOnDifferentKbSucceeds(): void
    {
        $kb1 = $this->createKbArticle(__FUNCTION__ . '_1');
        $kb2 = $this->createKbArticle(__FUNCTION__ . '_2');

        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $kb1->getID(),
            'entities_id'      => 0,
        ]);

        $link2 = new Entity_KnowbaseItem();
        $result = $link2->add([
            'knowbaseitems_id' => $kb2->getID(),
            'entities_id'      => 0,
        ]);

        $this->assertGreaterThan(0, (int) $result);
    }
}
