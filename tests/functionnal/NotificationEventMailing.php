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

use \DbTestCase;

/* Test for inc/notificationeventajax.class.php */

class NotificationEventMailing extends DbTestCase {

   public function testGetTargetField() {
      $data = [];
      $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

      $expected = ['email' => null];
      $this->array($data)->isIdenticalTo($expected);

      $data = ['email' => 'user'];
      $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

      $expected = ['email' => null];
      $this->array($data)->isIdenticalTo($expected);

      $data = ['email' => 'user@localhost'];
      $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');

      $expected = ['email' => 'user@localhost'];
      $this->array($data)->isIdenticalTo($expected);

      $uid = getItemByTypeName('User', TU_USER, true);
      $data = ['users_id' => $uid];

      $this->string(\NotificationEventMailing::getTargetField($data))->isIdenticalTo('email');
      $expected = [
         'users_id'  => $uid,
         'email'     => TU_USER . '@glpi.com'
      ];
      $this->array($data)->isIdenticalTo($expected);
   }

   public function testCanCron() {
      $this->boolean(\NotificationEventMailing::canCron())->isTrue();
   }

   public function testGetAdminData() {
      global $CFG_GLPI;

      $this->array(\NotificationEventMailing::getAdminData())
         ->isIdenticalTo([
            'email'     => $CFG_GLPI['admin_email'],
            'name'      => $CFG_GLPI['admin_email_name'],
            'language'  => $CFG_GLPI['language']
         ]);

      $CFG_GLPI['admin_email'] = 'adminlocalhost';
      $this->boolean(\NotificationEventMailing::getAdminData())->isFalse();
   }

   public function testGetEntityAdminsData() {
      $this->boolean(\NotificationEventMailing::getEntityAdminsData(0))->isFalse();

      $this->login();

      $entity1 = getItemByTypeName('Entity', '_test_child_1');
      $this->boolean(
         $entity1->update([
            'id'                 => $entity1->getId(),
            'admin_email'        => 'entadmin@localhost',
            'admin_email_name'   => 'Entity admin ONE'
         ])
      )->isTrue();

      $entity2 = getItemByTypeName('Entity', '_test_child_2');
      $this->boolean(
         $entity2->update([
            'id'                 => $entity2->getId(),
            'admin_email'        => 'entadmin2localhost',
            'admin_email_name'   => 'Entity admin TWO'
         ])
      )->isTrue();

      $this->array(\NotificationEventMailing::getEntityAdminsData($entity1->getID()))
         ->isIdenticalTo([
            [
               'language' => 'en_GB',
               'email' => 'entadmin@localhost',
               'name' => 'Entity admin ONE'
            ]
         ]);
      $this->boolean(\NotificationEventMailing::getEntityAdminsData($entity2->getID()))->isFalse();

      //reset
      $this->boolean(
         $entity1->update([
            'id'                 => $entity1->getId(),
            'admin_email'        => 'NULL',
            'admin_email_name'   => 'NULL'
         ])
      )->isTrue();
      $this->boolean(
         $entity2->update([
            'id'                 => $entity2->getId(),
            'admin_email'        => 'NULL',
            'admin_email_name'   => 'NULL'
         ])
      )->isTrue();
   }
}
