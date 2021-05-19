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

use Session;
use Toolbox;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;
use User;

/**
 * @since 10.0.0
 */
class UserExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('User__getPicture', [$this, 'getPicture']),
         new TwigFunction('User__getBgColor', [$this, 'getBgColor']),
         new TwigFunction('User__getInitials', [$this, 'getInitials']),
         new TwigFunction('User__getLink', [$this, 'getLink'], ['is_safe' => ['html']]),
         new TwigFunction('User__getLinkUrl', [$this, 'getLinkUrl']),
         new TwigFunction('User__getUserName', [$this, 'getUserName']),
      ];
   }

   public function getPicture(int $users_id = 0): string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         if ($this->getAnonymizedName($users_id) === "") {
            return User::getThumbnailURLForPicture(null, false);
         }
         return User::getThumbnailURLForPicture($user->fields['picture'], false);
      }

      return "";
   }

   public function getInitials(int $users_id = 0): string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         return strtoupper(
            substr($user->fields['firstname'], 0, 1).
            substr($user->fields['realname'], 0, 1)
         );
      }

      return "";
   }

   public function getBgColor(int $users_id = 0): string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         return Toolbox::getColorForString($user->fields['firstname'].$user->fields['realname']);
      }

      return "";
   }

   public function getLink(int $users_id = null, array $options = []):string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         if (strlen($anon_name = $this->getAnonymizedName($users_id, $options)) > 0) {
            return $anon_name;
         }
         return $user->getLink($options);
      }

      return "";
   }

   public function getLinkUrl(int $users_id = null):string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         return $user->getLinkURL();
      }

      return "";
   }

   /**
    * Get name of the user with ID=$ID (optional with link to user.form.php)
    *
    * @param int $users_id ID of the user
    * @param int $link 1 = Show link to user.form.php 2 = return array with comments and link
    *                      (default =0)
    * @return string|string[] The username (realname if not empty and name if realname is empty) if link is not set to 2.
    *    If link is set to 2, an array containing the name, link and comment (Tooltip content) is returned.
    */
   public function getUserName(int $users_id = 0, int $link = 0) {
      if ($users_id === 0) {
         $users_id = Session::getLoginUserID();
      }

      if (strlen($anon_name = $this->getAnonymizedName($users_id)) > 0) {
         return $anon_name;
      }

      return getUserName($users_id, $link);
   }


   public function getAnonymizedName(int $users_id = null, array $options = []): ?string {
      $entities_id = Session::getActiveEntity();
      if (isset($options['timeline_item'])) {
         $entities_id = $options['timeline_item']->getEntityID();
      }

      return Session::getCurrentInterface() == 'helpdesk'
         ? User::getAnonymizedName($users_id, $entities_id)
         : "";
   }
}
