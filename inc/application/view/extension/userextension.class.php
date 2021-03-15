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

use DbUtils;
use Toolbox;
use User;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
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
         return User::getThumbnailURLForPicture($user->fields['picture'], false);
      }

      return "";
   }

   public function getInitials(int $users_id = 0): string {
      $user = new User;
      if ($user->getFromDB($users_id)) {
         return strtoupper(
            substr($user->fields['firstname'],0, 1).
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

   public function getUserName(int $users_id = 0, int $link = 0):string {
      $dbu = new DbUtils();
      return $dbu->getUserName($users_id, $link);
   }
}
