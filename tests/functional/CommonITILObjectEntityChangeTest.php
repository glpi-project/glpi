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

namespace tests\units;

use Change;
use CommonITILObject;
use Entity;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Session;
use Ticket;

/**
 * Entity change of open ITIL objects: the target entity must be accessible to the user.
 */
class CommonITILObjectEntityChangeTest extends DbTestCase
{
    #[DataProvider('itilClassProvider')]
    public function testMoveOpenItilToAnotherEntity(string $itil_class): void
    {
        // --- arrange ---
        $itil = $this->createOpenItil($itil_class, '_test_child_1');
        $new_entities_id = getItemByTypeName(Entity::class, '_test_child_2')->getID();
        assert((new $itil_class())->can($itil->getID(), UPDATE), 'Update right on the item is required: only the target entity access must decide');
        assert(Session::haveAccessToEntity($new_entities_id), 'Target entity access is required: the move must be allowed');

        // --- act ---
        $itil->update(['id' => $itil->getID(), 'entities_id' => $new_entities_id]);

        // --- assert ---
        $this->assertSame($new_entities_id, $this->reloadItem($itil)->fields['entities_id'], 'Entity should be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testMoveOpenItilToEntityWithoutAccess(string $itil_class): void
    {
        // --- arrange ---
        $itil = $this->createOpenItil($itil_class, '_test_child_1');
        $initial_entities_id = $itil->fields['entities_id'];
        $new_entities_id = $this->getTestRootEntity(true);
        $this->setEntity('_test_child_1', false);
        assert((new $itil_class())->can($itil->getID(), UPDATE), 'Update right on the item is required: only the target entity access must block the move');
        assert(!Session::haveAccessToEntity($new_entities_id), 'Parent entity must not be accessible: only the active entity is');

        // --- act ---
        $itil->update(['id' => $itil->getID(), 'entities_id' => $new_entities_id]);

        // --- assert ---
        $this->assertSame($initial_entities_id, $this->reloadItem($itil)->fields['entities_id'], 'Entity should not be changed');
    }

    #[DataProvider('itilClassProvider')]
    public function testMoveOpenItilToNonexistentEntity(string $itil_class): void
    {
        // --- arrange ---
        $itil = $this->createOpenItil($itil_class, '_test_child_1');
        $initial_entities_id = $itil->fields['entities_id'];
        $new_entities_id = 999999;
        $this->setEntity('_test_child_1', false);
        assert((new $itil_class())->can($itil->getID(), UPDATE), 'Update right on the item is required: only the target entity must block the move');
        assert(!(new Entity())->getFromDB($new_entities_id), 'Target entity must not exist');

        // --- act ---
        $itil->update(['id' => $itil->getID(), 'entities_id' => $new_entities_id]);

        // --- assert ---
        $this->assertSame($initial_entities_id, $this->reloadItem($itil)->fields['entities_id'], 'Entity should not be changed');
    }

    public static function itilClassProvider(): iterable
    {
        yield Ticket::class => ['itil_class' => Ticket::class];
        yield Change::class => ['itil_class' => Change::class];
        yield Problem::class => ['itil_class' => Problem::class];
    }

    /**
     * @param class-string<CommonITILObject> $itil_class
     */
    private function createOpenItil(string $itil_class, string $entity_name): CommonITILObject
    {
        assert(is_a($itil_class, CommonITILObject::class, true), 'Only CommonITILObject class is allowed in ' . __FUNCTION__);

        $this->login();

        return $this->createItem($itil_class, [
            'entities_id' => getItemByTypeName(Entity::class, $entity_name)->getID(),
        ] + $this->getMinimalCreationInput($itil_class));
    }
}
