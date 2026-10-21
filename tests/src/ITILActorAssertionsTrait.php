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

namespace Glpi\Tests;

use CommonITILObject;

/**
 * Assertions on the actors linked to an ITIL object, read from the database.
 */
trait ITILActorAssertionsTrait
{
    protected function isActorLinked(CommonITILObject $itil, string $actor_itemtype, int $actor_id, int $actor_type): bool
    {
        $link = $itil->getActorObjectForItem($actor_itemtype);

        return countElementsInTable($link::getTable(), [
            $link::getItilObjectForeignKey() => $itil->getID(),
            $link->getActorForeignKey() => $actor_id,
            'type' => $actor_type,
        ]) > 0;
    }

    protected function assertActorLinked(CommonITILObject $itil, string $actor_itemtype, int $actor_id, int $actor_type, string $message = ''): void
    {
        $this->assertTrue($this->isActorLinked($itil, $actor_itemtype, $actor_id, $actor_type), $message);
    }

    protected function assertActorNotLinked(CommonITILObject $itil, string $actor_itemtype, int $actor_id, int $actor_type, string $message = ''): void
    {
        $this->assertFalse($this->isActorLinked($itil, $actor_itemtype, $actor_id, $actor_type), $message);
    }
}
