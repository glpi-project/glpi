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

namespace tests\units\Glpi\Api;

use Change;
use Change_Item;
use ChangeValidation;
use Computer;
use Glpi\Api\APIRest;
use Glpi\Tests\DbTestCase;
use ReflectionClass;
use Session;
use User;

class APIRestGetItemTest extends DbTestCase
{
    public function testWithChangesOnlyReturnsVisibleChanges(): void
    {
        $this->login();
        $entities_id = $this->getTestRootEntity(only_id: true);
        $computer = $this->createItem(Computer::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $entities_id,
        ]);

        $targets = [
            'approver' => getItemByTypeName(User::class, 'tech', true),
            'other'    => getItemByTypeName(User::class, 'normal', true),
            'none'     => null,
        ];
        $ids = [];
        foreach ($targets as $case => $target) {
            $change = $this->createItem(Change::class, [
                'name'              => __FUNCTION__ . ' ' . $case,
                'content'           => __FUNCTION__,
                'entities_id'       => $entities_id,
                '_skip_auto_assign' => true,
            ], ['content']);
            $this->createItem(Change_Item::class, [
                'changes_id' => $change->getID(),
                'itemtype'   => Computer::class,
                'items_id'   => $computer->getID(),
            ]);
            if ($target !== null) {
                $this->createItem(ChangeValidation::class, [
                    'changes_id'      => $change->getID(),
                    'itemtype_target' => User::class,
                    'items_id_target' => $target,
                ]);
            }
            $ids[$case] = $change->getID();
        }

        // Approver only: no right on changes, only on approvals
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile'][Change::$rightname] = 0;
        $_SESSION['glpiactiveprofile'][ChangeValidation::$rightname] = ChangeValidation::VALIDATE;
        $_SESSION['glpiactiveprofile'][Computer::$rightname] = READ;
        Session::changeActiveEntities($entities_id, true);

        $api = (new ReflectionClass(APIRest::class))->newInstanceWithoutConstructor();
        $get_item = (new ReflectionClass(APIRest::class))->getMethod('getItem');
        $fields = $get_item->invoke($api, Computer::class, $computer->getID(), ['with_changes' => true, 'get_hateoas' => false]);

        $this->assertSame([$ids['approver']], array_map(static fn($c) => (int) $c['id'], $fields['_changes']));
    }
}
