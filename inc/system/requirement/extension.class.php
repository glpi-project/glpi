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

namespace Glpi\System\Requirement;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class Extension extends AbstractRequirement {

   /**
    * Required extension name.
    *
    * @var string
    */
   protected $name;

   /**
    * @param string $name    Required extension name.
    * @param bool $optional  Indicated if extension is optional.
    */
   public function __construct(string $name, bool $optional = false) {
      $this->title = sprintf(__('%s extension test'), $name);
      $this->name = $name;
      $this->optional = $optional;
   }

   protected function check() {
      $this->validated = extension_loaded($this->name);
      $this->buildValidationMessage();
   }

   /**
    * Defines the validation message based on self properties.
    *
    * @return void
    */
   protected function buildValidationMessage() {
      if ($this->validated) {
         $this->validation_messages[] = sprintf(__('%s extension is installed'), $this->name);
      } else if ($this->optional) {
         $this->validation_messages[] = sprintf(__('%s extension is not present'), $this->name);
      } else {
         $this->validation_messages[] = sprintf(__('%s extension is missing'), $this->name);
      }
   }
}
