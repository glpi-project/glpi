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

namespace Glpi\CalDAV\Node;

/**
 * Calendar node properties constants.
 *
 * @since 9.5.0
 */
class Property
{
    const CAL_COLOR                = '{http://apple.com/ns/ical/}calendar-color';
    const CAL_DESCRIPTION          = '{urn:ietf:params:xml:ns:caldav}calendar-description';
    const CAL_SUPPORTED_COMPONENTS = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
    const CAL_USER_TYPE            = '{urn:ietf:params:xml:ns:caldav}calendar-user-type';
    const DISPLAY_NAME             = '{DAV:}displayname';
    const PRIMARY_EMAIL            = '{http://sabredav.org/ns}email-address';
    const RESOURCE_TYPE            = '{DAV:}resourcetype';
    const USERNAME                 = '{http://glpi-project.org/ns}username';
}
