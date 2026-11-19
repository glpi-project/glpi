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
use Computer;
use CronTask;
use Glpi\System\HousekeepingCron;
use Glpi\Tests\DbTestCase;
use Item_Process;
use Log;
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

    public function testHousekeepingWarning(): void
    {
        $this->login();

        $warning = 'Some obsolete data should be removed, but the related automatic action is disabled.';
        $has_warning = function () use ($warning): bool {
            $warnings = $this->callPrivateMethod(new Central(), 'getMessages')['warnings'] ?? [];
            foreach ($warnings as $message) {
                if (str_contains($message, $warning)) {
                    return true;
                }
            }
            return false;
        };

        $task = new CronTask();
        $this->assertTrue($task->getFromDBbyName(HousekeepingCron::class, HousekeepingCron::TASK_NAME));

        // nothing to clean
        $this->assertFalse($has_warning());

        $computer = getItemByTypeName(Computer::class, '_test_pc01');
        $this->createItem(Log::class, [
            'itemtype'      => Computer::class,
            'items_id'      => $computer->getID(),
            'itemtype_link' => Item_Process::class,
            'linked_action' => Log::HISTORY_ADD_SUBITEM,
            'user_name'     => 'test',
            'date_mod'      => $_SESSION['glpi_currenttime'],
        ]);

        // automatic action is disabled by default
        $this->assertTrue($has_warning());
        $warnings = $this->callPrivateMethod(new Central(), 'getMessages')['warnings'];
        $this->assertStringContainsString('See automatic action', implode('', $warnings));

        // no link to the automatic action without the right to view it
        $_SESSION['glpiactiveprofile']['config'] = UPDATE;
        $this->assertTrue($has_warning());
        $warnings = $this->callPrivateMethod(new Central(), 'getMessages')['warnings'];
        $this->assertStringNotContainsString('See automatic action', implode('', $warnings));
        $_SESSION['glpiactiveprofile']['config'] = READ | UPDATE;

        // automatic action is enabled, it will handle the cleanup
        $this->updateItem(CronTask::class, $task->getID(), ['state' => CronTask::STATE_WAITING]);
        $this->assertFalse($has_warning());
    }
}
