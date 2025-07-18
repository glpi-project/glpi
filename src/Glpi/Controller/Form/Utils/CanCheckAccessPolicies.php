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

namespace Glpi\Controller\Form\Utils;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Session;
use Symfony\Component\HttpFoundation\Request;

trait CanCheckAccessPolicies
{
    protected function checkFormAccessPolicies(Form $form, Request $request): void
    {
        $form_access_manager = FormAccessControlManager::getInstance();

        if (Session::haveRight(Form::$rightname, READ)) {
            // Form administrators can bypass restrictions while previewing forms.
            $parameters = new FormAccessParameters(bypass_restriction: true);
        } else {
            $url_parameters = $request->query->all();

            // Load current user session info and URL parameters.
            $parameters = new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo(),
                url_parameters: $url_parameters,
            );
        }

        // Must be authenticated here
        if (!$form_access_manager->canAnswerForm($form, $parameters)) {
            throw new AccessDeniedHttpException();
        }
    }
}
