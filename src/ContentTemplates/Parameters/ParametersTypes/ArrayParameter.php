<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\ContentTemplates\Parameters\ParametersTypes;

use Glpi\ContentTemplates\Parameters\TemplatesParametersInterface;

/**
 * ArrayParmameter represent a template parameter that contains multiple objets
 * of the same types.
 * For example the requesters of a tickets or the users in a group.
 *
 * @since 10.0.0
 */
class ArrayParameter extends AbstractParameterType
{
    /**
     * Parameters of each item contained in this array.
     *
     * @var TemplatesParametersInterface
     */
    protected $template_parameters;

    /**
     * @param string                       $key        Key to access this value
     * @param TemplatesParametersInterface $parameters Parameters of each item contained in this array
     * @param string                       $label      Label to display in the autocompletion widget
     */
    public function __construct(
        string $key,
        TemplatesParametersInterface $parameters,
        string $label
    ) {
        parent::__construct($key, $label);
        $this->template_parameters = $parameters;
    }

    public function compute(): array
    {
        $object_parameters = new ObjectParameter($this->template_parameters);
        return [
            'type'      => "ArrayParameter",
            'key'       => $this->key,
            'label'     => $this->label,
            'items_key' => $this->template_parameters->getDefaultNodeName(),
            'content'   => $object_parameters->compute(),
        ];
    }

    public function getDocumentationUsage(?string $parent = null): string
    {
        $parent = !empty($parent) ? "$parent." : "";
        return "{% for {$this->template_parameters->getDefaultNodeName()} in {$parent}{$this->key} %}";
    }

    public function getDocumentationReferences(): ?TemplatesParametersInterface
    {
        return $this->template_parameters;
    }
}
