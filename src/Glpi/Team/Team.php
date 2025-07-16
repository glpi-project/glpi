<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Team;

use CommonITILActor;

/**
 * Class for a team that may contain members of multiple types (users, groups, etc) of various roles.
 * @since 10.0.0
 */
final class Team
{
    /**
     * A team member that is requesting the ticket, change, problem, etc.
     */
    public const ROLE_REQUESTER = CommonITILActor::REQUESTER;

    /**
     * A team member that is watching the ticket, change, problem, etc.
     */
    public const ROLE_OBSERVER = CommonITILActor::OBSERVER;

    /**
     * A team member that is assigned to the ticket, change, problem, etc.
     */
    public const ROLE_ASSIGNED = CommonITILActor::ASSIGN;

    /**
     * A team member who is an owner of the item.
     * Typically, this is used for Projects (Project managers).
     */
    public const ROLE_OWNER = 5;

    /**
     * The member who "wrote" or submitted a ticket, change, or problem.
     */
    public const ROLE_WRITER = 6;

    /**
     * A general team member for when a more specific role is not applicable.
     * Typically, this is used for Projects for anyone who is not the Project manager.
     */
    public const ROLE_MEMBER = 7;
}
