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

namespace tests\units\Glpi\Features;

/**
 * Test for the {@link \Glpi\Features\Clonable} feature
 */
class Clonable extends \DbTestCase
{
    public function massiveActionTargetingProvider()
    {
        return [
            [\Computer::class, true],
            [\Monitor::class, true],
            [\Software::class, true],
            [\Ticket::class, true],
            [\Plugin::class, false],
            [\Config::class, false]
        ];
    }

    /**
     * @param $class
     * @param $result
     * @dataProvider massiveActionTargetingProvider
     */
    public function testMassiveActionTargeting($class, $result)
    {
        $this->login();
        $ma_prefix = 'MassiveAction' . \MassiveAction::CLASS_ACTION_SEPARATOR;
        $actions = \MassiveAction::getAllMassiveActions($class);
        $this->boolean(array_key_exists($ma_prefix . 'clone', $actions))->isIdenticalTo($result);
    }
}
