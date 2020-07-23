<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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
/**
 * @var DB $DB
 * @var Migration $migration
 */

/** Domains improvements */

/** Add templates to domains  */
$migration->addField('glpi_domains', 'is_template', 'bool', [
   'after' => 'comment'
]);
$migration->addField('glpi_domains', 'template_name', 'string', [
   'after' => 'is_template'
]);
$migration->addKey('glpi_domains', 'is_template');
/** /Add templates to domains  */

/** Active domains */
$migration->addField('glpi_domains', 'is_active', 'bool', ['after' => 'template_name']);
$migration->addKey('glpi_domains', 'is_active');
$migration->addPostQuery(
   $DB->buildUpdate(
      'glpi_domains',
      ['is_active' => 1],
      [true]
   )
);
/** /Active domains */

//remove "useless "other" field
$migration->dropField('glpi_domains', 'others');

// Add fields descriptor field
if (!$DB->fieldExists('glpi_domainrecordtypes', 'fields')) {
   $migration->addField(
      'glpi_domainrecordtypes',
      'fields',
      'text',
      [
         'after'  => 'name'
      ]
   );
   foreach (DomainRecordType::getDefaults() as $type) {
      if (countElementsInTable('glpi_domainrecordtypes', ['name' => $type['name']]) === 0) {
         continue;
      }
      $migration->addPostQuery(
         $DB->buildUpdate(
            'glpi_domainrecordtypes',
            ['fields' => $type['fields']],
            ['name' => $type['name']]
         )
      );
   }
}

// Create new CAA default
if (countElementsInTable('glpi_domainrecordtypes', ['name' => 'CAA']) === 0) {
   foreach (DomainRecordType::getDefaults() as $type) {
      if ($type['name'] === 'CAA') {
         unset($type['id']);
         $migration->addPostQuery(
            $DB->buildInsert(
               DomainRecordType::getTable(),
               $type
            )
         );
         break;
      }
   }
}

// Add a field to store record data as an object if user inputs data using helper form
$migration->addField(
   'glpi_domainrecords',
   'data_obj',
   'text',
   [
      'after'  => 'data'
   ]
);
