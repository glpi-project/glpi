<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\UI\Component\Timeline;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\Sanitizer;
use Glpi\UI\Component\ComponentInterface;
use User;

/**
 * UI component used to render a user info card.
 */
class UserInfoCard implements ComponentInterface
{
    public function render(array $params): string
    {
        // Read and validate parameters
        $user = $this->getUserParameter($params);
        $enable_anonymization = $this->getEnableAnonymizationParameter($params);
        $can_edit = $this->getCanEditParameter($params);

        // Compute additional parameters
        $friendlyname = Sanitizer::getVerbatimValue($user->getFriendlyName());
        $email = $user->getDefaultEmail();

        // Render template
        $twig = TemplateRenderer::getInstance();
        return $twig->render('components/user/info_card.html.twig', [
            'user'                 => $user,
            'enable_anonymization' => $enable_anonymization,
            'can_edit'             => $can_edit,
            'friendlyname'         => $friendlyname,
            'email'                => $email,
        ]);
    }

    /**
     * Read and validate the mandatory 'user' parameter.
     *
     * @param array $params
     *
     * @return User
     */
    protected function getUserParameter(array $params): User
    {
        return $params['user'];
    }

    /**
     * Read and validate the optional 'enable_anonymization' parameter.
     *
     * Fallback to `false` if missing.
     *
     * @param array $params
     *
     * @return bool
     */
    protected function getEnableAnonymizationParameter(array $params): bool
    {
        return $params['enable_anonymization'] ?? false;
    }

    /**
     * Read and validate the optional 'can_edit' parameter.
     *
     * Fallback to `false` if missing.
     *
     * @param array $params
     *
     * @return bool
     */
    protected function getCanEditParameter(array $params): bool
    {
        return $params['can_edit'] ?? false;
    }
}
