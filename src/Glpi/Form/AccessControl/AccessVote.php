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

namespace Glpi\Form\AccessControl;

/**
 * ControlTypeInterface::canAnwser require to return an AccessVote.
 * Ideally, a control type will pick between two types depending on how it is
 * labelled to end users.
 *
 * Here is an example for an hypothetical control type that would manager access
 * according to the user IP.
 * The control type could word its label in 3 different ways and for each labels
 * there is a logical vote couple:
 * vote couples:
 * - "Allow access by specific IP": (Grant, Abstain)
 * - "Restrict access by specific IP": (Grant, Deny)
 * - "Block access to specific IP": (Abstain, Deny)
 */
enum AccessVote
{
    /**
      * Grant access.
      * The access grant can still be denied if any other policy vote is `Deny`.
      */
    case Grant;

    /**
      * Does not allow access, but does not deny it either.
      * Access grant will depend on other policies vote.
      */
    case Abstain;

    /**
      * Deny access.
      * The access grant will be refused, whatever the other policies vote is.
      */
    case Deny;
}
