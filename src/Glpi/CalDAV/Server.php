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

namespace Glpi\CalDAV;

use Glpi\Application\ErrorHandler;
use Glpi\CalDAV\Backend\Auth;
use Glpi\CalDAV\Backend\Calendar;
use Glpi\CalDAV\Backend\Principal;
use Glpi\CalDAV\Node\CalendarRoot;
use Glpi\CalDAV\Plugin\Acl;
use Glpi\CalDAV\Plugin\Browser;
use Glpi\CalDAV\Plugin\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

class Server extends DAV\Server
{
    public function __construct()
    {
        $this->on('exception', [$this, 'logException']);

       // Backends
        $authBackend = new Auth();
        $principalBackend = new Principal();
        $calendarBackend = new Calendar();

       // Directory tree
        $tree = [
            new DAV\SimpleCollection(
                Principal::PRINCIPALS_ROOT,
                [
                    new DAVACL\PrincipalCollection($principalBackend, Principal::PREFIX_GROUPS),
                    new DAVACL\PrincipalCollection($principalBackend, Principal::PREFIX_USERS),
                ]
            ),
            new DAV\SimpleCollection(
                Calendar::CALENDAR_ROOT,
                [
                    new CalendarRoot($principalBackend, $calendarBackend, Principal::PREFIX_GROUPS),
                    new CalendarRoot($principalBackend, $calendarBackend, Principal::PREFIX_USERS),
                ]
            ),
        ];

        parent::__construct($tree);

        $this->addPlugin(new DAV\Auth\Plugin($authBackend));
        $this->addPlugin(new Acl());
        $this->addPlugin(new CalDAV());

       // Support for html frontend (only in debug mode)
        $this->addPlugin(new Browser(false));
    }

    /**
     *
     * @param \Throwable $exception
     */
    public function logException(\Throwable $exception)
    {
        if ($exception instanceof \Sabre\DAV\Exception && $exception->getHTTPCode() < 500) {
           // Ignore server exceptions that does not corresponds to a server error
            return;
        }

        ErrorHandler::getInstance()->handleException($exception, true);
    }
}
