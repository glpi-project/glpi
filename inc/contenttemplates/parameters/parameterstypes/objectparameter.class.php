<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\ContentTemplates\Parameters\AbstractParameters;
use Glpi\ContentTemplates\TemplateManager;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

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
    * @var AbstractParameters
    */
   protected $template_parameters;

   /**
    * @param AbstractParameters $template_parameters  Parameters to add
    * @param null|string $key                         Key to access this value
    */
   public function __construct(AbstractParameters $template_parameters, ?string $key = null) {
      $this->key = $key ?? $template_parameters->getDefaultNodeName();
      $this->template_parameters = $template_parameters;
   }

   public function compute(): array {
      $sub_parameters = $this->template_parameters->getAvailableParameters();
      $properties =  TemplateManager::computeParameters($sub_parameters);

      return [
         'type'       => "ObjectParameter",
         'key'        => $this->key,
         'label'      => $this->template_parameters->getObjectLabel(),
         'properties' => $properties,
      ];
   }

   public function getTemplateParameters(): AbstractParameters {
      return $this->template_parameters;
   }

   public function getDocumentationLabel(): string {
      return $this->template_parameters->getObjectLabel();
   }

   public function getDocumentationUsage(?string $parent = null): string {
      $parent = !empty($parent) ? "$parent." : "";
      return "{{ {$parent}{$this->key}.XXX }}";
   }

   public function getDocumentationReferences(): ?AbstractParameters {
      return $this->template_parameters;
   }
}
