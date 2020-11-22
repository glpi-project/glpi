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

namespace tests\units;

use \DbTestCase;

/* Test for inc/transfer.class.php */

class Transfer extends DbTestCase {

   public function testTransfer() {
      $this->login();

      //Original entity
      $fentity = (int)getItemByTypeName('Entity', '_test_root_entity', true);
      //Destination entity
      $dentity = (int)getItemByTypeName('Entity', '_test_child_2', true);

      $itemtypeslist = $this->getClasses(
         'searchOptions',
         [
            '/^Rule.*/',
            '/^Common.*/',
            '/^DB.*/',
            '/^SlaLevel.*/',
            '/^OlaLevel.*/',
            'Reservation',
            'ReservationItem',
            'Event',
            'Glpi\\Event',
            'KnowbaseItem',
            'NetworkPortMigration',
            '/^TicketTemplate.*/',
            '/^Computer_Software.*/',
            '/SavedSearch.*/',
            '/.*Notification.*/',
            '/.*Cost.*/',
            '/^Item_.*/',
            '/^Device.*/',
            '/.*Validation$/',
            '/^Network.*/',
            'CalendarSegment',
            'IPAddress',
            'IPNetwork',
            'FQDN',
            '/^SoftwareVersion.*/',
            '/^SoftwareLicense.*/',
            '/.*Predefined.*/',
            '/.*Mandatory.*/',
            '/.*Hidden.*/',
            'Entity_Reminder',
            'Document_Item',
            'Cartridge',
            '/.*Task.*/',
            'Entity_RSSFeed',
            'ComputerVirtualMachine',
            'FieldUnicity',
            'PurgeLogs',
            '/.*_?KnowbaseItem_?.*/',
            'Consumable',
            'Infocom',
            'ComputerAntivirus',
            'TicketRecurrent'
         ]
      );

      $count = 0;
      foreach ($itemtypeslist as $itemtype) {
         $item_class = new \ReflectionClass($itemtype);
         if ($item_class->isAbstract()) {
            continue;
         }

         $obj = new $itemtype();
         if (!$obj->isEntityAssign()) {
            continue;
         }

         // Add
         $input = [
            'name'            => 'Object to transfer',
            'entities_id'     => $fentity,
            'content'         => 'A content',
            'definition_time' => '',
            'begin_date'      => ''
         ];

         if ($obj->maybeLocated()) {
            $input['locations_id'] = '';
         }

         $id = $obj->add($input);
         $this->integer((int)$id)->isGreaterThan(0, "Cannot add $itemtype");
         $this->boolean($obj->getFromDB($id))->isTrue();

         //transer to another entity
         $transfer = new \Transfer();

         $controller = new \atoum\atoum\mock\controller();
         $controller->__construct = function() {
            // void
         };

         $ma = new \mock\MassiveAction([], [], 'process', $controller);

         \MassiveAction::processMassiveActionsForOneItemtype(
            $ma,
            $obj,
            [$id]
         );
         $transfer->moveItems([$itemtype => [$id]], $dentity, [$id]);
         unset($_SESSION['glpitransfer_list']);

         $this->boolean($obj->getFromDB($id))->isTrue();
         $this->integer((int)$obj->fields['entities_id'])->isidenticalTo($dentity, "Transfer has failed on $itemtype");

         ++$count;
      }
      $this->dump(
         sprintf(
            '%1$s itemtypes tested',
            $count
         )
      );
   }

   public function testDomainTransfer() {
      $this->login();

      //Original entity
      $fentity = (int)getItemByTypeName('Entity', '_test_root_entity', true);
      //Destination entity
      $dentity = (int)getItemByTypeName('Entity', '_test_child_2', true);

      //records types
      $type_a = (int)getItemByTypeName('DomainRecordType', 'A', true);
      $type_cname = (int)getItemByTypeName('DomainRecordType', 'CNAME', true);

      $domain = new \Domain;
      $record = new \DomainRecord;

      $did = (int)$domain->add([
         'name'         => 'glpi-project.org',
         'entities_id'  => $fentity
      ]);
      $this->integer($did)->isGreaterThan(0);
      $this->boolean($domain->getFromDB($did))->isTrue();

      $this->integer(
         (int)$record->add([
            'name'         => 'glpi-project.org.',
            'type'         => $type_a,
            'data'         => '127.0.1.1',
            'entities_id'  => $fentity,
            'domains_id'   => $did
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$record->add([
            'name'         => 'www.glpi-project.org.',
            'type'         => $type_cname,
            'data'         => 'glpi-project.org.',
            'entities_id'  => $fentity,
            'domains_id'   => $did
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$record->add([
            'name'         => 'doc.glpi-project.org.',
            'type'         => $type_cname,
            'data'         => 'glpi-doc.rtfd.io',
            'entities_id'  => $fentity,
            'domains_id'   => $did
         ])
      )->isGreaterThan(0);

      //transer to another entity
      $transfer = new \Transfer();

      $controller = new \atoum\atoum\mock\controller();
      $controller->__construct = function() {
         // void
      };

      $ma = new \mock\MassiveAction([], [], 'process', $controller);

      \MassiveAction::processMassiveActionsForOneItemtype(
         $ma,
         $domain,
         [$did]
      );
      $transfer->moveItems(['Domain' => [$did]], $dentity, [$did]);
      unset($_SESSION['glpitransfer_list']);

      $this->boolean($domain->getFromDB($did))->isTrue();
      $this->integer((int)$domain->fields['entities_id'])->isidenticalTo($dentity);

      global $DB;
      $records = $DB->request([
         'FROM'   => $record->getTable(),
         'WHERE'  => [
            'domains_id' => $did
         ]
      ]);

      $this->integer(count($records))->isidenticalTo(3);
      foreach ($records as $rec) {
         $this->integer((int)$rec['entities_id'])->isidenticalTo($dentity);
      }
   }
}
