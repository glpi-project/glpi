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

namespace tests\units;

use DbTestCase;
use Plugin;

class PluginTest extends DbTestCase
{
    public function testGetWebDir(): void
    {
        global $CFG_GLPI;

        $this->assertEquals('plugins/tester', @Plugin::getWebDir('tester', false, false));
        $this->assertEquals('marketplace/myplugin', @Plugin::getWebDir('myplugin', false, false));

        foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
            $CFG_GLPI['root_doc'] = $root_doc;
            $this->assertEquals($root_doc . '/plugins/tester', @Plugin::getWebDir('tester', true, false));
            $this->assertEquals($root_doc . '/marketplace/myplugin', @Plugin::getWebDir('myplugin', true, false));
        }

        foreach (['http://localhost', 'https://www.example.org/glpi'] as $url_base) {
            $CFG_GLPI['url_base'] = $url_base;
            $this->assertEquals($url_base . '/plugins/tester', @Plugin::getWebDir('tester', true, true));
            $this->assertEquals($url_base . '/marketplace/myplugin', @Plugin::getWebDir('myplugin', true, true));
        }

        $this->assertFalse(@Plugin::getWebDir('notaplugin', true, true));
    }
}
