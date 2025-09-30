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

namespace tests\units\Glpi\UI;

use Glpi\UI\IllustrationManager;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class IllustrationManagerTest extends GLPITestCase
{
    public static function searchIconsUsingFilterProvider(): iterable
    {
        yield [
            'filter' => 'Service',
            'expected' => ['helpdesk', 'request-service'],
        ];
        yield [
            'filter' => 'backup And restoration',
            'expected' => ['backup-restoration-1', 'backup-restoration-2'],
        ];
    }

    public static function searchIconsUsingFilterProviderWithTags(): iterable
    {
        yield [
            'filter' => 'software',
            'expected' => [
                'application',
                'license',
                'software-deployment',
                'update-1',
                'update-2',
            ],
        ];
    }

    #[DataProvider('searchIconsUsingFilterProvider')]
    #[DataProvider('searchIconsUsingFilterProviderWithTags')]
    public function testSearchIconsIdsUsingFilter(
        string $filter,
        array $expected,
    ): void {
        // Act: get icons matching the requester filter.
        $manager = new IllustrationManager();
        $ids = $manager->searchIcons(filter: $filter);

        // Assert: the expected icons ids are found
        $this->assertEquals($expected, $ids);
    }

    public static function searchIconsIdsUsingPaginationProvider(): iterable
    {
        yield [
            'page' => 1,
            'page_size' => 3,
        ];

        yield [
            'page' => 2,
            'page_size' => 3,
        ];

        yield [
            'page' => 1,
            'page_size' => 10,
        ];
    }

    #[DataProvider('searchIconsIdsUsingPaginationProvider')]
    public function testSearchIconsIdsUsingPagination(
        int $page,
        int $page_size,
    ): void {
        // Act: get icons matching the requester filter.
        $manager = new IllustrationManager();
        $ids = $manager->searchIcons(page: $page, page_size: $page_size);
    }

    public function testIllustrationsTranslationsAreGenerated(): void
    {
        // Assert: a file with translations for each icons should exist in the
        // ressources folder.
        $this->assertFileExists(IllustrationManager::TRANSLATION_FILE);

        $content = file_get_contents(IllustrationManager::TRANSLATION_FILE);
        $to_check = [
            "Approve Requests",
            "Monitoring",
            "Make a reservation",
            "New user 3",
            "planet",
            "cellphone",
        ];
        foreach ($to_check as $string) {
            $this->assertStringContainsString('_x("Icon", "' . $string . '")', $content);
        }
    }
}
