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

namespace tests\unit\Glpi\Knowbase\History;

use Glpi\Knowbase\History\LogEvent;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LogEventTest extends GLPITestCase
{
    public static function authorProvider(): iterable
    {
        yield 'a one digit id' => ['glpi (2)', 2];
        yield 'a multiple digit id' => ['user (123)', 123];
        yield 'a name that holds digits' => ['Agent 007 (42)', 42];
        yield 'a name that holds parentheses' => ['Dupont (Ltd 3) Jean (77)', 77];
        yield 'an unknown user' => ['Unknown user', 0];
        yield 'a cron task' => ['mycrontask', 0];
        yield 'no user at all' => ['', 0];

        // Limitation: for languages that put the impersonator at the end,
        // we get the wrong id because we target the last number found.
        // This is a safety because taking the first number would fail if the
        // user name contains a number, e.g an user named "John (2) smith".
        // We would target 2 instead of his real ID.
        // By taking the last number, we are sure it is not part of any name.
        yield 'an impersonated action' => ['Smith John (5) impersonated by Doe Jane (2)', 2];

        // Used by locales/zh_CN.po
        yield 'full-width parentheses' => ['test （5）', 5];

        // Used by locales/id_ID.po
        yield 'with space at the start' => ['test ( 5)', 5];
        yield 'with space at the end' => ['test (5 )', 5];

        // Used by locales/mn_MN.po
        yield 'with no spaces before' => ['test(5)', 5];
    }

    #[DataProvider('authorProvider')]
    public function testGetAuthorReadsTheIdFromTheLogName(string $author, int $expected): void
    {
        $event = new LogEvent(
            label: 'Permissions updated',
            description: 'Access granted to Root entity by',
            date: '2026-01-15 11:00:00',
            author: $author,
        );

        $this->assertSame($expected, $event->getAuthor());
    }
}
