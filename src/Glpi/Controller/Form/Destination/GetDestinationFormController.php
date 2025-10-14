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

namespace Glpi\Controller\Form\Destination;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Destination\FormDestination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetDestinationFormController extends AbstractController
{
    #[Route("/Form/{form_id}/Destinations/{destination_id}", name: "glpi_form_destination_get_form", methods: "GET")]
    public function __invoke(Request $request, int $form_id, int $destination_id): Response
    {
        $destination = new FormDestination();
        $loaded = $destination->getFromDB($destination_id);
        if (!$loaded) {
            throw new BadRequestHttpException();
        }

        // Right check
        if (!$destination->can($destination_id, READ)) {
            throw new AccessDeniedHttpException();
        }

        $twig_params = [
            'destination' => $destination,
            'form' => $destination->getForm(),
            'can_update' => FormDestination::canUpdate(),
            'concrete_destination' => $destination->getConcreteDestinationItem(),
        ];

        // language=Twig
        $twig = TemplateRenderer::getInstance()->renderFromStringTemplate(
            <<<TWIG
            <form id="form-destination-{{ destination.getID() }}">
                <div class="overflow-x-hidden px-4">
                    {{ concrete_destination.renderConfigForm(
                        form,
                        destination,
                        destination.getConfig(),
                    )|raw }}
                    {% if concrete_destination.useDefaultConfigLayout() and can_update %}
                        <div class="mt-3 mb-3">
                            {{ include('pages/admin/form/form_destination_actions.html.twig', {
                                form: form,
                                destination: destination
                            }, with_context = false) }}
                        </div>
                    {% endif %}
                </div>

                {# Hidden values #}
                <input type="hidden" name="id" value="{{ destination.getID() }}"/>
                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}"/>
            </form>
        TWIG,
            $twig_params
        );

        return new Response($twig);
    }
}
