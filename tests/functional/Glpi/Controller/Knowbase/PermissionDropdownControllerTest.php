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

namespace tests\units\Glpi\Controller\Knowbase;

use Glpi\Controller\Knowbase\PermissionDropdownController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use User;

final class PermissionDropdownControllerTest extends DbTestCase
{
    public function testGetUsedIds(): void
    {
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'   => __FUNCTION__,
            'answer' => 'Answer',
        ]);
        $users_ids = [
            getItemByTypeName(User::class, 'glpi', true),
            getItemByTypeName(User::class, 'tech', true),
        ];
        foreach ($users_ids as $users_id) {
            $this->createItem(KnowbaseItem_User::class, [
                'knowbaseitems_id' => $kb->getID(),
                'users_id'         => $users_id,
            ]);
        }

        $controller = new PermissionDropdownController($this->getDbConnection());

        $this->assertEqualsCanonicalizing(
            $users_ids,
            $this->callPrivateMethod($controller, 'getUsedIds', KnowbaseItem_User::class, 'users_id', $kb->getID())
        );
        $this->assertSame(
            [],
            $this->callPrivateMethod($controller, 'getUsedIds', KnowbaseItem_User::class, 'users_id', $kb->getID() + 1)
        );
    }
}
