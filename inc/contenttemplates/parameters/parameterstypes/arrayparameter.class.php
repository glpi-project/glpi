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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

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
    * This parameter will need to be handled in a loop, this key will be the
    * suggested variable name when iterating on its children.
    *
    * @var string
    */
   protected $items_key;

   /**
    * Parameters of each item contained in this array.
    *
    * @var ObjectParameter
    */
   protected $content;

   /**
    * The parameter label, to be displayed in the client side autocompletion
    *
    * @var string
    */
    protected $label;

   /**
    * @param string              $key        Key to access this value
    * @param AbstractParameters  $parameters Parameters of each item contained in this array
    * @param string              $label      Label to display in the autocompletion widget
    */
   public function __construct(
      string $key,
      AbstractParameters $parameters,
      string $label
   ) {
      $this->key = $key;
      $this->label = $label;
      $this->items_key = $parameters->getDefaultNodeName();
      $this->content = new ObjectParameter($parameters);
   }

   public function compute(): array {
      return [
         'type'      => "ArrayParameter",
         'key'       => $this->key,
         'label'     => $this->label,
         'items_key' => $this->items_key,
         'content'   => $this->content->compute(),
      ];
   }

   public function getDocumentationLabel(): string {
      return $this->label;
   }

   public function getDocumentationUsage(?string $parent = null): string {
      $parent = !empty($parent) ? "$parent." : "";
      return "{% for {$this->items_key} in {$parent}{$this->key} %}";
   }

   public function getDocumentationReferences(): ?AbstractParameters {
      return $this->content->getTemplateParameters();
   }
}
