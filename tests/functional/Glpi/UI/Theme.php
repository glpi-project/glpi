<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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
use Glpi\UI\ThemeManager;
use org\bovigo\vfs\vfsStream;

class Theme extends DbTestCase
{
    public function testGetCoreThemes()
    {
        $themes = ThemeManager::getInstance()->getCoreThemes();
        $this->integer(count($themes))->isGreaterThan(0);
        // Each element should be a Theme object
        foreach ($themes as $theme) {
            $this->object($theme)->isInstanceOf(\Glpi\UI\Theme::class);
        }
    }

    public function testGetCustomThemes()
    {
        vfsStream::setup('custom_themes', null, [
            'my_theme.scss' => '',
            'my_dark_theme.scss' => <<<SCSS
\$is-dark: true;
SCSS
        ]);
        $theme_manager = new \mock\Glpi\UI\ThemeManager();
        $this->calling($theme_manager)->getCustomThemesDirectory = static function () {
            return vfsStream::url('custom_themes');
        };
        $custom_themes = $theme_manager->getCustomThemes();
        $this->integer(count($custom_themes))->isIdenticalTo(2);
        $my_theme = $theme_manager->getTheme('my_theme');
        $this->object($my_theme)->isNotNull();
        $this->boolean($my_theme->isDarkTheme())->isFalse();
        $this->string($my_theme->getName())->isIdenticalTo('My theme');
        $my_dark_theme = $theme_manager->getTheme('my_dark_theme');
        $this->object($my_dark_theme)->isNotNull();
        $this->boolean($my_dark_theme->isDarkTheme())->isTrue();
        $this->string($my_dark_theme->getName())->isIdenticalTo('My dark theme');
    }

    public function testGetAllThemes()
    {
        $themes = ThemeManager::getInstance()->getCoreThemes();
        $this->integer(count($themes))->isGreaterThan(0);
        // Each element should be a Theme object
        foreach ($themes as $theme) {
            $this->object($theme)->isInstanceOf(\Glpi\UI\Theme::class);
        }
    }

    public function testGetCurrentTheme()
    {
        $this->login();
        $theme = ThemeManager::getInstance()->getCurrentTheme();
        $this->object($theme)->isInstanceOf(\Glpi\UI\Theme::class);
        $this->string($theme->getKey())->isEqualTo('auror');
    }

    public function testGetTheme()
    {
        $theme = ThemeManager::getInstance()->getTheme('auror');
        $this->object($theme)->isInstanceOf(\Glpi\UI\Theme::class);
        $this->string($theme->getKey())->isEqualTo('auror');
    }

    public function testProperties()
    {
        $theme_auror = ThemeManager::getInstance()->getTheme('auror');
        $this->string($theme_auror->getKey())->isEqualTo('auror');
        $this->string($theme_auror->getName())->isEqualTo('Auror');
        $this->boolean($theme_auror->isCustomTheme())->isFalse();
        $this->boolean($theme_auror->isDarkTheme())->isFalse();

        $theme_midnight = ThemeManager::getInstance()->getTheme('midnight');
        $this->string($theme_midnight->getKey())->isEqualTo('midnight');
        $this->string($theme_midnight->getName())->isEqualTo('Midnight');
        $this->boolean($theme_midnight->isCustomTheme())->isFalse();
        $this->boolean($theme_midnight->isDarkTheme())->isTrue();
    }

    public function testConfigGetPalletes()
    {
        $themes = ThemeManager::getInstance()->getAllThemes();
        $palettes = (new \Config())->getPalettes();
        $this->integer(count($palettes))->isEqualTo(count($themes));
        foreach ($themes as $theme) {
            $this->array($palettes)->hasKey($theme->getKey());
            $this->string($palettes[$theme->getKey()])->isEqualTo($theme->getName());
        }
    }
}
