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

namespace tests\units\Glpi\Session;

class SessionInfo extends \GLPITestCase
{
    public function testGetUserId(): void
    {
        $session_info = new \Glpi\Session\SessionInfo(
            user_id: 500,
        );
        $this->integer($session_info->getUserId())->isEqualTo(500);
    }

    public function testGetGroupsIds(): void
    {
        $session_info = new \Glpi\Session\SessionInfo(
            group_ids: [1, 2, 3],
        );
        $this->array($session_info->getGroupsIds())->isEqualTo([1, 2, 3]);
    }

    public function testGetProfileId(): void
    {
        $session_info = new \Glpi\Session\SessionInfo(
            profile_id: 13,
        );
        $this->integer($session_info->getProfileId())->isEqualTo(13);
    }
}
