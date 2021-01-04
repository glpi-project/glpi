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

namespace Glpi\Api\Deprecated;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.4.0
 */
class TicketFollowup implements DeprecatedInterface
{
   use CommonDeprecatedTrait;

   public function getType(): string {
      return "ITILFollowup";
   }

   public function mapCurrentToDeprecatedHateoas(array $hateoas): array {
      $hateoas = $this->replaceCurrentHateoasRefByDeprecated($hateoas);
      return $hateoas;
   }

   public function mapDeprecatedToCurrentFields(object $fields): object {
      $this
         ->renameField($fields, "tickets_id", "items_id")
         ->addField($fields, "itemtype", "Ticket");

      return $fields;
   }

   public function mapCurrentToDeprecatedFields(array $fields): array {
      $this
         ->renameField($fields, "items_id", "tickets_id")
         ->deleteField($fields, "itemtype")
         ->deleteField($fields, "sourceitems_id")
         ->deleteField($fields, "sourceof_items_id");

      return $fields;
   }

   public function mapDeprecatedToCurrentCriteria(array $criteria): array {
      // Add itemtype condition
      $criteria[] = [
         "link"       => 'AND',
         "field"      => "6",
         "searchtype" => 'equals',
         "value"      => "Ticket"
      ];

      return $criteria;
   }

   public function mapCurrentToDeprecatedSearchOptions(array $soptions): array {
      $this
         ->updateSearchOptionsUids($soptions)
         ->updateSearchOptionsTables($soptions)
         ->alterSearchOption($soptions, "1", [
            "available_searchtypes" => ["contains"]
         ])
         ->alterSearchOption($soptions, "2", [
            "available_searchtypes" => [
               "contains",
               "equals",
               "notequals"
            ]
         ])
         ->alterSearchOption($soptions, "3", [
            "available_searchtypes" => [
               "equals",
               "notequals",
               "lessthan",
               "morethan",
               "contains"
            ]
         ])
         ->alterSearchOption($soptions, "4", [
            "available_searchtypes" => [
               "equals",
               "notequals",
               "contains"
            ]
         ])
         ->alterSearchOption($soptions, "5", [
            "available_searchtypes" => [
               "contains",
               "equals",
               "notequals"
            ]
         ])
         ->deleteSearchOption($soptions, "6")
         ->deleteSearchOption($soptions, "119")
         ->deleteSearchOption($soptions, "document");

      return $soptions;
   }
}