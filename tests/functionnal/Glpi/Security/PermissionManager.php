<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace tests\units\Glpi\Security;

use DbTestCase;

class PermissionManager extends DbTestCase
{
    public function testHaveRight()
    {
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $root_entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $this->boolean(\Glpi\Security\PermissionManager::haveRight(
            $test_users_id,
            'computer',
            ALLSTANDARDRIGHT,
            false,
            $root_entities_id
        ))->isTrue();
        $this->boolean(\Glpi\Security\PermissionManager::haveRight(
            $test_users_id,
            'computer',
            PHP_INT_MAX,
            false,
            $root_entities_id
        ))->isFalse();

        $this->login();

        $this->boolean(\Glpi\Security\PermissionManager::haveRight(
            -1,
            'computer',
            ALLSTANDARDRIGHT
        ))->isTrue();
        $this->boolean(\Glpi\Security\PermissionManager::haveRight(
            -1,
            'computer',
            PHP_INT_MAX
        ))->isFalse();
    }

    public function testGetPossibleProfiles()
    {
        $this->integer(count(\Glpi\Security\PermissionManager::getPossibleProfiles('computer', CREATE)))->isEqualTo(4);
        $this->integer(count(\Glpi\Security\PermissionManager::getPossibleProfiles('computer', 255)))->isEqualTo(1);
    }

    public function testHasProfile()
    {
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $this->boolean(\Glpi\Security\PermissionManager::hasProfile($test_users_id, 4))->isTrue();
        $this->boolean(\Glpi\Security\PermissionManager::hasProfile($test_users_id, 8))->isFalse();

        $this->login();
        $this->boolean(\Glpi\Security\PermissionManager::hasProfile(-1, 4))->isTrue();
        $this->boolean(\Glpi\Security\PermissionManager::hasProfile(-1, 8))->isFalse();
    }

    public function testGetAggregatedRights()
    {
        $profile = new \Profile();
        $profiles_id_1 = $profile->add(['name' => __FUNCTION__ . '_1']);
        $this->integer($profiles_id_1)->isGreaterThan(0);
        $profiles_id_2 = $profile->add(['name' => __FUNCTION__ . '_2']);
        $this->integer($profiles_id_2)->isGreaterThan(0);

        \ProfileRight::updateProfileRights($profiles_id_1, [
            'computer' => READ,
            'ticket' => \Ticket::READMY,
        ]);

        \ProfileRight::updateProfileRights($profiles_id_2, [
            'monitor' => READ,
            'computer' => UPDATE,
            'ticket' => \Ticket::READMY | \Ticket::READASSIGN,
        ]);

        $aggregated = \Glpi\Security\PermissionManager::getAggregatedRights([
            $profiles_id_1,
            $profiles_id_2,
        ]);

        $this->array($aggregated)
            ->integer['computer']->isEqualTo(READ | UPDATE)
            ->integer['monitor']->isEqualTo(READ)
            ->integer['ticket']->isEqualTo(
                \Ticket::READMY | \Ticket::READASSIGN
            );
    }
}
