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
use Glpi\Console\Migration\MyIsamToInnoDbCommand;
use Glpi\Tests\DbTestCase;
use ReflectionClass;
use Reminder;
use RSSFeed;
use Symfony\Component\Console\Tester\CommandTester;
use Ticket;

use function Safe\ob_get_clean;
use function Safe\ob_start;

class CentralTest extends DbTestCase
{
    public function testDatabaseSchemaChecksAreCached(): void
    {
        global $GLPI_CACHE;

        $this->login();

        $cache_key = (new ReflectionClass(Central::class))->getConstant('DATABASE_SCHEMA_CHECKS_CACHE_KEY');
        Central::clearDatabaseSchemaChecksCache();

        // The checks are done once, then read from the cache.
        $this->callPrivateMethod(Central::class, 'getMessages');
        $schema_checks = $GLPI_CACHE->get($cache_key);
        $this->assertIsArray($schema_checks);
        $this->assertSame(
            ['myisam_tables', 'datetime_columns', 'non_utf8mb4_tables', 'signed_keys_columns'],
            array_keys($schema_checks)
        );

        $GLPI_CACHE->set($cache_key, ['myisam_tables' => 3] + $schema_checks);
        $messages = $this->callPrivateMethod(Central::class, 'getMessages');
        $this->assertNotEmpty(array_filter(
            $messages['warnings'] ?? [],
            static fn(string $warning): bool => str_contains($warning, '3 tables are using the deprecated MyISAM storage engine.')
        ));

        // Running a schema migration command clears them.
        $tester = new CommandTester(new MyIsamToInnoDbCommand());
        $tester->execute([], ['interactive' => false]);
        $this->assertSame(0, $tester->getStatusCode());
        $this->assertFalse($GLPI_CACHE->has($cache_key));
    }

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
