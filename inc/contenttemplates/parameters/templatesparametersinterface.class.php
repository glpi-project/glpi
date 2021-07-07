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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

interface TemplatesParametersInterface
{
   /**
    * Get default node name to use for this class.
    *
    * @return string
    */
   public static function getDefaultNodeName(): string;

   /**
    * Get object label to use for this class.
    *
    * @return string
    */
   public static function getObjectLabel(): string;

   /**
    * Get values for a given item, used for template rendering
    *
    * @param CommonDBTM $item
    * @param bool       $root
    *
    * @return array
    */
   public function getValues(CommonDBTM $item): array;

   /**
    * Get the available parameters, used by autocomplete
    *
    * @return array
    */
   public function getAvailableParameters(): array;
}
