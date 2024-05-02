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

use Glpi\Form\AccessControl\FormAccessParameters;
use JsonConfigInterface;
use Glpi\Application\View\TemplateRenderer;
use Override;
use Glpi\Session\SessionInfo;

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
    public function renderConfigForm(JsonConfigInterface $config): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!$config instanceof DirectAccessConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        // Build form URL with integrated token parameter
        $url = $CFG_GLPI['url_base'];
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
        ]);
    }

    #[Override]
    public function canAnswer(
        JsonConfigInterface $config,
        FormAccessParameters $parameters
    ): bool {
        if (!$config instanceof DirectAccessConfig) {
            throw new \InvalidArgumentException("Invalid config class");
        }

        if (!$this->validateSession($config, $parameters)) {
            return false;
        }

        return $this->validateToken($config, $parameters);
    }

    private function validateSession(
        DirectAccessConfig $config,
        FormAccessParameters $parameters,
    ): bool {
        if (!$config->allow_unauthenticated && !$parameters->isAuthenticated()) {
            return false;
        }

        return true;
    }

    private function validateToken(
        DirectAccessConfig $config,
        FormAccessParameters $parameters,
    ): bool {
        $token = $parameters->getUrlParameters()['token'] ?? null;
        if ($token === null) {
            return false;
        }

        return $config->token === $token;
    }
}
