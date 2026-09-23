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

use Central;
use Glpi\Tests\DbTestCase;
use Reminder;
use RSSFeed;
use Ticket;

use function Safe\ob_get_clean;
use function Safe\ob_start;

class CentralTest extends DbTestCase
{
    public function testUnavailableCentralTabsAreHidden(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READMY
            | Ticket::READALL
            | Ticket::READNEWTICKET
            | CREATE
            | UPDATE;
        $_SESSION['glpiactiveprofile'][Reminder::$rightname] = 0;
        $_SESSION['glpiactiveprofile'][RSSFeed::$rightname] = 0;
        $_SESSION['glpiactiveprofile']['problem'] = 0;
        $_SESSION['glpiactiveprofile']['change'] = 0;
        $_SESSION['glpigroups'] = [];

        $central = new Central();
        $raw_tabs = $central->getTabNameForItem($central);
        $this->assertIsArray($raw_tabs);
        $tabs = array_map(
            static fn(string $tab): string => strip_tags($tab),
            $raw_tabs,
        );

        $this->assertContains('Personal View', $tabs);
        $this->assertContains('Global View', $tabs);
        $this->assertNotContains('Group View', $tabs);
        $this->assertNotContains('RSS feed', $tabs);
        $this->assertArrayHasKey(1, $tabs);
        $this->assertArrayNotHasKey(2, $tabs);
        $this->assertArrayHasKey(3, $tabs);
        $this->assertArrayNotHasKey(4, $tabs);
    }

    public function testPersonalViewDoesNotRenderReminderWithoutRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Reminder::$rightname] = 0;

        ob_start();
        Central::showMyView();
        $output = ob_get_clean();

        $this->assertFalse(
            str_contains($output, 'data-itemtype="Reminder"'),
            'Reminder widget must not be rendered without permission.',
        );
    }

    public function testGroupViewDoesNotRenderWithoutRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = 0;
        $_SESSION['glpiactiveprofile']['problem'] = 0;
        $_SESSION['glpiactiveprofile']['change'] = 0;
        $_SESSION['glpiactiveprofile']['project'] = 0;
        $_SESSION['glpiactiveprofile']['projecttask'] = 0;
        $_SESSION['glpigroups'] = [1];

        ob_start();
        Central::showGroupView();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testAvailableCentralTabsAreShown(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READALL;
        $_SESSION['glpiactiveprofile'][RSSFeed::$rightname] = RSSFeed::PERSONAL;
        $_SESSION['glpigroups'] = [1];

        $central = new Central();
        $tabs = $central->getTabNameForItem($central);
        $this->assertIsArray($tabs);

        $this->assertArrayHasKey(2, $tabs);
        $this->assertSame('Group View', strip_tags($tabs[2]));
        $this->assertArrayHasKey(4, $tabs);
        $this->assertSame('RSS feed', strip_tags($tabs[4]));
    }

    public function testGroupViewRendersWithRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = Ticket::READALL;
        $_SESSION['glpigroups'] = [1];

        ob_start();
        Central::showGroupView();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, 'data-itemtype="Ticket"'),
            'Group View must be rendered with permission.',
        );
    }

    public function testPersonalViewRendersReminderWithRight(): void
    {
        $this->login();

        $_SESSION['glpiactiveprofile'][Reminder::$rightname] = Reminder::PERSONAL;

        ob_start();
        Central::showMyView();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, 'data-itemtype="Reminder"'),
            'Reminder widget must be rendered with permission.',
        );
    }
}
