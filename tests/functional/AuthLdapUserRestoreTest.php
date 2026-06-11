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

use AuthLDAP;
use Glpi\Tests\DbTestCase;
use User;

/**
 * Non-regression tests for LDAP user restore.
 *
 * When a user is disabled in Active Directory and the sync runs without
 * ACTION_ALL, is_deleted_ldap is never set. On re-enable, the restore
 * must still fire based on is_active alone.
 */
class AuthLdapUserRestoreTest extends DbTestCase
{
    /**
     * A user inactive with is_deleted_ldap=0 must be re-enabled by
     * manageRestoredUserInLdap().
     */
    public function testRestoreEnablesUserEvenWhenIsDeletedLdapIsFalse(): void
    {
        global $CFG_GLPI;

        $user = $this->createItem(User::class, [
            'name'            => $this->getUniqueString(),
            'is_active'       => 0,
            'is_deleted_ldap' => 0,
        ]);

        $CFG_GLPI['user_restored_ldap'] = AuthLDAP::RESTORED_USER_ENABLE;
        User::manageRestoredUserInLdap($user->getID());

        $user->getFromDB($user->getID());
        $this->assertSame(1, (int) $user->fields['is_active']);
        $this->assertSame(0, (int) $user->fields['is_deleted_ldap']);
    }

    /**
     * Nominal path: user marked as LDAP-deleted (is_deleted_ldap=1) must
     * be re-enabled by manageRestoredUserInLdap().
     */
    public function testRestoreEnablesUserWhenIsDeletedLdapIsTrue(): void
    {
        global $CFG_GLPI;

        $user = $this->createItem(User::class, [
            'name'            => $this->getUniqueString(),
            'is_active'       => 0,
            'is_deleted_ldap' => 1,
        ]);

        $CFG_GLPI['user_restored_ldap'] = AuthLDAP::RESTORED_USER_ENABLE;
        User::manageRestoredUserInLdap($user->getID());

        $user->getFromDB($user->getID());
        $this->assertSame(1, (int) $user->fields['is_active']);
        $this->assertSame(0, (int) $user->fields['is_deleted_ldap']);
    }
}
