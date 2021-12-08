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
class ExtensionClass extends Extension {

   /**
    * Required class or interface name.
    *
    * @var string
    */
   private $class_name;

   /**
    * @param string $name        Extension name.
    * @param string $class_name  Required class or interface name.
    * @param bool $optional      Indicated if extension is optional.
    */
   public function __construct(string $name, string $class_name, bool $optional = false) {
      parent::__construct($name, $optional);
      $this->class_name = $class_name;
   }

   protected function check() {
      $this->validated = class_exists($this->class_name) || interface_exists($this->class_name);
      $this->buildValidationMessage();
   }

}
