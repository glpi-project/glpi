<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;

/* Test for inc/notificationeventajax.class.php */

class NotificationEventMailing extends DbTestCase
{
    public function testGetTargetField()
    {
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

    public function testCanCron()
    {
        $this->boolean(\NotificationEventMailing::canCron())->isTrue();
    }

    public function testGetAdminData()
    {
        global $CFG_GLPI;

        $this->array(\NotificationEventMailing::getAdminData())
         ->isIdenticalTo([
             'email'     => $CFG_GLPI['admin_email'],
             'name'      => $CFG_GLPI['admin_email_name'],
             'language'  => $CFG_GLPI['language']
         ]);

        $CFG_GLPI['admin_email'] = 'adminlocalhost';

        $this->when(
            function () {
                $this->array(\NotificationEventMailing::getAdminData())->isIdenticalTo([]);
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid email address "adminlocalhost" configured in "admin_email".')
         ->exists();
    }

    public function testGetEntityAdminsData()
    {
        $this->login();

        $entity1 = getItemByTypeName('Entity', '_test_child_1');
        $this->boolean(
            $entity1->update([
                'id'                 => $entity1->getId(),
                'admin_email'        => 'entadmin@localhost',
                'admin_email_name'   => 'Entity admin ONE'
            ])
        )->isTrue();

        $sub_entity1 = $this->createItem(\Entity::class, ['name' => 'sub entity', 'entities_id' => $entity1->getId()]);

        $entity2 = getItemByTypeName('Entity', '_test_child_2');
        $this->boolean(
            $entity2->update([
                'id'                 => $entity2->getId(),
                'admin_email'        => 'entadmin2localhost',
                'admin_email_name'   => 'Entity admin TWO'
            ])
        )->isTrue();

        $entity0_result = [
            [
                'name'     => '',
                'email'    => 'admsys@localhost',
                'language' => 'en_GB',
            ]
        ];
        $this->array(\NotificationEventMailing::getEntityAdminsData(0))->isEqualTo($entity0_result);
        $entity1_result = [
            [
                'name'     => 'Entity admin ONE',
                'email'    => 'entadmin@localhost',
                'language' => 'en_GB',
            ]
        ];
        $this->array(\NotificationEventMailing::getEntityAdminsData($entity1->getID()))->isEqualTo($entity1_result);
        $this->array(\NotificationEventMailing::getEntityAdminsData($sub_entity1->getID()))->isEqualTo($entity1_result);

        $this->when(
            function () use ($entity2, $entity0_result) {
                $this->array(\NotificationEventMailing::getEntityAdminsData($entity2->getID()))->isEqualTo($entity0_result);
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid email address "entadmin2localhost" configured for entity "' . $entity2->getID() . '". Default administrator email will be used.')
         ->exists();
    }
}
