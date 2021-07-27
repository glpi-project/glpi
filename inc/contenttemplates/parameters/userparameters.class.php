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

namespace Glpi\ContentTemplates\Parameters;

use CommonDBTM;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\Toolbox\Sanitizer;
use User;
use UserEmail;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Parameters for "User" items.
 *
 * @since 10.0.0
 */
class UserParameters extends AbstractParameters
{
   public static function getDefaultNodeName(): string {
      return 'user';
   }

   public static function getObjectLabel(): string {
      return User::getTypeName(1);
   }

   protected function getTargetClasses(): array {
      return [User::class];
   }

   public function defineParameters(): array {
      return [
         new AttributeParameter("id", __('ID')),
         new AttributeParameter("login", __('Login')),
         new AttributeParameter("fullname", __('Name')),
         new AttributeParameter("email", _n('Email', 'Emails', 1)),
         new AttributeParameter("phone", _n('Phone', 'Phones', 1)),
         new AttributeParameter("phone2", __('Phone 2')),
         new AttributeParameter("mobile", __('Mobile')),
      ];
   }

   protected function defineValues(CommonDBTM $user): array {

      // Output "unsanitized" values
      $fields = Sanitizer::unsanitize($user->fields);

      return [
         'id'    => $fields['id'],
         'login' => $fields['name'],
         'fullname'  => $user->getFriendlyName(),
         'email' => UserEmail::getDefaultForUser($fields['id']),
         'phone' => $fields['phone'],
         'phone2' => $fields['phone2'],
         'mobile' => $fields['mobile'],
      ];
   }
}
