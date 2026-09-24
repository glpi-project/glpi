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

use DbTestCase;

final class CommonDBRelationTest extends DbTestCase
{
    public function testCannotCreateRelationWithoutUpdateRightOnReminder(): void
    {
        // Arrange: a reminder of another user, visible in all the entities.
        $this->login('glpi', 'glpi');
        $reminder = new \Reminder();
        $reminder_id = $reminder->add([
            'name'     => 'Public reminder',
            'text'     => 'Public reminder',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertGreaterThan(0, (new \Entity_Reminder())->add([
            'reminders_id' => $reminder_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        // Arrange: a user that can read this reminder, but that cannot update it.
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | \Reminder::PERSONAL;
        $this->assertTrue($reminder->getFromDB($reminder_id));
        $this->assertTrue($reminder->canViewItem());
        $this->assertFalse($reminder->canUpdateItem());

        $input = [
            'reminders_id' => $reminder_id,
            'users_id'     => getItemByTypeName(\User::class, 'tech', true),
        ];

        // Act: compute creation rights
        $can_create = (new \Reminder_User())->can(-1, CREATE, $input);

        // Assert: should be refused
        $this->assertFalse($can_create);
    }

    public function testCanCreateRelationWithUpdateRightOnReminder(): void
    {
        // Arrange: a reminder of another user, visible in all the entities.
        $this->login('glpi', 'glpi');
        $reminder = new \Reminder();
        $reminder_id = $reminder->add([
            'name'     => 'Public reminder',
            'text'     => 'Public reminder',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertGreaterThan(0, (new \Entity_Reminder())->add([
            'reminders_id' => $reminder_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]));

        // Arrange: a user that can read and update this reminder
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile']['reminder_public'] = READ | UPDATE | \Reminder::PERSONAL;
        $this->assertTrue($reminder->getFromDB($reminder_id));
        $this->assertTrue($reminder->canViewItem());
        $this->assertTrue($reminder->canUpdateItem());

        $input = [
            'reminders_id' => $reminder_id,
            'users_id'     => getItemByTypeName(\User::class, 'tech', true),
        ];

        // Act: compute creation rights
        $can_create = (new \Reminder_User())->can(-1, CREATE, $input);

        // Assert: should be allowed
        $this->assertTrue($can_create);
    }

    public function testCanCreateRelationWithoutItemOnUncheckedSide(): void
    {
        // Arrange: a rack, and a new rack item whose item is not selected yet (e.g. on rack item creation form)
        $this->login('glpi', 'glpi');
        $rack_id = $this->createItem(
            \Rack::class,
            [
                'name'        => 'Test rack',
                'entities_id' => $this->getTestRootEntity(true),
            ]
        )->getID();

        $input = [
            'racks_id'    => $rack_id,
            'orientation' => \Rack::FRONT,
            'position'    => 1,
        ];

        // Act: compute creation rights
        $can_create = (new \Item_Rack())->can(-1, CREATE, $input);

        // Assert: should be allowed
        $this->assertTrue($can_create);
    }
}
