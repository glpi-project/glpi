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

namespace Glpi\System\Requirement;

final class IntegerSize extends AbstractRequirement
{
    public function __construct()
    {
        parent::__construct(
            __('PHP maximal integer size'),
            __('Support of 64 bits integers is required for IP addresses related operations (network inventory, API clients IP filtering, ...).'),
            true
        );
    }

    protected function check()
    {
        if (PHP_INT_SIZE < 8) {
            $this->validated = false;
            $this->validation_messages[] = __('OS or PHP is not relying on 64 bits integers, operations on IP addresses may produce unexpected results.');
        } else {
            $this->validated = true;
            $this->validation_messages[] = __('OS and PHP are relying on 64 bits integers.');
        }
    }
}
