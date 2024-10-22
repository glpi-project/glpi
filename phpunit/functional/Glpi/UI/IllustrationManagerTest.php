<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace test\units\Glpi\UI;

use Glpi\UI\IllustrationManager;
use GLPITestCase;
use org\bovigo\vfs\vfsStream;

final class IllustrationManagerTest extends GLPITestCase
{
    private function getDefaultManager(): IllustrationManager
    {
        // Manager with its default configuration
        return new IllustrationManager();
    }

    public function testIllustrationsAreLoaded(): void
    {
        // Arrange: list all icons
        $manager = $this->getDefaultManager();
        $icons = $manager->getAllIllustrationsNames();

        // Act: render all icons
        $rendered_icons = array_map(
            fn($filename) => $manager->render($filename),
            $icons
        );

        // Assert: load sould succeed
        $this->assertNotEmpty($rendered_icons);
        foreach ($rendered_icons as $rendered_icon) {
            $this->assertNotEmpty($rendered_icon);
        }
    }

    public function testIllustrationsUseThemeColors(): void
    {
        // Arrange: list all icons
        $manager = $this->getDefaultManager();
        $icons = $manager->getAllIllustrationsNames();

        // Act: render all icons
        $rendered_icons = array_map(
            fn($filename) => $manager->render($filename),
            $icons
        );

        // Assert: renderered content should not contain harcoded references to
        // colors
        $this->assertNotEmpty($rendered_icons);
        foreach ($rendered_icons as $i => $rendered_icon) {
            $failed_message = "Failed for $icons[$i]";
            $this->assertStringNotContainsString(
                "rgb(",
                $rendered_icon,
                $failed_message
            );
            $this->assertStringContainsString(
                "var(--tblr-primary)",
                $rendered_icon,
                $failed_message
            );
            $this->assertStringContainsString(
                "var(--glpi-mainmenu-bg)",
                $rendered_icon,
                $failed_message
            );
            $this->assertStringContainsString(
                "var(--glpi-helpdesk-header)",
                $rendered_icon,
                $failed_message
            );
        }
    }

    public function testIllustrationUseTheSpecifiedSize(): void
    {
        // Arrange: list all icons
        $manager = $this->getDefaultManager();
        $icons = $manager->getAllIllustrationsNames();

        // Act: render all icons
        $rendered_icons = array_map(
            fn($filename) => $manager->render($filename, 75),
            $icons
        );

        // Assert: the icon should have the given size
        $this->assertNotEmpty($rendered_icons);
        foreach ($rendered_icons as $rendered_icon) {
            $this->assertStringContainsString('width="75px"', $rendered_icon);
            $this->assertStringNotContainsString('width="100%"', $rendered_icon);
            $this->assertStringContainsString('height="75px"', $rendered_icon);
            $this->assertStringNotContainsString('height="100%"', $rendered_icon);
        }
    }

    public function testGetAllIllustrationNames(): void
    {
        $virtual_dir = 'glpi' . mt_rand();

        // Arrange: create a virtual file system and use it as our base directory
        vfsStream::setup($virtual_dir, null, [
            'valid-file1.svg' => 'fake_content',
            'not-an-svg.exe'  => 'fake_content',
            'no-extension'    => 'fake_content',
            'directory'       => [
                'nested-icon.svg' => 'fake_content',
            ],
            'valid-file2.svg' => 'fake_content',
        ]);
        $manager = new IllustrationManager(vfsStream::url($virtual_dir));

        // Act: get all illustrations
        $names = $manager->getAllIllustrationsNames();

        // Assert: only top level valid svg files should be found
        $this->assertEquals(['valid-file1.svg', 'valid-file2.svg'], $names);
    }

    public function testRenderMethodCantBeAbusedToReadFilesOutsideBaseFolder(): void
    {
        // We can't use VFS here because the `realpath` function used in
        // isValidIllustrationName() will always return false with them
        $glpi_dir          = realpath(FIXTURE_DIR . '/mocked_glpi_dir_for_illustrations');
        $illustrations_dir = "$glpi_dir/resources/illustration";

        $valid_svg               = "valid_svg.svg"; // Control subject
        $svg_path_outside_folder = "../../my_svg.svg";
        $file_outside_folder     = "../../confidential_info.txt";

        // Arrange: make sure our fixtures files are valid, else the test would
        // be meaningless
        $to_check = [
            $valid_svg,
            $svg_path_outside_folder,
            $file_outside_folder,
        ];
        foreach ($to_check as $path) {
            $path = "$illustrations_dir/$path";
            if (
                !file_exists($path)
                || !is_readable($path)
                || !is_file($path)
            ) {
                $this->fail("Invalid fixture file: $path");
            }
        }
        $manager = new IllustrationManager($illustrations_dir);

        // Act: try to get one valid file (control) and two external files
        $valid_svg_content = $manager->render($valid_svg);
        $svg_outside_folder_content = $manager->render($svg_path_outside_folder);
        $file_outside_folder_content = $manager->render($file_outside_folder);

        // Assert: only files inside the base folder are rendered
        $this->assertNotEmpty($valid_svg_content);
        $this->assertEmpty($svg_outside_folder_content);
        $this->assertEmpty($file_outside_folder_content);
    }
}
