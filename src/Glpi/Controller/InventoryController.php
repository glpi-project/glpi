<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Http\RedirectResponse;
use Glpi\Inventory\Conf;
use RefusedEquipment;
use Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use function Safe\file_get_contents;

final class InventoryController extends AbstractController
{
    public static bool $is_running = false;

    public function __construct(private readonly UrlGeneratorInterface $router)
    {
        //empty constructor
    }

    #[Route("/Inventory", name: "glpi_inventory", methods: ['GET', 'POST'])]
    #[Route("/front/inventory.php", name: "glpi_inventory_legacy", methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $conf = new Conf();
        if ($conf->enabled_inventory != 1) {
            throw new AccessDeniedHttpException("Inventory is disabled");
        }

        $inventory_request = new \Glpi\Inventory\Request();
        $inventory_request->handleHeaders();

        self::$is_running = true;

        try {
            $handle = true;
            $contents = '';
            if (!$request->isMethod('POST')) {
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
                $inventory_request->handleRequest($contents);
            }
        } catch (Throwable $e) {
            //empty
            $inventory_request->addError($e->getMessage());
        } finally {
            self::$is_running = false;
        }

        $inventory_request->handleMessages();

        $response = new Response();
        $response->setStatusCode($inventory_request->getHttpResponseCode());
        $headers = $inventory_request->getHeaders(true);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
        $response->setContent($inventory_request->getResponse());
        return $response;
    }

    #[Route("/Inventory/RefusedEquipment", name: "glpi_refused_inventory", methods: 'POST')]
    public function refusedEquipement(Request $request): Response
    {
        $conf = new Conf();
        if ($conf->enabled_inventory != 1) {
            throw new AccessDeniedHttpException("Inventory is disabled");
        }

        $inventory_request = new \Glpi\Inventory\Request();
        $refused_id = (int) $request->get('id');

        $refused = new RefusedEquipment();

        try {
            Session::checkRight("config", READ);
            if ($refused->getFromDB($refused_id) && ($inventory_file = $refused->getInventoryFileName()) !== null) {
                $contents = file_get_contents($inventory_file);
            } else {
                throw new HttpException(
                    404,
                    sprintf('Invalid RefusedEquipment "%s" or inventory file missing', $refused_id)
                );
            }
            $inventory_request->handleRequest($contents);
        } catch (Throwable $e) {
            //empty
            $inventory_request->addError($e->getMessage());
        }

        $redirect_url = $refused->handleInventoryRequest($inventory_request);
        $response = new RedirectResponse($redirect_url);
        return $response;
    }

    #[Route("/Inventory/Configuration", name: "glpi_inventory_configuration", methods: ['GET'])]
    #[Route("/front/inventory.conf.php", name: "glpi_inventory_configuration_legacy", methods: ['GET'])]
    public function configure(Request $request): Response
    {
        Session::checkRight(Conf::$rightname, Conf::UPDATECONFIG);
        return $this->render('pages/admin/inventory/conf/index.html.twig', [
            'conf' => new Conf(),
        ]);
    }

    #[Route("/Inventory/Configuration/Store", name: "glpi_inventory_store_configuration", methods: ['POST'])]
    #[Route("/front/inventory.conf.php", name: "glpi_inventory_store_configuration_legacy", methods: ['POST'])]
    public function storeConfiguration(Request $request): Response
    {
        Session::checkRight(Conf::$rightname, Conf::UPDATECONFIG);
        $conf = new Conf();
        $post_data = $request->request->all();

        if (isset($post_data['update'])) {
            unset($post_data['update']);
            if ($conf->saveConf($post_data)) {
                Session::addMessageAfterRedirect(
                    __s('Configuration has been updated'),
                    false,
                    INFO
                );
            }
        }
        return new RedirectResponse($this->router->generate('glpi_inventory_configuration'));
    }

    #[Route("/Inventory/ImportFiles", name: "glpi_inventory_report", methods: ['POST'])]
    public function report(Request $request): Response
    {
        Session::checkRight(Conf::$rightname, Conf::IMPORTFROMFILE);
        $conf = new Conf();

        $to_import = [];
        foreach ($request->files->get('inventory_files') as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $to_import[$file->getClientOriginalName()] = $file->getPathname();
            }
        }

        $imported_files = $conf->importFiles($to_import);

        return $this->render(
            'pages/admin/inventory/upload_result.html.twig',
            [
                'imported_files' => $imported_files,
            ]
        );
    }
}
