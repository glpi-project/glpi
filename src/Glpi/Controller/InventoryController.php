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
use Glpi\Http\Firewall;
use Symfony\Component\HttpFoundation\Request;
use Glpi\Inventory\Conf;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InventoryController extends AbstractController
{
    public static bool $is_inventory = false;
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route("/Inventory", name: "glpi_inventory", methods: ['GET', 'POST'])]
    #[Route("/front/inventory.php", name: "glpi_inventory_legacy", methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $conf = new Conf();
        self::$is_inventory = true;

        if ($conf->enabled_inventory != 1) {
            throw new AccessDeniedHttpException("Inventory is disabled");
        }

        $inventory_request = new \Glpi\Inventory\Request();
        $inventory_request->handleHeaders();

        $refused = new \RefusedEquipment();

        $handle = true;
        $contents = '';
        if ($refused_id = $request->get('refused')) {
            \Session::checkRight("config", READ);
            if ($refused->getFromDB($refused_id) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
                $contents = file_get_contents($inventory_file);
            } else {
                trigger_error(
                    sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $refused_id),
                    E_USER_WARNING
                );
            }
        } else if (!$request->isMethod('POST')) {
            if ($request->get('action') === 'getConfig') {
                /**
                 * Even if Fusion protocol is not supported for getConfig requests, they
                 * should be handled and answered with a json content type
                 */
                $inventory_request->handleContentType('application/json');
                $inventory_request->addError('Protocol not supported', 400);
            } else {
                // Method not allowed answer without content
                $inventory_request->addError(null, 405);
            }
            $handle = false;
        } else {
            $contents = file_get_contents("php://input");
        }

        if ($handle) {
            try {
                $inventory_request->handleRequest($contents);
            } catch (\Throwable $e) {
                $inventory_request->addError($e->getMessage());
            }
        }

        $inventory_request->handleMessages();

        if ($refused_id) {
            $redirect_url = $refused->handleInventoryRequest($inventory_request);
            $response = new RedirectResponse($redirect_url);
        } else {
            $response = new Response();
            $response->setStatusCode($inventory_request->getHttpResponseCode());
            $headers = $inventory_request->getHeaders(true);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            $response->setContent($inventory_request->getResponse());
        }

        return $response;
    }
}
