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

/* Test for inc/operatingsystem.class.php */

class OperatingSystemEditionTest extends CommonDropdown
{
    public function getObjectClass()
    {
        return '\OperatingSystemEdition';
    }

    public static function typenameProvider()
    {
        return [
            [\OperatingSystemEdition::getTypeName(), 'Editions'],
            [\OperatingSystemEdition::getTypeName(0), 'Editions'],
            [\OperatingSystemEdition::getTypeName(10), 'Editions'],
            [\OperatingSystemEdition::getTypeName(1), 'Edition'],
        ];
    }

    public function testMaybeTranslated()
    {
        $instance = $this->newInstance();
        $this->assertTrue($instance->maybeTranslated());
    }

    protected function getTabs()
    {
        return [
            'OperatingSystemEdition$main' => "Edition",
            'DropdownTranslation$1' => "Translations",
        ];
    }

    /**
     * Create new Operating system in database
     *
     * @return \CommonDBTM
     */
    protected function newInstance(): \CommonDBTM
    {
        $instance = new \OperatingSystemEdition();
        $this->assertGreaterThan(
            0,
            $instance->add([
                'name' => 'OS name ' . $this->getUniqueString(),
            ])
        );
        $this->assertTrue($instance->getFromDB($instance->getID()));
        return $instance;
    }
}
