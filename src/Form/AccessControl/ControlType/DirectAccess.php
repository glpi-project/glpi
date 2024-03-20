<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Form\AccessControl\ControlType;

use FreeJsonConfigInterface;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Form;
use Override;
use SessionInfo;

final class DirectAccess implements ControlTypeInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Allow direct access");
    }

    #[Override]
    public function getIcon(): string
    {
        return "ti ti-link";
    }

    #[Override]
    public function getConfigClass(): string
    {
        return DirectAccessConfig::class;
    }

    #[Override]
    public function renderConfigForm(FreeJsonConfigInterface $config): string
    {
        if (!$config instanceof DirectAccessConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        // Build form URL with integrated token parameter
        $url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
        $url .= "/front/form/form_renderer.php?";
        $url .= http_build_query([
            'id'    => $_GET['id'],
            'token' => $config->token,
        ]);

        $twig = TemplateRenderer::getInstance();
        return $twig->render("pages/admin/form/access_control/direct_access.html.twig", [
            'config' => $config,
            'url'    => $url,
            'token'  => $config->token,
        ]);
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function createConfigFromUserInput(array $input): DirectAccessConfig
    {
        return new DirectAccessConfig([
            'token'                 => $input['_token'] ?? null,
            'allow_unauthenticated' => $input['_allow_unauthenticated'] ?? false,
            'force_direct_access'   => $input['_force_direct_access'] ?? false,
        ]);
    }

    #[Override]
    public function allowUnauthenticatedUsers(FreeJsonConfigInterface $config): bool
    {
        if (!$config instanceof DirectAccessConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        return $config->allow_unauthenticated;
    }

    #[Override]
    public function canAnswer(
        FreeJsonConfigInterface $config,
        SessionInfo $session
    ): bool {
        if (!$config instanceof DirectAccessConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        if (isset($_GET['token'])) {
            // A token was supplied, it must be correct
            return $config->token === $_GET['token'];
        } else {
            // No token supplied, check if token access is mandatory
            return !$config->force_direct_access;
        }
    }
}
