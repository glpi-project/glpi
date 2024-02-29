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

namespace tests\units;

use DbTestCase;

class ProfileRight extends DbTestCase
{
    public function testUpdateProfileLastRightsUpdate()
    {
        // Create a profile
        $profile = getItemByTypeName('Profile', 'Super-Admin');

        // Update the last_rights_update field to null
        $this->updateItem('Profile', $profile->getID(), [
            'last_rights_update' => null
        ]);
        $profile->getFromDB($profile->getID());

        // Check that the last_rights_update field is null
        $this->variable($profile->fields['last_rights_update'])->isNull();

        // Create a profile right
        $profileRight = $this->createItem('ProfileRight', [
            'profiles_id' => $profile->getID(),
            'name'    => 'testUpdateProfileLastRightsUpdate',
            'rights'  => READ
        ]);
        $profile->getFromDB($profile->getID());

        // Check that the last_rights_update field is not null
        $this->variable($profile->fields['last_rights_update'])->isNotNull();

        // Update the last_rights_update field to null
        $this->updateItem('Profile', $profile->getID(), [
            'last_rights_update' => null
        ]);
        $profile->getFromDB($profile->getID());

        // Check that the last_rights_update field is null
        $this->variable($profile->fields['last_rights_update'])->isNull();

        // Update the profile right
        $this->updateItem('ProfileRight', $profileRight->getID(), [
            'rights' => READ | UPDATE
        ]);
        $profile->getFromDB($profile->getID());

        // Check that the last_rights_update field is not null
        $this->variable($profile->fields['last_rights_update'])->isNotNull();
    }
}
