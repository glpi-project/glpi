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
 * Define the base interface for parameters types.
 *
 * @since 10.0.0
 */
abstract class AbstractParameterType
{
   /**
    * The parameter key that need to be used to retrieve its value in a template
    *
    * @var string
    */
   protected $key;

   /**
    * To be defined in each subclasses, convert the parameter data into an array
    * that can be shared to the client side code as json and used for autocompletion.
    *
    * @return array
    */
   abstract public function compute(): array;

   /**
    * Label to use for this parameter's documentation
    *
    * @return string
    */
   abstract public function getDocumentationLabel(): string;

   /**
    * Recommended usage (twig code) to use for this parameter's documentation
    *
    * @param string|null $parent
    *
    * @return string
    */
   abstract public function getDocumentationUsage(?string $parent = null): string;

   /**
    * Reference to others parameters for this parameter's documentation
    *
    * @return AbstractParameters|null
    */
   abstract public function getDocumentationReferences(): ?AbstractParameters;

   /**
    * Field name for this parameter's documentation
    *
    * @return string
    */
   public function getDocumentationField(): string {
      return $this->key;
   }
}
