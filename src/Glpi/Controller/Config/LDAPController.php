<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller\Config;

use AuthLDAP;
use AuthLdapReplicate;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LDAPController extends AbstractController
{
    #[Route(
        "/AuthLDAP/{authldaps_id}/Replica/{authldapreplicates_id}/Test",
        name: "authldap_replica_status",
        requirements: [
            'authldaps_id' => '\d+',
            'authldapreplicates_id' => '\d+',
        ],
        methods: ['POST'],
    )]
    public function testReplica(Request $request): Response
    {
        if (!AuthLDAP::canUpdate()) {
            throw new AccessDeniedHttpException();
        }
        $authldap = new AuthLDAP();
        $replicate = new AuthLdapReplicate();
        if (!$authldap->getFromDB($request->request->getInt('authldaps_id')) || !$replicate->getFromDB($request->request->getInt('authldapreplicates_id'))) {
            throw new NotFoundHttpException();
        }

        if (AuthLDAP::testLDAPConnection($authldap->getID(), $replicate->getID())) {
            return new Response();
        } else {
            return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
