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

namespace Glpi\Controller\Form;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExportController extends AbstractController
{
    #[Route("/Form/Export", name: "glpi_form_export")]
    public function __invoke(Request $request): Response
    {
        // Right check
        if (!Form::canView()) {
            throw new AccessDeniedHttpException();
        }

        // Read parameters
        $ids = $request->query->all()["ids"] ?? [];

        // Execute export
        $serializer = new FormSerializer();
        $forms = Form::getByIds($ids);
        $export = $serializer->exportFormsToJson($forms);

        // Output file
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $export->getFileName(),
        );
        $response = new Response($export->getJsonContent());
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
