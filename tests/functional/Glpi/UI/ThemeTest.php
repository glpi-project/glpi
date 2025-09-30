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

use DbTestCase;
use Glpi\UI\Theme;
use Glpi\UI\ThemeManager;
use org\bovigo\vfs\vfsStream;

class ThemeTest extends DbTestCase
{
    public function testGetCoreThemes(): void
    {
        $themes = ThemeManager::getInstance()->getCoreThemes();
        $this->assertGreaterThan(0, count($themes));
        // Each element should be a Theme object
        foreach ($themes as $theme) {
            $this->assertInstanceOf(Theme::class, $theme);
        }
    }

    public function testGetCustomThemes(): void
    {
        vfsStream::setup('custom_themes', null, [
            'my_theme.scss' => '',
            'my_dark_theme.scss' => <<<SCSS
\$is-dark: true;
SCSS,
        ]);
        $theme_manager = $this->getMockBuilder(ThemeManager::class)
            ->onlyMethods(['getCustomThemesDirectory'])
            ->getMock();
        $theme_manager->method('getCustomThemesDirectory')->willReturn(vfsStream::url('custom_themes'));

        $custom_themes = $theme_manager->getCustomThemes();
        $this->assertCount(2, $custom_themes);
        $my_theme = $theme_manager->getTheme('my_theme');
        $this->assertNotNull($my_theme);
        $this->assertFalse($my_theme->isDarkTheme());
        $this->assertSame('My theme', $my_theme->getName());
        $my_dark_theme = $theme_manager->getTheme('my_dark_theme');
        $this->assertNotNull($my_dark_theme);
        $this->assertTrue($my_dark_theme->isDarkTheme());
        $this->assertSame('My dark theme', $my_dark_theme->getName());
    }

    public function testGetAllThemes(): void
    {
        $themes = ThemeManager::getInstance()->getCoreThemes();
        $this->assertGreaterThan(0, count($themes));
        // Each element should be a Theme object
        foreach ($themes as $theme) {
            $this->assertInstanceOf(Theme::class, $theme);
        }
    }

    public function testGetCurrentTheme(): void
    {
        $this->login();
        $theme = ThemeManager::getInstance()->getCurrentTheme();
        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertSame('auror', $theme->getKey());
    }

    public function testGetTheme(): void
    {
        $theme = ThemeManager::getInstance()->getTheme('auror');
        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertEquals('auror', $theme->getKey());
    }

    public function testProperties(): void
    {
        $theme_auror = ThemeManager::getInstance()->getTheme('auror');
        $this->assertEquals('auror', $theme_auror->getKey());
        $this->assertEquals('Auror', $theme_auror->getName());
        $this->assertFalse($theme_auror->isCustomTheme());
        $this->assertFalse($theme_auror->isDarkTheme());

        $theme_midnight = ThemeManager::getInstance()->getTheme('midnight');
        $this->assertEquals('midnight', $theme_midnight->getKey());
        $this->assertEquals('Midnight', $theme_midnight->getName());
        $this->assertFalse($theme_midnight->isCustomTheme());
        $this->assertTrue($theme_midnight->isDarkTheme());
    }

    public function testConfigGetPalletes(): void
    {
        $themes = ThemeManager::getInstance()->getAllThemes();
        $palettes = (new \Config())->getPalettes();
        $this->assertCount(count($themes), $palettes);
        foreach ($themes as $theme) {
            $this->assertArrayHasKey($theme->getKey(), $palettes);
            $this->assertEquals($theme->getName(), $palettes[$theme->getKey()]);
        }
    }
}
