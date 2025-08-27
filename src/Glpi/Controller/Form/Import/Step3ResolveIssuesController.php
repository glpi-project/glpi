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

namespace Glpi\Controller\Form\Import;

use Glpi\Controller\AbstractController;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class Step3ResolveIssuesController extends AbstractController
{
    #[Route("/Form/Import/ResolveIssues", name: "glpi_form_import_resolve_issues", methods: "POST")]
    public function __invoke(Request $request): Response
    {
        if (!Form::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        // Get json, form name and skipped_forms from the request.
        $json          = $request->request->get('json');
        $form_id       = $request->request->get('form_id');
        $skipped_forms = $request->request->all()["skipped_forms"] ?? [];

        $serializer = new FormSerializer();
        $mapper = new DatabaseMapper(Session::getActiveEntities());

        $replacements = $request->request->all()["replacements"] ?? [];
        foreach ($replacements as $replacements_data) {
            $mapper->addMappedItem(
                $replacements_data['itemtype'],
                $replacements_data['original_name'],
                (int) $replacements_data['replacement_id'],
            );
        }

        $issues = $serializer->listIssues($mapper, $json)->getIssues()[$form_id];
        return $this->render("pages/admin/form/import/step3_resolve_issues.html.twig", [
            'title'        => __("Resolve issues"),
            'menu'         => ['admin', Form::getType()],
            'issues'       => $issues,
            'json'         => $json,
            'replacements' => $replacements,
            'skipped_forms' => $skipped_forms,
        ]);
    }
}
