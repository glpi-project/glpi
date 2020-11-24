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

namespace Glpi\Application\View\Extension;

use Config;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class ConfigExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('config', [$this, 'config']),
         new TwigFunction('php_config', [$this, 'phpConfig']),
         new TwigFunction('displayPasswordSecurityChecks', [$this, 'displayPasswordSecurityChecks']),
      ];
   }

   /**
    * Get GLPI configuration value.
    *
    * @param string $name
    *
    * @return mixed
    *
    * @TODO Add a unit test.
    */
   public function config(string $name) {
      global $CFG_GLPI;

      return $CFG_GLPI[$name] ?? null;
   }

   /**
    * Get PHP configuration value.
    *
    * @param string $name
    *
    * @return mixed
    *
    * @TODO Add a unit test.
    */
   public function phpConfig(string $name) {
      return ini_get($name);
   }


   /**
    * Display security checks on password
    *
    * @param $field string id of the field containing password to check (default 'password')
    *
    * @return void
    */
   public function displayPasswordSecurityChecks($field = 'password') {
      return Config::displayPasswordSecurityChecks($field);
   }
}
