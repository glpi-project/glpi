<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Controller;

use Slim\Http\Request;
use Slim\Http\Response;
use Toolbox;

class Itil extends AbstractController implements ControllerInterface
{
    const TYPES = 'Problem|Change|Ticket';

   /**
    * path: '/user/itemtype'
    *
    * @param Request  $request  Request
    * @param Response $response Response
    * @param array    $args     URL arguments
    *
    * @return void
    *
     * @Glpi\Annotation\Route(name="user_itilobjects_active", pattern="/user/{itemtype:Problem|Change|Ticket}/{id:\d+}")
    */
    public function userObject(Request $request, Response $response, array $args)
    {
        $object = new $args['itemtype'];

        $options = [
         'criteria'  => [
            [ //users_id_assign
               'field'        => 5,
               'searchtype'   => 'equals',
               'value'        => (int)$args['id'],
               'link'         => 'AND'
            ], [ //status
               'field'        => 12,
               'searchtype'   => 'equals',
               'value'        => 'notold',
               'link'         => 'AND'
            ]
         ],
         'reset'     => 'reset'
        ];
        $url = $object->getSearchURL()."?".Toolbox::append_params($options, '&amp;');

        return $response->withJson([
         'count'        => $object->countActiveObjectsForTech((int)$args['id']),
         'search_url'   => $url
        ]);
    }
}
