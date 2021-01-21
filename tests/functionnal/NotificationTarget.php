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

namespace tests\units;

use \DbTestCase;

/* Test for inc/notificationtarget.class.php */

class NotificationTarget extends DbTestCase {

   public function testGetSubjectPrefix() {
      $this->login();

      $root    = getItemByTypeName('Entity', 'Root entity', true);
      $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
      $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
      $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

      $ntarget_parent  = new \NotificationTarget($parent);
      $ntarget_child_1 = new \NotificationTarget($child_1);
      $ntarget_child_2 = new \NotificationTarget($child_2);

      $this->string($ntarget_parent->getSubjectPrefix())->isEqualTo("[GLPI] ");
      $this->string($ntarget_child_1->getSubjectPrefix())->isEqualTo("[GLPI] ");
      $this->string($ntarget_child_2->getSubjectPrefix())->isEqualTo("[GLPI] ");

      $entity  = new \Entity;
      $this->boolean($entity->update([
         'id'                       => $root,
         'notification_subject_tag' => "prefix_root",
      ]))->isTrue();

      $this->string($ntarget_parent->getSubjectPrefix())->isEqualTo("[prefix_root] ");
      $this->string($ntarget_child_1->getSubjectPrefix())->isEqualTo("[prefix_root] ");
      $this->string($ntarget_child_2->getSubjectPrefix())->isEqualTo("[prefix_root] ");

      $this->boolean($entity->update([
         'id'                       => $parent,
         'notification_subject_tag' => "prefix_parent",
      ]))->isTrue();

      $this->string($ntarget_parent->getSubjectPrefix())->isEqualTo("[prefix_parent] ");
      $this->string($ntarget_child_1->getSubjectPrefix())->isEqualTo("[prefix_parent] ");
      $this->string($ntarget_child_2->getSubjectPrefix())->isEqualTo("[prefix_parent] ");

      $this->boolean($entity->update([
         'id'                       => $child_1,
         'notification_subject_tag' => "prefix_child_1",
      ]))->isTrue();

      $this->string($ntarget_parent->getSubjectPrefix())->isEqualTo("[prefix_parent] ");
      $this->string($ntarget_child_1->getSubjectPrefix())->isEqualTo("[prefix_child_1] ");
      $this->string($ntarget_child_2->getSubjectPrefix())->isEqualTo("[prefix_parent] ");

      $this->boolean($entity->update([
         'id'                       => $child_2,
         'notification_subject_tag' => "prefix_child_2",
      ]))->isTrue();

      $this->string($ntarget_parent->getSubjectPrefix())->isEqualTo("[prefix_parent] ");
      $this->string($ntarget_child_1->getSubjectPrefix())->isEqualTo("[prefix_child_1] ");
      $this->string($ntarget_child_2->getSubjectPrefix())->isEqualTo("[prefix_child_2] ");
   }
}
