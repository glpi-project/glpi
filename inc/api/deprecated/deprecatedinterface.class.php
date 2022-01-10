<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

namespace Glpi\Api\Deprecated;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5
 */

interface DeprecatedInterface
{
   /**
    * Get the deprecated itemtype
    *
    * @return string
    */
   public function getType(): string;

   /**
    * Convert current hateoas to deprecated hateoas
    *
    * @param array $hateoas
    * @return array
    */
   public function mapCurrentToDeprecatedHateoas(array $hateoas): array;

   /**
    * Convert current fields to deprecated fields
    *
    * @param array $fields
    * @return array
    */
   public function mapCurrentToDeprecatedFields(array $fields): array;

   /**
    * Convert current searchoptions to deprecated searchoptions
    *
    * @param array $soptions
    * @return array
    */
   public function mapCurrentToDeprecatedSearchOptions(array $soptions): array;

   /**
    * Convert deprecated fields to current fields
    *
    * @param object $fields
    * @return object
    */
   public function mapDeprecatedToCurrentFields(object $fields): object;

   /**
    * Convert deprecated search criteria to current search criteria
    *
    * @param array $criteria
    * @return array
    */
   public function mapDeprecatedToCurrentCriteria(array $criteria): array;
}