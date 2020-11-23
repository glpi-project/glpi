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

use DbTestCase;

/* Test for inc/software.class.php */

class Domain extends DbTestCase {

   public function testTypeName() {
      $this->string(\Domain::getTypeName(1))->isIdenticalTo('Domain');
      $this->string(\Domain::getTypeName(0))->isIdenticalTo('Domains');
      $this->string(\Domain::getTypeName(10))->isIdenticalTo('Domains');
   }

   public function testPrepareInput() {
      $domain = new \Domain();
      $expected = ['date_creation' => 'NULL', 'date_expiration' => null];
      $result   = $domain->prepareInputForUpdate(['date_creation' => '', 'date_expiration' => null]);
      $this->array($result)->isIdenticalTo($expected);
      $result   = $domain->prepareInputForAdd(['date_creation' => '', 'date_expiration' => null]);
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testCleanDBonPurge() {
      $this->login();

      $domain = new \Domain();
      $domains_id = (int)$domain->add([
         'name'   => 'glpi-project.org'
      ]);
      $this->integer($domains_id)->isGreaterThan(0);

      $domain_item = new \Domain_Item();
      $this->integer(
         $domain_item->add([
            'domains_id'   => $domains_id,
            'itemtype'     => 'Computer',
            'items_id'     => getItemByTypeName('Computer', '_test_pc01', true)
         ])
      )->isGreaterThan(0);

      $record = new \DomainRecord();
      foreach (['www', 'ftp', 'mail'] as $sub) {
         $this->integer(
            (int)$record->add([
               'name'         => $sub,
               'data'         => 'glpi-project.org.',
               'domains_id'   => $domains_id
            ])
         )->isGreaterThan(0);
      }

      $this->integer((int)countElementsInTable($domain_item->getTable(), ['domains_id' => $domains_id]))->isIdenticalTo(1);
      $this->integer((int)countElementsInTable($record->getTable(), ['domains_id' => $domains_id]))->isIdenticalTo(3);
      $this->boolean($domain->delete(['id' => $domains_id], true))->isTrue();
      $this->integer((int)countElementsInTable($domain_item->getTable(), ['domains_id' => $domains_id]))->isIdenticalTo(0);
      $this->integer((int)countElementsInTable($record->getTable(), ['domains_id' => $domains_id]))->isIdenticalTo(0);
   }

   public function testGetEntitiesToNotify() {
      global $DB;
      $this->login();

      $this->array(\Entity::getEntitiesToNotify('use_domains_alert'))->isEmpty();

      $entity = getItemByTypeName('Entity', '_test_root_entity');
      $this->boolean(
         $entity->update([
            'id'                                      => $entity->fields['id'],
            'use_domains_alert'                       => 1,
            'send_domains_alert_close_expiries_delay' => 7,
            'send_domains_alert_expired_delay'        => 1
         ])
      )->isTrue();
      $this->boolean($entity->getFromDB($entity->fields['id']))->isTrue();

      $this->array(\Entity::getEntitiesToNotify('use_domains_alert'))->isIdenticalTo([
         getItemByTypeName('Entity', '_test_root_entity', true)   => 1,
         getItemByTypeName('Entity', '_test_child_1', true)       => 1,
         getItemByTypeName('Entity', '_test_child_2', true)       => 1,
      ]);

      $iterator = $DB->request(\Domain::expiredDomainsCriteria($entity->fields['id']));
      $this->string($iterator->getSql())->isIdenticalTo(
         "SELECT * FROM `glpi_domains` WHERE " .
         "NOT (`date_expiration` IS NULL) AND `is_deleted` = '0' ".
         "AND DATEDIFF(CURDATE(), `date_expiration`) > 1 AND DATEDIFF(CURDATE(), `date_expiration`) > 0"
      );

      $iterator = $DB->request(\Domain::closeExpiriesDomainsCriteria($entity->fields['id']));
      $this->string($iterator->getSql())->isIdenticalTo(
         "SELECT * FROM `glpi_domains` WHERE " .
         "NOT (`date_expiration` IS NULL) AND `is_deleted` = '0' ".
         "AND DATEDIFF(CURDATE(), `date_expiration`) > -7 AND DATEDIFF(CURDATE(), `date_expiration`) < 0"
      );
   }

   public function testTransfer() {
      $this->login();
      $domain = new \Domain();
      $domains_id = (int)$domain->add([
         'name'   => 'glpi-project.org'
      ]);
      $this->integer($domains_id)->isGreaterThan(0);

      $record = new \DomainRecord();
      foreach (['www', 'ftp', 'mail'] as $sub) {
         $this->integer(
            (int)$record->add([
               'name'         => $sub,
               'data'         => 'glpi-project.org.',
               'domains_id'   => $domains_id
            ])
         )->isGreaterThan(0);
      }

      $entities_id = getItemByTypeName('Entity', '_test_child_2', true);

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
         [$domains_id]
      );
      $transfer->moveItems(['Domain' => [$domains_id]], $entities_id, [$domains_id]);
      unset($_SESSION['glpitransfer_list']);

      $this->boolean($domain->getFromDB($domains_id))->isTrue();
      $this->integer((int)$domain->fields['entities_id'])->isidenticalTo($entities_id);

      global $DB;
      $records = $DB->request([
         'FROM'   => \DomainRecord::getTable(),
         'WHERE'  => ['domains_id' => $domains_id]
      ]);
      while ($row = $records->next()) {
         $this->integer((int)$row['entities_id'])->isidenticalTo($entities_id);
      }
   }
}
