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

use Plugin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class PluginExtension extends AbstractExtension {
   public function getFunctions(): array {
      return [
         new TwigFunction('call_plugin_hook', [$this, 'callPluginHook']),
         new TwigFunction('call_plugin_hook_func', [$this, 'callPluginHookFunction']),
      ];
   }

   /**
    * Call plugin hook with given params.
    *
    * @param string  $name          Hook name.
    * @param mixed   $params        Hook parameters.
    * @param bool    $return_result Indicates that the result should be returned.
    *
    * @return mixed|void
    */
   public function callPluginHook(string $name, $params = null, bool $return_result = false) {
      $result = Plugin::doHook($name, $params);

      if ($return_result) {
         return $result;
      }
   }

   /**
    * Call plugin hook function with given params.
    *
    * @param string  $name          Hook name.
    * @param mixed   $params        Hook parameters.
    * @param bool    $return_result Indicates that the result should be returned.
    *
    * @return mixed|void
    */
   public function callPluginHookFunction(string $name, $params = null, bool $return_result = false) {
      $result = Plugin::doHookFunction($name, $params);

      if ($return_result) {
         return $result;
      }
   }
}
