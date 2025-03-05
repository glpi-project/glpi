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
use Glpi\Controller\Form\Utils\CanCheckAccessPolicies;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\Dropdown\FormActorsDropdown;
use Glpi\Form\Form;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuestionActorsDropdownController extends AbstractController
{
    use CanCheckAccessPolicies;

    #[Route(
        "/Form/Question/ActorsDropdown",
        name: "glpi_form_question_actors_dropdown_value",
        methods: "POST"
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        $form = $this->loadTargetForm($request);
        $this->checkFormAccessPolicies($form, $request);

        $options = [
            'allowed_types'    => $request->request->all('allowed_types'),
            'right_for_users'  => $request->request->getString('right_for_users', 'all'),
            'group_conditions' => $request->request->all('group_conditions'),
        ];

        return new JsonResponse(FormActorsDropdown::fetchValues('', $options));
    }

    private function loadTargetForm(Request $request): Form
    {
        $forms_id = (int) $request->request->getInt('form_id');
        if (!$forms_id) {
            throw new BadRequestHttpException();
        }

        $form = Form::getById($forms_id);
        if (!$form) {
            throw new NotFoundHttpException();
        }

        return $form;
    }
}
