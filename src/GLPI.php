<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/**
 * @FIXME Find a better place for these constants.
 */
class GLPI
{
    /**
     * Production environment.
     */
    public const ENV_PRODUCTION  = 'production';

    /**
     * Staging environment.
     * Suitable for pre-production servers and customer acceptance tests.
     */
    public const ENV_STAGING     = 'staging';

    /**
     * Testing environment.
     * Suitable for CI runners, quality control and internal acceptance tests.
     */
    public const ENV_TESTING     = 'testing';

    /**
     * Development environment.
     * Suitable for developer machines and development servers.
     */
    public const ENV_DEVELOPMENT = 'development';
}
