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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;

/**
 * Twig content templates parameters definition interface.
 *
 * @since 10.0.0
 */
interface TemplatesParametersInterface
{
    /**
     * Get default node name to use for this class.
     *
     * @return string
     */
    public static function getDefaultNodeName(): string;

    /**
     * Get object label to use for this class.
     *
     * @return string
     */
    public static function getObjectLabel(): string;

    /**
     * Get values for a given item, used for template rendering
     *
     * @param CommonDBTM $item
     *
     * @return array
     */
    public function getValues(CommonDBTM $item): array;

    /**
     * To be defined in each subclasses, define all available parameters for one or more itemtypes.
     * These parameters information are meant to be used for autocompletion on the client side.
     *
     * @return \Glpi\ContentTemplates\Parameters\ParametersTypes\ParameterTypeInterface[]
     */
    public function getAvailableParameters(): array;
}
