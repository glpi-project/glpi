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

use CommonGLPI;
use Profile_User;
use Session;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class SessionExtension extends AbstractExtension implements ExtensionInterface, GlobalsInterface {

   public function getFunctions() {
      return [
         new TwigFunction('has_global_right', [$this, 'hasGlobalRight']),
         new TwigFunction('has_item_right', [$this, 'hasItemRight']),
         new TwigFunction('user_pref', [$this, 'userPref']),
         new TwigFunction('user_pref', [$this, 'userPref']),
         new TwigFunction('haveAccessToEntity', [$this, 'haveAccessToEntity']),
         new TwigFunction('haveRecursiveAccessToEntity', [$this, 'haveRecursiveAccessToEntity']),
         new TwigFunction('canViewAllEntities', [$this, 'canViewAllEntities']),
         new TwigFunction('haveAccessToOneOfEntities', [$this, 'haveAccessToOneOfEntities']),
         new TwigFunction('userhaveAccessToOneOfEntities', [$this, 'userhaveAccessToOneOfEntities']),
      ];
   }

   public function getGlobals(): array {
      $user_name = Session::getLoginUserID()
         ? formatUserName(0, $_SESSION['glpiname'], $_SESSION['glpirealname'], $_SESSION['glpifirstname'])
         : '';

      return [
         'is_user_connected'        => Session::getLoginUserID() !== false,
         'is_debug_active'          => $_SESSION['glpi_use_mode'] ?? null === Session::DEBUG_MODE,
         'current_user_id'          => Session::getLoginUserID() || null,
         'current_user_name'        => $user_name,
         'use_simplified_interface' => Session::getCurrentInterface() === 'helpdesk',
      ];
   }

   /**
    * Check global right.
    *
    * @param string   $itemtype
    * @param int      $right
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function hasGlobalRight(string $itemtype, int $right): bool {
      if (!is_a($itemtype, CommonGLPI::class, true)) {
         throw new \Exception(sprintf('Unable to check rights of itemtype "%s".', $itemtype));
      }

      $item = new $itemtype();
      return $item->canGlobal($right);
   }

   /**
    * Check rights on item.
    *
    * @param string $itemtype
    * @param int    $right
    * @param int    $id
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function hasItemRight(string $itemtype, int $right, int $id = null): bool {
      if (!is_a($itemtype, CommonGLPI::class, true)) {
         throw new \Exception(sprintf('Unable to check rights of itemtype "%s".', $itemtype));
      }

      $item = new $itemtype();
      return $item->can($id, $right);
   }

   /**
    * Get user preference.
    *
    * @param string $name
    *
    * @return null|string
    *
    * @TODO Add a unit test.
    */
   public function userPref(string $name): ?string {
      global $CFG_GLPI;

      return $_SESSION[$name] ?? $CFG_GLPI[$name] ?? null;
   }

   /**
    * Check if we have access in session to a given entity
    *
    * @param int $entities_id
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function haveAccessToEntity(int $entities_id):bool {
      return Session::haveAccessToEntity($entities_id);
   }


   /**
    * Check if we have access in session to a given entity recursively
    *
    * @param int $entities_id
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function haveRecursiveAccessToEntity(int $entities_id):bool {
      return Session::haveRecursiveAccessToEntity($entities_id);
   }


   /**
    * Does user have right to see all entities?
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function canViewAllEntities():bool {
      return Session::canViewAllEntities();
   }

   /**
    * Check if current user have access to a given list of entities
    *
    * @param array $entities
    * @param int $is_recursive
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
   public function haveAccessToOneOfEntities(array $entities = [], int $is_recursive = 0):bool {
      return Session::haveAccessToOneOfEntities($entities, $is_recursive);
   }


   /**
    * Check if a given user have access to current entity list
    *
    * @param array $entities
    * @param int $is_recursive
    *
    * @return bool
    *
    * @TODO Add a unit test.
    */
    public function userhaveAccessToOneOfEntities(int $users_id = 0, int $is_recursive = 0):bool {
      return Session::haveAccessToOneOfEntities(Profile_User::getUserEntities($users_id), $is_recursive);
   }
}
