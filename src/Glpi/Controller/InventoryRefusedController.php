<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Controller;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\HttpException;
use Glpi\Http\Firewall;
use Symfony\Component\HttpFoundation\Request;
use Glpi\Inventory\Conf;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InventoryRefusedController extends AbstractController
{
    #[Route("/RefusedEquipment/Inventory/{refused_id}", requirements: ['refused_id' => '\d+'], name: "glpi_refused_inventory", methods: 'POST')]
    public function __invoke(Request $request): Response
    {
        $conf = new Conf();
        if ($conf->enabled_inventory != 1) {
            throw new AccessDeniedHttpException("Inventory is disabled");
        }

        $inventory_request = new \Glpi\Inventory\Request();
        $refused_id = (int)$request->get('refused_id');

        $refused = new \RefusedEquipment();

        try {
            \Session::checkRight("config", READ);
            if ($refused->getFromDB($refused_id) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
                $contents = file_get_contents($inventory_file);
            } else {
                throw new HttpException(
                    404,
                    sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $refused_id)
                );
            }
            $inventory_request->handleRequest($contents);
        } catch (\Throwable $e) {
            //empty
            $inventory_request->addError($e->getMessage());
        }

        $redirect_url = $refused->handleInventoryRequest($inventory_request);
        $response = new RedirectResponse($redirect_url);
        return $response;
    }
}
