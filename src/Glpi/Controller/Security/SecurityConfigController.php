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

namespace Glpi\Controller\Security;

use Config;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\SecurityConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityConfigController extends AbstractController
{
    #[Route(
        path: "/front/security/securityconfig.form.php",
        name: "security_config_form",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    public function showForm(Request $request): Response
    {
        if (!SecurityConfig::canView()) {
            throw new AccessDeniedHttpException();
        }

        $config_id = Config::getConfigIDForContext('core');
        SecurityConfig::displayFullPageForItem($config_id, ["config", SecurityConfig::class], [
            'formoptions'  => "data-track-changes=true",
        ]);
        return new Response();
    }

    #[Route(
        path: "/front/security/securityconfig.form.php",
        name: "update_security_config_form",
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_ADMIN_ACCESS)]
    public function handleFormSubmission(Request $request): Response
    {
        $do_update = $request->request->getBoolean('update');
        if (!$do_update) {
            throw new BadRequestHttpException();
        }

        $config_id = Config::getConfigIDForContext('core');
        $config = new Config();

        $update_input = $request->request->all();
        $update_input['id'] = $config_id;
        $config->update($update_input);
        return new RedirectResponse(SecurityConfig::getFormURLWithID($config_id));
    }
}
