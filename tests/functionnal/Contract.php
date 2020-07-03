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

/* Test for inc/contract.class.php */

class Contract extends DbTestCase {

   public function testClone() {
      $contract = new \Contract();
      $input = [
         'name' => 'A test contract',
         'entities_id'  => 0
      ];
      $cid = $contract->add($input);
      $this->integer($cid)->isGreaterThan(0);

      $cloned = $contract->clone();
      $this->integer($cloned)->isGreaterThan($cid);

      /*$calendar = new \Calendar();
      $default_id = getItemByTypeName('Calendar', 'Default', true);
      // get Default calendar
      $this->boolean($calendar->getFromDB($default_id))->isTrue();
      $this->addXmas($calendar);

      $id = $calendar->clone();
      $this->integer($id)->isGreaterThan($default_id);
      $this->boolean($calendar->getFromDB($id))->isTrue();
      //should have been duplicated too.
      $this->checkXmas($calendar);*/
   }
}
