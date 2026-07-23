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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

abstract class AbstractReAuthStrategy implements ReAuthStrategyInterface
{
    /**
     * Verify url points to \Glpi\Controller\Security\ReAuthController::verify
     *
     * Providing another value allows prompt form validation to bypass the ReauthStrategy verify().
     * A use case is when verification is done by an external authentication service.
     * Before overriding it, ensure you really need it because it will bypass ReAuthStrategyInterface::verify().
     *
     * @see ReAuthStrategyInterface::getVerifyUrl()
     * @see ReAuthStrategyInterface::verify()
     */
    public function getVerifyUrl(): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . '/ReAuth/Verify';
    }

    /**
     * @see ReAuthStrategyInterface::getVerifyHttpMethod()
     */
    public function getVerifyHttpMethod(): string
    {
        return 'POST';
    }
}
