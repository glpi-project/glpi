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

use DbTestCase;

/* Test for inc/savedsearch.class.php */

class Lockedfield extends DbTestCase {

   public function testWithComputer() {
      $computer = new \Computer();
      $cid = (int)$computer->add([
         'name'         => 'Computer from inventory',
         'serial'       => '123456',
         'otherserial'  => '789012',
         'entities_id'  => 0,
         'is_dynamic'   => 1
      ]);
      $this->integer($cid)->isGreaterThan(0);

      $lockedfield = new \Lockedfield();
      $this->boolean($lockedfield->isHandled($computer))->isTrue();
      $this->array($lockedfield->getLocks($computer->getType(), $cid))->isEmpty();

      //update computer manually, to add a locked field
      $this->boolean(
         (bool)$computer->update(['id' => $cid, 'otherserial' => 'AZERTY'])
      )->isTrue();

      $this->boolean($computer->getFromDB($cid))->isTrue();
      $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

      //ensure new dynamic update does not override otherserial again
      $this->boolean(
         (bool)$computer->update([
            'id' => $cid,
            'otherserial'  => '789012',
            'is_dynamic'   => 1
         ])
      )->isTrue();

      $this->boolean($computer->getFromDB($cid))->isTrue();
      $this->variable($computer->fields['otherserial'])->isEqualTo('AZERTY');
      $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

      //ensure new dynamic update do not set new lock on regular update
      $this->boolean(
         (bool)$computer->update([
            'id' => $cid,
            'name'         => 'Computer name changed',
            'is_dynamic'   => 1
         ])
      )->isTrue();

      $this->boolean($computer->getFromDB($cid))->isTrue();
      $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
      $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

      //ensure regular update do work on locked field
      $this->boolean(
         (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
      )->isTrue();
      $this->boolean($computer->getFromDB($cid))->isTrue();
      $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
   }
}
