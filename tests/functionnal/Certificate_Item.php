<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/* Test for inc/certificate_item.class.php */

class Certificate_Item extends DbTestCase {

   public function testRelations() {
      $this->newTestedInstance();
      $cert = new \Certificate();

      $input = [
         'name'   => 'Test certificate',
      ];
      $cid1 = (int)$cert->add($input);
      $this->integer($cid1)->isGreaterThan(0);

      $input = [
         'name'   => 'Test certificate 2',
      ];
      $cid2 = (int)$cert->add($input);
      $this->integer($cid2)->isGreaterThan(0);

      $input = [
         'name'   => 'Test certificate 3',
      ];
      $cid3 = (int)$cert->add($input);
      $this->integer($cid3)->isGreaterThan(0);

      $input = [
         'name'   => 'Test certificate 4',
      ];
      $cid4 = (int)$cert->add($input);
      $this->integer($cid4)->isGreaterThan(0);

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $printer = getItemByTypeName('Printer', '_test_printer_all');

      $input = [
         'certificates_id' => $cid1,
         'itemtype'        => 'Computer',
         'items_id'        => $computer->getID()
      ];
      $this->integer(
         (int)$this->testedInstance->add($input)
      )->isGreaterThan(0);

      $input['certificates_id'] = $cid2;
      $this->integer(
         (int)$this->testedInstance->add($input)
      )->isGreaterThan(0);

      $input['certificates_id'] = $cid3;
      $this->integer(
         (int)$this->testedInstance->add($input)
      )->isGreaterThan(0);

      $input = [
         'certificates_id' => $cid1,
         'itemtype'        => 'Printer',
         'items_id'        => $printer->getID()
      ];
      $this->integer(
         (int)$this->testedInstance->add($input)
      )->isGreaterThan(0);

      $input['certificates_id'] = $cid4;
      $this->integer(
         (int)$this->testedInstance->add($input)
      )->isGreaterThan(0);

      $list_items = iterator_to_array($this->testedInstance->getListForItem($computer));
      $this->array($list_items)
         ->hasSize(3)
         ->hasKeys([$cid1, $cid2, $cid3]);

      $list_items = iterator_to_array($this->testedInstance->getListForItem($printer));
      $this->array($list_items)
         ->hasSize(2)
         ->hasKeys([$cid1, $cid4]);

      $this->boolean($cert->getFromDB($cid1))->isTrue();
      $this->exception(
         function () use ($cert) {
            $this->boolean($this->testedInstance->getListForItem($cert))->isFalse();
         }
      )->message->contains('Cannot use getListForItemParams() for a Certificate');

      $list_types = iterator_to_array($this->testedInstance->getDistinctTypes($cid1));
      $expected = [
         -1 => ['itemtype' => 'Computer'],
         0  => ['itemtype' => 'Printer']
      ];
      $this->array($list_types)->isIdenticalTo($expected);

      foreach ($list_types as $type) {
         $list_items = iterator_to_array($this->testedInstance->getTypeItems($cid1, $type['itemtype']));
         $this->array($list_items)->hasSize(1);
      }

      $this->integer($this->testedInstance->countForItem($computer))->isIdenticalTo(3);
      $this->integer($this->testedInstance->countForItem($printer))->isIdenticalTo(2);

      $computer = getItemByTypeName('Computer', '_test_pc02');
      $this->integer($this->testedInstance->countForItem($computer))->isIdenticalTo(0);

      $this->exception(
         function () use ($cert) {
            $this->testedInstance->countForItem($cert);
         }
      )->message->contains('Cannot use getListForItemParams() for a Certificate');

      $this->integer($this->testedInstance->countForMainItem($cert))->isIdenticalTo(2);
   }
}
