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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use CommonGLPI;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class ItemtypeExtension extends AbstractExtension implements ExtensionInterface {

   public function getFilters() {
      return [
         new TwigFilter('itemtype_name', [$this, 'itemtypeName']),
      ];
   }

   public function getFunctions() {
      return [
         new TwigFunction('itemInstanceOf', [$this, 'itemInstanceOf']),
         new TwigFunction('maybeRecursive', [$this, 'maybeRecursive']),
      ];
   }

   /**
    * Return domain-relative path of a resource.
    *
    * @param string|CommonGLPI $itemtype
    * @param number $count
    *
    * @return string
    *
    * @TODO Add a unit test.
    */
   public function itemtypeName($itemtype, $count = 0): ?string
   {
      if ($itemtype instanceof CommonGLPI || is_a($itemtype, CommonGLPI::class, true)) {
         return $itemtype::getTypeName($count);
      }
   }


   /**
    * chech an givent item is an instance of given class
    *
    * @param mixed $item
    * @param string $class
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function itemInstanceOf($item, string $class = ""): ?bool
   {
      return ($item instanceof $class);
   }


   /**
    * Check given item can be entity recursive
    *
    * @param CommonDBTM $item
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function maybeRecursive(CommonDBTM $item): ?bool
   {
      return $item->maybeRecursive();
   }
}
