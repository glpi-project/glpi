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

namespace Glpi\Form\AccessControl\ControlType;

use JsonConfigInterface;
use Toolbox;

final class DirectAccessConfig implements JsonConfigInterface
{
    public readonly string $token;
    public readonly bool $allow_unauthenticated;
    public readonly bool $force_direct_access;

    public function __construct(array $data = [])
    {
        // Access token
        $token = Toolbox::getRandomString(40);
        if (isset($data['token'])) {
            $token = $data['token'];
        }
        $this->token = $token;

        // Allow unauthenticated
        $allow_unauthenticated = false;
        if (isset($data['allow_unauthenticated'])) {
            $allow_unauthenticated = (bool) $data['allow_unauthenticated'];
        }
        $this->allow_unauthenticated = $allow_unauthenticated;

        // Force direct access
        $force_direct_access = false;
        if (isset($data['force_direct_access'])) {
            $force_direct_access = (bool) $data['force_direct_access'];
        }
        $this->force_direct_access = $force_direct_access;
    }
}
