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

namespace Glpi\Controller\Form\ConditionalVisibilityEditor;

use Glpi\Controller\AbstractController;
use Glpi\Form\ConditionalVisiblity\EditorManager;
use Glpi\Form\ConditionalVisiblity\FormData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    public function __construct(
        private EditorManager $editor_manager,
    ) {
    }

    #[Route(
        // Need '/ajax' prefix due to legacy CSRF constraints.
        "/ajax/Form/ConditionalVisibilityEditor",
        name: "glpi_form_conditional_visibility_editor",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        $form_data = $request->request->all()['form_data'];
        $this->editor_manager->setFormData(new FormData($form_data));

        return $this->render('pages/admin/form/conditional_visibility_editor.html.twig', [
            'manager'            => $this->editor_manager,
            'defined_conditions' => $this->editor_manager->getDefinedConditions(),
            'items_values'       => $this->editor_manager->getItemsDropdownValues(),
        ]);
    }
}
