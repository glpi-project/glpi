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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

final class AllowListConfig extends \GLPITestCase
{
    public function testCreateFromRawArray(): void
    {
        $config = \Glpi\Form\AccessControl\ControlType\AllowListConfig::createFromRawArray([
            'user_ids'    => [1, 2, 3],
            'group_ids'   => [4, 5, 6],
            'profile_ids' => [7, 8, 9],
        ]);
        $this->array($config->getUserIds())->isEqualTo([1, 2, 3]);
        $this->array($config->getGroupIds())->isEqualTo([4, 5, 6]);
        $this->array($config->getProfileIds())->isEqualTo([7, 8, 9]);
    }

    public function testGetUserIds(): void
    {
        $allow_list_config = new \Glpi\Form\AccessControl\ControlType\AllowListConfig(
            user_ids: [1, 2, 3],
        );
        $this->array($allow_list_config->getUserIds())->isEqualTo([1, 2, 3]);
    }

    public function testGetGroupIds(): void
    {
        $allow_list_config = new \Glpi\Form\AccessControl\ControlType\AllowListConfig(
            group_ids: [4, 5, 6],
        );
        $this->array($allow_list_config->getGroupIds())->isEqualTo([4, 5, 6]);
    }

    public function testGetProfileIds(): void
    {
        $allow_list_config = new \Glpi\Form\AccessControl\ControlType\AllowListConfig(
            profile_ids: [7, 8, 9],
        );
        $this->array($allow_list_config->getProfileIds())->isEqualTo([7, 8, 9]);
    }
}
