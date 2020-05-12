<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace Glpi\System;

use Glpi\System\Requirement\RequirementInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class RequirementsList implements \IteratorAggregate {

   /**
    * Requirements.
    *
    * @var RequirementInterface[]
    */
   private $requirements;

   /**
    * @param RequirementInterface[] $requirements
    */
   public function __construct(array $requirements = []) {
      $this->requirements = $requirements;
   }

   public function getIterator() {
      return new \ArrayIterator($this->requirements);
   }

   /**
    * Indicates if a mandatory requirement is missing.
    *
    * @return boolean
    */
   public function hasMissingMandatoryRequirements() {
      foreach ($this->requirements as $requirement) {
         if ($requirement->isMissing() && !$requirement->isOptional()) {
            return true;
         }
      }
      return false;
   }

   /**
    * Indicates if an optional requirement is missing.
    *
    * @return boolean
    */
   public function hasMissingOptionalRequirements() {
      foreach ($this->requirements as $requirement) {
         if ($requirement->isMissing() && $requirement->isOptional()) {
            return true;
         }
      }
      return false;
   }
}
