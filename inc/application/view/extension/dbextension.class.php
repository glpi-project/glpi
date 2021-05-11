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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class DBExtension extends AbstractExtension {

   private function getDBUtils(): DbUtils {
      static $dbu = null;
      if ($dbu === null) {
         $dbu = new DbUtils();
      }
      return $dbu;
   }

   public function getFilters() {
      return [];
   }

   public function getFunctions() {
      $dbu = $this->getDBUtils();
      return [
         new TwigFunction('getSonsOf', [$dbu, 'getSonsOf']),
         new TwigFunction('getTableNameForForeignKeyField', [$dbu, 'getTableNameForForeignKeyField']),
         new TwigFunction('getItemTypeForTable', [$dbu, 'getItemTypeForTable']),
         new TwigFunction('getItemtypeForForeignKeyField', [$dbu, 'getItemtypeForForeignKeyField']),
      ];
   }
}
