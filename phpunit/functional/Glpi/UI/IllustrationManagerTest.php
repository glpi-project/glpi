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

use DirectoryIterator;
use Glpi\UI\IllustrationManager;
use GLPITestCase;

final class IllustrationManagerTest extends GLPITestCase
{
    private function getTestedInstance(): IllustrationManager
    {
        return new IllustrationManager();
    }

    public function testIllustrationsAreLoaded(): void
    {
        // Act: try to load a given illustration
        $manager = $this->getTestedInstance();
        $render = $manager->render('report-issue.svg');

        // Assert: load sould succeed
        $this->assertNotEmpty($render);
    }

    public function testIllustrationsUseThemeColors(): void
    {
        // Arrange: list all icons
        $icons = [];
        $icons_files = new DirectoryIterator(GLPI_ROOT . "/pics/illustration");
        foreach ($icons_files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            if ($file->getExtension() !== 'svg') {
                continue;
            }

            $icons[] = $file->getFilename();
        }

        // Act: render all icons
        $manager = $this->getTestedInstance();
        $rendered_icons = array_map(
            fn($filename) => $manager->render($filename),
            $icons
        );

        // Assert: renderered content should not contain harcoded references to
        // colors
        $this->assertNotEmpty($rendered_icons);
        foreach ($rendered_icons as $rendered_icon) {
            $this->assertStringNotContainsString("rgb(", $rendered_icon);
            $this->assertStringContainsString("var(--tblr-primary)", $rendered_icon);
            $this->assertStringContainsString("var(--glpi-mainmenu-bg)", $rendered_icon);
            $this->assertStringContainsString("var(--glpi-helpdesk-header)", $rendered_icon);
        }
    }

    public function testIllustrationUseTheSpecifiedSize(): void
    {
        // Act: try to load a given illustration
        $manager = $this->getTestedInstance();
        $render = $manager->render('report-issue.svg', 75);

        // Assert: the icon should have the given size
        $this->assertStringContainsString('width="75px"', $render);
        $this->assertStringNotContainsString('width="100%"', $render);
        $this->assertStringContainsString('height="75px"', $render);
        $this->assertStringNotContainsString('height="100%"', $render);
    }
}
