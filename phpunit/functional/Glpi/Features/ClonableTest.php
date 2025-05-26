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

namespace tests\units\Glpi\Features;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test for the {@link \Glpi\Features\Clonable} feature
 */
class ClonableTest extends \DbTestCase
{
    public static function massiveActionTargetingProvider()
    {
        return [
            [\Computer::class, true],
            [\Monitor::class, true],
            [\Software::class, true],
            [\Ticket::class, true],
            [\Plugin::class, false],
            [\Config::class, false],
        ];
    }

    #[DataProvider('massiveActionTargetingProvider')]
    public function testMassiveActionTargeting($class, $result)
    {
        $this->login();
        $ma_prefix = 'MassiveAction' . \MassiveAction::CLASS_ACTION_SEPARATOR;
        $actions = \MassiveAction::getAllMassiveActions($class);
        $this->assertSame($result, array_key_exists($ma_prefix . 'clone', $actions));
        // Create template option never should exist when not targetting a single item
        $this->assertFalse(array_key_exists($ma_prefix . 'create_template', $actions));

        if ($result === true) {
            $item = $this->createItem($class, [
                'name' => 'Test',
                'entities_id' => $this->getTestRootEntity(true),
                'content' => '',
            ], ['content']);
            if ($item->maybeTemplate()) {
                $specific_actions = \MassiveAction::getAllMassiveActions($class, false, $item, $item->getID());
                $this->assertTrue(array_key_exists($ma_prefix . 'create_template', $specific_actions));
            }
        }
    }
}
