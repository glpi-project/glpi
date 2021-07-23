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
use Entity;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Parameters for "Assets" items (Computer, Monitor, ...).
 *
 * @since 10.0.0
 */
class AssetParameters extends AbstractParameters
{
   public static function getDefaultNodeName(): string {
      return 'asset';
   }

   public static function getObjectLabel(): string {
      return __('Asset');
   }

   protected function getTargetClasses(): array {
      global $CFG_GLPI;
      return $CFG_GLPI["asset_types"];
   }

   protected function defineParameters(): array {
      return [
         new AttributeParameter("id", __('ID')),
         new AttributeParameter("name", __('Name')),
         new AttributeParameter("itemtype", __('Itemtype')),
         new AttributeParameter("serial", __('Serial number')),
         new ObjectParameter(new EntityParameters()),
      ];
   }

   protected function defineValues(CommonDBTM $asset): array {

      // Output "unsanitized" values
      $fields = Sanitizer::unsanitize($asset->fields);

      $values = [
         'id'       => $fields['id'],
         'name'     => $fields['name'],
         'itemtype' => $asset->getType(),
         'serial'   => $fields['serial'],
      ];

      // Add asset's entity
      if ($entity = Entity::getById($fields['entities_id'])) {
         $entity_parameters = new EntityParameters();
         $values['entity'] = $entity_parameters->getValues($entity);
      }

      return $values;
   }
}
