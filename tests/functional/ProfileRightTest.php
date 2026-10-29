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

use Glpi\Tests\DbTestCase;

class ProfileRightTest extends DbTestCase
{
    public function testUpdateProfileLastRightsUpdate()
    {
        // Create a profile
        $profile = getItemByTypeName('Profile', 'Super-Admin');

        // Update the last_rights_update field to null
        $this->updateItem('Profile', $profile->getID(), [
            'last_rights_update' => null,
        ]);
        $this->assertTrue($profile->getFromDB($profile->getID()));

        // Check that the last_rights_update field is null
        $this->assertNull($profile->fields['last_rights_update']);

        // Create a profile right
        $profileRight = $this->createItem('ProfileRight', [
            'profiles_id' => $profile->getID(),
            'name'    => 'testUpdateProfileLastRightsUpdate',
            'rights'  => READ,
        ]);
        $this->assertTrue($profile->getFromDB($profile->getID()));

        // Check that the last_rights_update field is not null
        $this->assertNotNull($profile->fields['last_rights_update']);

        // Update the last_rights_update field to null
        $this->updateItem('Profile', $profile->getID(), [
            'last_rights_update' => null,
        ]);
        $this->assertTrue($profile->getFromDB($profile->getID()));

        // Check that the last_rights_update field is null
        $this->assertNull($profile->fields['last_rights_update']);

        // Update the profile right
        $this->updateItem('ProfileRight', $profileRight->getID(), [
            'rights' => READ | UPDATE,
        ]);
        $this->assertTrue($profile->getFromDB($profile->getID()));

        // Check that the last_rights_update field is not null
        $this->assertNotNull($profile->fields['last_rights_update']);
    }

    public function testCannotGrantRightsNotHeld()
    {
        $this->login();

        // Delegated profile manager: `profile` READ+UPDATE+CREATE, nothing on `config`, READ on `ticket`.
        $_SESSION['glpiactiveprofile']['config']  = 0;
        $_SESSION['glpiactiveprofile']['profile'] = READ | UPDATE | CREATE;
        $_SESSION['glpiactiveprofile']['ticket']  = READ;

        $profile = new \Profile();
        $profiles_id = $profile->add(['name' => __FUNCTION__, 'interface' => 'central', 'config' => READ | UPDATE]);
        $this->assertGreaterThan(0, $profiles_id);
        $this->hasSessionMessages(ERROR, ['You cannot grant rights that you do not have.']);
        $this->assertSame(0, \ProfileRight::getProfileRights($profiles_id, ['config'])['config']);

        // Update: `config` is refused, an held right is granted.
        $this->assertTrue($profile->update([
            'id'      => $profiles_id,
            '_config' => [READ . '_0' => 1, UPDATE . '_0' => 1],
            '_ticket' => [READ . '_0' => 1],
        ]));
        $this->hasSessionMessages(ERROR, ['You cannot grant rights that you do not have.']);
        $rights = \ProfileRight::getProfileRights($profiles_id, ['config', 'ticket']);
        $this->assertSame(0, $rights['config']);
        $this->assertSame(READ, $rights['ticket']);

        // Direct ProfileRight writes (e.g. from the API) are filtered the same way.
        $right = new \ProfileRight();
        $this->assertTrue($right->getFromDBByCrit(['profiles_id' => $profiles_id, 'name' => 'config']));
        $this->assertTrue($right->update(['id' => $right->getID(), 'rights' => READ | UPDATE]));
        $this->hasSessionMessages(ERROR, ['You cannot grant rights that you do not have.']);
        $this->assertSame(0, \ProfileRight::getProfileRights($profiles_id, ['config'])['config']);

        // A right entry can't be moved to another profile.
        $super_admin = getItemByTypeName('Profile', 'Super-Admin', true);
        $this->assertTrue($right->getFromDBByCrit(['profiles_id' => $super_admin, 'name' => 'config']));
        $right->update(['id' => $right->getID(), 'profiles_id' => $profiles_id]);
        $this->assertTrue($right->getFromDB($right->getID()));
        $this->assertSame($super_admin, $right->fields['profiles_id']);

        // Rights already held by the edited profile are kept, even if the current user lacks them.
        $this->assertTrue($right->update(['id' => $right->getID(), 'rights' => READ | UPDATE]));
        $this->assertSame(READ | UPDATE, \ProfileRight::getProfileRights($super_admin, ['config'])['config']);
    }
}
