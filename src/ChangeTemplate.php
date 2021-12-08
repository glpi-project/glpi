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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Chang Template class
 *
 * since version 9.5.0
**/
class ChangeTemplate extends ITILTemplate {
   use Glpi\Features\Clonable;

   public $second_level_menu         = "change";
   public $third_level_menu          = "ChangeTemplate";

   static function getTypeName($nb = 0) {
      return _n('Change template', 'change templates', $nb);
   }

   public function getCloneRelations() :array {
      return [
         ChangeTemplateHiddenField::class,
         ChangeTemplateMandatoryField::class,
         ChangeTemplatePredefinedField::class,
      ];
   }

   public static function getExtraAllowedFields($withtypeandcategory = 0, $withitemtype = 0) {
      $change = new Change();
      return [
         $change->getSearchOptionIDByField('field', 'impactcontent', 'glpi_changes')      => 'impactcontent',
         $change->getSearchOptionIDByField('field', 'controlistcontent', 'glpi_changes')  => 'controlistcontent',
         $change->getSearchOptionIDByField('field', 'rolloutplancontent', 'glpi_changes') => 'rolloutplancontent',
         $change->getSearchOptionIDByField('field', 'backoutplancontent', 'glpi_changes') => 'backoutplancontent',
         $change->getSearchOptionIDByField('field', 'checklistcontent', 'glpi_changes')   => 'checklistcontent'
      ];
   }
}
