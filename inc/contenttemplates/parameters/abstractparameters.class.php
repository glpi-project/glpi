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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Handle user defined twig templates :
 *  - followup templates
 *  - tasks templates
 *  - solutions templates
 */
abstract class AbstractParameters implements TemplatesParametersInterface
{
   /**
    * To be defined in each subclasses, define all available parameters for one or
    * more itemtypes.
    * These parameters informations are meant to be used for autocompletion on
    * the client side
    *
    * @return \Glpi\ContentTemplates\Parameters\ParametersTypes\AbstractParameterType[]
    */
   abstract protected function defineParameters(): array;

   /**
    * To by defined in each subclasses, get the exposed values for a given item
    * This values will be used as parameters when rendering a twig template.
    *
    * @param CommonDBTM $item
    *
    * @return array
    */
   abstract protected function defineValues(CommonDBTM $item): array;

   /**
    * Get supported classes by this parameter type
    *
    * @return array
    */
   abstract protected function getTargetClasses(): array;

   /**
    * "Wrapper" function for defineValues()
    *  Validate the class of the given item before calling defineValues()
    *
    * @param CommonDBTM $item
    *
    * @return array
    */
   public function getValues(CommonDBTM $item): array {
      $valid_class = false;
      foreach ($this->getTargetClasses() as $class) {
         if ($item instanceof $class) {
            $valid_class = true;
            break;
         }
      }

      if (!$valid_class) {
         trigger_error(get_class($item) . " is not allowed for this parameter type");
         return [];
      }

      return $this->defineValues($item);
   }

   /**
    * "Wrapper" function for defineParameters()
    * Get the parameters from defineParameters() and compute them
    *
    * @return array
    */
   public function getAvailableParameters(): array {
      $parameters = [];

      foreach ($this->defineParameters() as $parameter) {
         $parameters[] = $parameter->compute();
      }

      return $parameters;
   }
}
