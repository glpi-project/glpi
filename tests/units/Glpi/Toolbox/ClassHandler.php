<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\Toolbox;

use GLPITestCase;

class ClassHandler extends GLPITestCase
{
    protected function getProperClassnameProvider(): iterable
    {
        // Good case and namespace (should not be altered)
        yield [
            'classname' => 'Database',
            'expected'  => 'Database',
        ];
        yield [
            'classname' => 'Glpi\\Console\\Database\\UpdateCommand',
            'expected'  => 'Glpi\\Console\\Database\\UpdateCommand',
        ];
        yield [
            'classname' => 'PluginTesterTestItem',
            'expected'  => 'PluginTesterTestItem',
        ];
        yield [
            'classname' => 'GlpiPlugin\\Tester\\Service\\CacheService',
            'expected'  => 'GlpiPlugin\\Tester\\Service\\CacheService',
        ];

        // Bad case classnames matching an existing class file
        yield [
            'classname' => 'computer',
            'expected'  => 'Computer',
        ];
        yield [
            'classname' => 'glPI\\sockeT',
            'expected'  => 'Glpi\\Socket',
        ];
        yield [
            'classname' => 'glpi\\CoNsOlE\\database\\Installcommand',
            'expected'  => 'Glpi\\Console\\Database\\InstallCommand',
        ];
        yield [
            'classname' => 'PluginTesterBaSeClaSs',
            'expected'  => 'PluginTesterBaseClass',
        ];
        yield [
            'classname' => 'GlpiPlugin\\Tester\\RootNamESpACedItem',
            'expected'  => 'GlpiPlugin\\Tester\\RootNamespacedItem',
        ];
        yield [
            'classname' => 'GlpiPlugin\\Tester\\SeRviCe\\DBservice',
            'expected'  => 'GlpiPlugin\\Tester\\Service\\DbService',
        ];

        // Classname that does not use the good namespace
        yield [
            'classname' => 'Event',
            'expected'  => 'Glpi\\Event',
        ];
        yield [
            'classname' => 'Glpi\\Monitor',
            'expected'  => 'Monitor',
        ];
        yield [
            'classname' => 'GlpiPlugin\\Tester\\ItemWithoutNamespace',
            'expected'  => 'PluginTesterItemWithoutNamespace',
        ];
        yield [
            'classname' => 'PluginTesterSomeNamespacedItem',
            'expected'  => 'GlpiPlugin\\Tester\\SomeNamespacedItem',
        ];

        // Edge case using escaped slashes as namespace separator
        yield [
            'classname' => 'Glpi\\\\Supplier',
            'expected'  => 'Supplier',
        ];

        // Not matching any class file
        yield [
            'classname' => 'notaclassname',
            'expected'  => null,
        ];
        yield [
            'classname' => 'Glpi\\Not\\Valid',
            'expected'  => null,
        ];
        yield [
            'classname' => 'PluginTesterInvalidItem',
            'expected'  => null,
        ];
        yield [
            'classname' => 'GlpiPlugin\\Tester\\InvalidClassname',
            'expected'  => null,
        ];
    }

    /**
     * @dataProvider getProperClassnameProvider
     */
    public function testGetProperClassname(string $classname, ?string $expected): void
    {
        $this->newTestedInstance();
        $result = $this->testedInstance->getProperClassname($classname);
        $this->variable($result)->isEqualTo($expected);
    }
}
