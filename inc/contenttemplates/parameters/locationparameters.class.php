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
use Location;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Parameters for "Location" items.
 *
 * @since 10.0.0
 */
class LocationParameters extends AbstractParameters
{
   public static function getDefaultNodeName(): string {
      return 'location';
   }

   public static function getObjectLabel(): string {
      return Location::getTypeName(1);
   }

   protected function getTargetClasses(): array {
      return [Location::class];
   }

   protected function defineParameters(): array {
      return [
         new AttributeParameter("id", __('ID')),
         new AttributeParameter("name", __('Name')),
         new AttributeParameter("completename", __('Complete name')),
      ];
   }

   protected function defineValues(CommonDBTM $location): array {

      // Output "unsanitized" values
      $fields = Toolbox::unclean_cross_side_scripting_deep($location->fields);

      return [
         'id'           => $fields['id'],
         'name'         => $fields['name'],
         'completename' => $fields['completename'],
      ];
   }
}
