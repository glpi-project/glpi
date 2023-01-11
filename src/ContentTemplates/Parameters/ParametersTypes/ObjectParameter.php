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

namespace Glpi\ContentTemplates\Parameters\ParametersTypes;

use Glpi\ContentTemplates\Parameters\TemplatesParametersInterface;
use Glpi\ContentTemplates\TemplateManager;

/**
 * ObjectParameter represent a whole object to use as a parameter.
 * For exemple, this entity of a ticket or its category.
 *
 * @since 10.0.0
 */
class ObjectParameter extends AbstractParameterType
{
    /**
     * Parameters availables in the item that will be linked.
     *
     * @var TemplatesParametersInterface
     */
    protected $template_parameters;

    /**
     * @param TemplatesParametersInterface $template_parameters Parameters to add
     * @param null|string                  $key                 Key to access this value
     */
    public function __construct(TemplatesParametersInterface $template_parameters, ?string $key = null)
    {
        parent::__construct(
            $key ?? $template_parameters->getDefaultNodeName(),
            $template_parameters->getObjectLabel()
        );
        $this->template_parameters = $template_parameters;
    }

    public function compute(): array
    {
        $sub_parameters = $this->template_parameters->getAvailableParameters();
        $properties =  TemplateManager::computeParameters($sub_parameters);

        return [
            'type'       => "ObjectParameter",
            'key'        => $this->key,
            'label'      => $this->label,
            'properties' => $properties,
        ];
    }

    public function getDocumentationUsage(?string $parent = null): string
    {
        $parent = !empty($parent) ? "$parent." : "";
        return "{{ {$parent}{$this->key}.XXX }}";
    }

    public function getDocumentationReferences(): ?TemplatesParametersInterface
    {
        return $this->template_parameters;
    }
}
