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

namespace Glpi\CalDAV\Plugin;

use CommonDBTM;
use Glpi\CalDAV\Backend\Principal;
use Glpi\CalDAV\Traits\CalDAVPrincipalsTrait;
use Glpi\CalDAV\Traits\CalDAVUriUtilTrait;
use PlanningExternalEvent;
use Sabre\CalDAV\Calendar;
use Sabre\CalDAV\CalendarObject;
use Sabre\DAVACL\IACL;
use Sabre\DAVACL\Plugin;
use Session;

/**
 * ACL plugin for CalDAV server.
 *
 * @since 9.5.0
 */
class Acl extends Plugin
{
    use CalDAVPrincipalsTrait;
    use CalDAVUriUtilTrait;

    public $principalCollectionSet = [
        Principal::PREFIX_GROUPS,
        Principal::PREFIX_USERS,
    ];

    public $allowUnauthenticatedAccess = false;

    public function getAcl($node)
    {
        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = parent::getAcl($node);

        if (
            !($node instanceof IACL) || ($owner_path = $node->getOwner()) === null
            || !$this->canViewPrincipalObjects($owner_path)
        ) {
            return $acl;
        }

        $acl[] = [
            'principal' => '{DAV:}authenticated',
            'privilege' => '{DAV:}read',
            'protected' => true,
        ];

        if ($node instanceof Calendar && Session::haveRight(PlanningExternalEvent::$rightname, UPDATE)) {
           // If user can update external events, then he is able to write on calendar to create new events.
            $acl[] = [
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}write',
                'protected' => true,
            ];
        } else if ($node instanceof CalendarObject) {
            $item = $this->getCalendarItemForPath($node->getName());
            if ($item instanceof CommonDBTM && $item->can($item->fields['id'], UPDATE)) {
                $acl[] = [
                    'principal' => '{DAV:}authenticated',
                    'privilege' => '{DAV:}write',
                    'protected' => true,
                ];
            }
        }

        return $acl;
    }
}
