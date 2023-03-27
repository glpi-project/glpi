<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

/**
 * Tests for Profile_User class
 */
class Profile_User extends DbTestCase
{
    /**
     * Tests for Profile_User->canPurgeItem()
     *
     * @return void
     */
    public function testCanPurgeItem(): void
    {
        // Default: only one super admin account
        $super_admin = getItemByTypeName('Profile', 'Super-Admin');
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();

        // Default: 3 super admin account authorizations
        $authorizations = (new \Profile_User())->find([
            'profiles_id' => $super_admin->fields['id']
        ]);
        $this->array($authorizations)->hasSize(3);
        $this->array(array_column($authorizations, 'id'))->isEqualTo([
            2, // glpi
            6, // TU_USER
            7, // jsmith123
        ]);

        // Delete 2 authorizations
        $this->login('glpi', 'glpi');
        $this->boolean(\Profile_User::getById(6)->canPurgeItem())->isTrue();
        $this->boolean((new \Profile_User())->delete(['id' => 6], 1))->isTrue();
        $this->boolean(\Profile_User::getById(7)->canPurgeItem())->isTrue();
        $this->boolean((new \Profile_User())->delete(['id' => 7], 1))->isTrue();

        // Last user, can't be purged
        $this->boolean(\Profile_User::getById(2)->canPurgeItem())->isFalse();
        // Can still be purged by calling delete, maybe it should not be possible ?
        $this->boolean((new \Profile_User())->delete(['id' => 2], 1))->isTrue();
    }
}
