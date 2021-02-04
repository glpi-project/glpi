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

use Agent;
use CommonDBTM;
use CommonGLPI;
use Computer;
use Computer_Item;
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
         new TwigFilter('get_foreignkey_field', [$this, 'getForeignKeyField']),
         new TwigFilter('dropdown', [$this, 'dropdown'], ['is_safe' => ['html']]),
      ];
   }

   public function getFunctions() {
      return [
         new TwigFunction('itemInstanceOf', [$this, 'itemInstanceOf']),
         new TwigFunction('maybeRecursive', [$this, 'maybeRecursive']),
         new TwigFunction('getAgentForItem', [$this, 'getAgentForItem']),
         new TwigFunction('getInventoryFileName', [$this, 'getInventoryFileName']),
         new TwigFunction('getDcBreadcrumb', [$this, 'getDcBreadcrumb'], ['is_safe' => ['html']]),
         new TwigFunction('getAutofillMark', [$this, 'getAutofillMark'], ['is_safe' => ['html']]),
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
   public function itemtypeName($itemtype, $count = 0): ?string {
      if ($itemtype instanceof CommonGLPI || is_a($itemtype, CommonGLPI::class, true)) {
         return $itemtype::getTypeName($count);
      }
   }


   public function getForeignKeyField($itemtype): ?string {
      if ($itemtype instanceof CommonDBTM || is_a($itemtype, CommonDBTM::class, true)) {
         return $itemtype::getForeignKeyField();
      }

      return "";
   }

   public function dropdown($itemtype, array $options = []): bool {
      if ($itemtype instanceof CommonDBTM || is_a($itemtype, CommonDBTM::class, true)) {
         $itemtype::dropdown($options);
      }

      return false;
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
   public function itemInstanceOf($item, string $class = ""): ?bool {
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
   public function maybeRecursive(CommonDBTM $item): ?bool {
      return $item->maybeRecursive();
   }

   /**
    * Retrieve agent for a given item
    *
    * @param CommonDBTM $item
    *
    * @return bool|Agent
    *
    * @TODO Add a unit test.
    */
   public function getAgentForItem(CommonDBTM $item) {
      $agent = new Agent();
      $has_agent = $agent->getFromDBByCrit([
         'itemtype' => $item->getType(),
         'items_id' => $item->fields['id']
      ]);

      if (!$has_agent && $item instanceof Computer) {
         $citem = new Computer_Item;
         $has_relation = $citem->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->fields['id']
         ]);
         if ($has_relation) {
            $has_agent = $agent->getFromDBByCrit([
               'itemtype' => Computer::getType(),
               'items_id' => $citem->fields['computers_id']
            ]);
         }
      }

      return $has_agent
         ? $agent
         : false;
   }

   /**
    * Retrieve agent for a given item
    *
    * @param CommonDBTM $item
    *
    * @return bool|Agent
    *
    * @TODO Add a unit test.
    */
   public function getInventoryFileName(CommonDBTM $item):?string {
      if (method_exists($item, "getInventoryFileName")) {
         return $item->getInventoryFileName();
      }

      return "";
   }


   /**
    * Retrieve Datacenter breadcrumbs for a given item
    *
    * @param CommonDBTM $item
    *
    * @return string
    */
   public function getDcBreadcrumb(CommonDBTM $item):?array {
      if (method_exists($item, "getDcBreadcrumb")) {
         return $item->getDcBreadcrumb();
      }

      return [];
   }


   public function getAutofillMark(CommonDBTM $item, string $field, array $options, string $value = null):string {
      return $item->getAutofillMark($field, $options, $value);
   }
}
