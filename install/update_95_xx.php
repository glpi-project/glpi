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
 * Update from 9.5.x to x.x.x
 *
 * @return bool for success (will die for most error)
**/
function update95toXX() {
   global $DB, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), 'x.x.x'));
   $migration->setVersion('x.x.x');

   require __DIR__ . '/update_95_xx/softwares.php';
   include __DIR__ . '/update_95_xx/domains.php';
   require __DIR__ . '/update_95_xx/devicebattery.php';

   //add is_fqdn on some domain records types
   $fields = [
      'CNAME'  => ['target'],
      'MX'     => ['server'],
      'SOA'    => ['primary_name_server', 'primary_contact'],
      'SRV'    => ['target']
   ];

   $fields_it = $DB->request([
      'FROM'   => DomainRecordType::getTable(),
      'WHERE'  => ['name' => array_keys($fields)]
   ]);
   while ($field = $fields_it->next()) {
      if (empty($field['fields'])) {
         if ($field['name'] === 'CNAME') {
            //cname field definition has been added
            $field['fields'] = json_encode([[
               'key'         => 'target',
               'label'       => 'Target',
               'placeholder' => 'sip.example.com.',
               'is_fqdn'     => true
            ]]);
         } else {
            continue;
         }
      }
      $type_fields = DomainRecordType::decodeFields($field['fields']);
      $updated = false;
      foreach ($type_fields as &$conf) {
         if (in_array($conf['key'], $fields[$field['name']])) {
            $conf['is_fqdn'] = true;
            $updated = true;
         }
      }

      if ($updated) {
         $DB->update(
            DomainRecordType::getTable(),
            ['fields' => json_encode($type_fields)],
            ['name' => $field['name']]
         );
      }
   }

   // ************ Keep it at the end **************
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $rank = 1;
      foreach ($tab as $newval) {
         $DB->updateOrInsert("glpi_displaypreferences", [
            'rank'      => $rank++
         ], [
            'users_id'  => "0",
            'itemtype'  => $type,
            'num'       => $newval,
         ]);
      }
   }

   $migration->executeMigration();

   return $updateresult;
}
