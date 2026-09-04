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

namespace tests\units\Glpi\UI;

use Glpi\Tests\GLPITestCase;
use Glpi\UI\IllustrationManager;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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

    public function testInstantiationDoesNotRequireIllustrationFiles(): void
    {
        // Arrange / Act: instantiate the manager with paths that do not exist.
        // The illustration files are provided by an npm package, that may not
        // be installed (e.g. in a lint-only CI job), and instantiating the
        // manager happens on every kernel boot.
        $manager = new IllustrationManager(
            icons_definition_file: '/does/not/exist/icons.json',
            icons_sprites_path: '/lib/does-not-exist/icons.svg',
            scenes_gradient_sprites_path: '/lib/does-not-exist/scenes.svg',
        );

        // Assert: no exception has been thrown.
        $this->assertInstanceOf(IllustrationManager::class, $manager);
    }

    public function testRenderIconFailsWhenSpritesFileIsMissing(): void
    {
        // Arrange: a manager pointing to a missing sprites file.
        // The sprites path is relative to the `public` directory.
        $sprites_path = '/lib/does-not-exist/icons.svg';
        $manager = new IllustrationManager(icons_sprites_path: $sprites_path);

        // Assert: rendering an icon is where the missing file is reported.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed to read file: ' . GLPI_ROOT . '/public/' . $sprites_path
        );

        // Act
        $manager->renderIcon('report-issue');
    }

    public function testRenderIconReturnsEmptyStringForEmptyValue(): void
    {
        $manager = new IllustrationManager();

        $this->assertSame('', $manager->renderIcon(''));
        $this->assertSame('', $manager->renderIcon('', 60));
    }

    public function testRenderIconIsHiddenButKeepsTitleForTooltip(): void
    {
        $manager = new IllustrationManager();

        $html = $manager->renderIcon('report-issue');

        // aria-hidden hides it regardless of the title.
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('<title>Report an issue</title>', $html);
    }

    public function testRenderSceneIsHiddenAndUnnamed(): void
    {
        $manager = new IllustrationManager();

        $html = $manager->renderScene('desk');

        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringNotContainsString('<title>', $html);
    }

    public function testGetIconTitle(): void
    {
        $manager = new IllustrationManager();

        $this->assertSame('Report an issue', $manager->getIconTitle('report-issue'));
        $this->assertSame('', $manager->getIconTitle('report-issue.svg'));
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
