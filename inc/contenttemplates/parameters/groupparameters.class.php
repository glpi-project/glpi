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
use Group;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Parameters for "Group" items
 */
class GroupParameters extends AbstractParameters
{
   public static function getDefaultNodeName(): string {
      return 'group';
   }

   public static function getObjectLabel(): string {
      return Group::getTypeName(1);
   }

   protected function getTargetClasses(): array {
      return [Group::class];
   }

   public function defineParameters(): array {
      return [
         new AttributeParameter("id", __('ID')),
         new AttributeParameter("name", __('Name')),
      ];
   }

   protected function defineValues(CommonDBTM $group): array {

      // Output "unsanitized" values
      $fields = Toolbox::unclean_cross_side_scripting_deep($group->fields);

      return [
         'id'   => $fields['id'],
         'name' => $fields['name'],
      ];
   }
}
