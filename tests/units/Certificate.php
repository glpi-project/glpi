<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/alert.class.php */

class Certificate extends DbTestCase {

   private $method;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      //to handle GLPI barbarian replacements.
      $this->method = str_replace(
            ['\\', 'beforeTestMethod'],
            ['', $method],
            __METHOD__
            );
   }

   public function testAdd() {
      $this->login();
      $obj = new \Certificate();

      // Add
      $in = $this->_getIn($this->method);
      $id = $obj->add($in);
      $this->integer((int)$id)->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      $this->variable($obj->getField('id'))->isEqualTo($id);
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isEqualTo($v);
      }
   }

   public function testUpdate() {
      $this->login();
      $obj = new \Certificate();

      // Add
      $id = $obj->add([
         'name'        => $this->getUniqueString(),
         'entities_id' => 0
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Update
      $id = $obj->getID();
      $in = array_merge(['id' => $id], $this->_getIn($this->method));
      $this->boolean($obj->update($in))->isTrue();
      $this->boolean($obj->getFromDB($id))->isTrue();

      // getField methods
      foreach ($in as $k => $v) {
         $this->variable($obj->getField($k))->isEqualTo($v);
      }
   }

   public function testDelete() {
      $this->login();
      $obj = new \Certificate();

      // Add
      $id = $obj->add([
         'name' => $this->method,
      ]);
      $this->integer($id)->isGreaterThan(0);

      // Delete
      $in = [
         'id' => $obj->getID(),
      ];
      $this->boolean($obj->delete($in))->isTrue();
   }

   public function _getIn($method = "") {
      return [
         'name'                => $method,
         'entities_id'         => 0,
         'serial'              => $this->getUniqueString(),
         'otherserial'         => $this->getUniqueString(),
         'comment'             => $this->getUniqueString(),
         'certificatetypes_id' => $this->getUniqueInteger(),
         'dns_name'            => $this->getUniqueString(),
         'dns_suffix'          => $this->getUniqueString(),
         'users_id_tech'       => $this->getUniqueInteger(),
         'groups_id_tech'      => $this->getUniqueInteger(),
         'locations_id'        => $this->getUniqueInteger(),
         'manufacturers_id'    => $this->getUniqueInteger(),
         'users_id'            => $this->getUniqueInteger(),
         'groups_id'           => $this->getUniqueInteger(),
         'is_autosign'         => 1,
         'date_expiration'     => date('Y-m-d', time() + MONTH_TIMESTAMP),
         'states_id'           => $this->getUniqueInteger(),
         'command'             => $this->getUniqueString(),
         'certificate_request' => $this->getUniqueString(),
         'certificate_item'    => $this->getUniqueString(),
      ];
   }

   public function testCronCertificate() {
      global $CFG_GLPI;

      $this->login();
      $obj = new \Certificate();

      // Add
      $id = $obj->add([
         'name'            => $this->getUniqueString(),
         'entities_id'     => 0,
         'date_expiration' => date('Y-m-d', time() - MONTH_TIMESTAMP)
      ]);
      $this->integer($id)->isGreaterThan(0);

      // set root entity config for certificates alerts
      $entity = new \Entity;
      $entity->update([
         'id'                                   => 0,
         'use_certificates_alert'               => true,
         'send_certificates_alert_before_delay' => true,
      ]);

      // force usage of notification (no alert sended otherwise)
      $CFG_GLPI['use_notifications']  = true;
      $CFG_GLPI['notifications_ajax'] = 1;

      // lanch glpi cron and force task certificate
      $crontask = new \CronTask;
      $force    = -1;
      $ret      = $crontask->launch($force, 1, 'certificate');

      // check presence of the id in alerts table
      $alert  = new \Alert;
      $alerts = $alert->find();

      $this->array($alerts)
           ->hasSize(1);
      $alert_certificate = array_pop($alerts);
      $this->array($alert_certificate)
         ->string['itemtype']->isEqualTo('Certificate')
         ->string['items_id']->isEqualTo($id);

   }
}
