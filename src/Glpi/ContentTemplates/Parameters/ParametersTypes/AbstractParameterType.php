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

namespace Glpi\ContentTemplates\Parameters\ParametersTypes;

/**
 * Define the base interface for parameters types.
 *
 * @since 10.0.0
 */
abstract class AbstractParameterType implements ParameterTypeInterface
{
    /**
     * The parameter key that need to be used to retrieve its value in a template.
     *
     * @var string
     */
    protected $key;

    /**
     * The parameter label, to be displayed in the client side autocompletion.
     *
     * @var string
     */
    protected $label;

    /**
     * @param string  $key     Key to access this value
     * @param string  $label   Label to display in the autocompletion widget
     */
    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public function getDocumentationField(): string
    {
        return $this->key;
    }

    public function getDocumentationLabel(): string
    {
        return $this->label;
    }
}
