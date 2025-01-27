<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace test\units\Glpi\Helpdesk;

use DbTestCase;
use Glpi\Helpdesk\HomePageTabs;
use Profile;
use Reminder;
use Reminder_User;
use RSSFeed;
use RSSFeed_User;
use User;

final class HomePageTabsTest extends DbTestCase
{
    public function testDefaultTabs(): void
    {
        // Arrange: create a new user without reminders / rssfeeds
        $this->createItem(User::class, [
            'name'          => 'tmp_user',
            'password'      => 'tmp_user',
            'password2'     => 'tmp_user',
            '_profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            '_entities_id'  => $this->getTestRootEntity(true),
            '_is_recursive' => true,
        ], ['password', 'password2']);

        // Act: get tabs names
        $this->login('tmp_user', 'tmp_user');
        $tabs = $this->getHomeTabsNames();

        // Assert: the reminder and rss tabs should not be displayed as there is no data.
        $this->assertEquals([
            'Ongoing tickets',
            'Solved tickets',
        ], $tabs);
    }

    public function testPublicReminderTabIsAddedWhenReminderExists(): void
    {
        // Arrange: create a new user and one public reminder
        $user = $this->createItem(User::class, [
            'name'          => 'tmp_user',
            'password'      => 'tmp_user',
            'password2'     => 'tmp_user',
            '_profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            '_entities_id'  => $this->getTestRootEntity(true),
            '_is_recursive' => true,
        ], ['password', 'password2']);
        $reminder = $this->createItem(Reminder::class, [
            'name' => 'My reminder',
        ]);
        $this->createItem(Reminder_User::class, [
            'users_id' => $user->getID(),
            'reminders_id' => $reminder->getID(),
        ]);

        // Act: get tabs names
        $this->login('tmp_user', 'tmp_user');
        $tabs = $this->getHomeTabsNames();

        // Assert: the reminder and rss tabs should not be displayed as there is no data.
        $this->assertEquals([
            'Ongoing tickets',
            'Solved tickets',
            'Reminders',
        ], $tabs);
    }

    public function testRssFeedsTabIsAddedWhenRssFeedsExists(): void
    {
        // Arrange: create a new user and one public reminder
        $user = $this->createItem(User::class, [
            'name'          => 'tmp_user',
            'password'      => 'tmp_user',
            'password2'     => 'tmp_user',
            '_profiles_id'  => getItemByTypeName(Profile::class, 'Self-Service', true),
            '_entities_id'  => $this->getTestRootEntity(true),
            '_is_recursive' => true,
        ], ['password', 'password2']);
        $this->login('tmp_user', 'tmp_user'); // Need to be logged in to create the RSS feed
        $reminder = $this->createItem(RSSFeed::class, [
            'name' => 'My feed',
            'url'  => 'https://fake-rss.localhost.com/feed',
            '_do_not_fetch_values' => true,
        ]);
        $this->createItem(RSSFeed_User::class, [
            'users_id' => $user->getID(),
            'rssfeeds_id' => $reminder->getID(),
        ]);

        // Act: get tabs names
        $tabs = $this->getHomeTabsNames();

        // Assert: the reminder and rss tabs should not be displayed as there is no data.
        $this->assertEquals([
            'Ongoing tickets',
            'Solved tickets',
            'RSS feed',
        ], $tabs);
    }

    private function getHomeTabsNames(): array
    {
        $tabs = new HomePageTabs();
        $tabs = $tabs->getTabNameForItem($tabs);
        $tabs = array_map('strip_tags', $tabs);
        return array_values($tabs);
    }
}
