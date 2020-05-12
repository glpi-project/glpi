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

class KnowbaseItem extends \GLPITestCase {

   public function testGetForCategory() {
      global $DB;

      // Prepare mocks
      $m_db = new \mock\DB();
      $m_kbi = new \mock\KnowbaseItem();

      // Mocked db request result
      $it = new \ArrayIterator([
         ['id' => '1'],
         ['id' => '2'],
         ['id' => '3'],
      ]);
      $this->calling($m_db)->request = $it;

      // Ignore get fromDB
      $this->calling($m_kbi)->getFromDB = true;

      // True for call 1 & 3, false for call 2 and every following calls
      $this->calling($m_kbi)->canViewItem[0] = false;
      $this->calling($m_kbi)->canViewItem[1] = true;
      $this->calling($m_kbi)->canViewItem[2] = false;
      $this->calling($m_kbi)->canViewItem[3] = true;

      // Replace global DB with mocked DB
      $DB = $m_db;

      // Expected : [1, 3]
      $this->array(\KnowbaseItem::getForCategory(1, $m_kbi))
         ->hasSize(2)
         ->containsValues([1, 3]);

      // Expected : [-1]
      $this->array(\KnowbaseItem::getForCategory(1, $m_kbi))
         ->hasSize(1)
         ->contains(-1);
   }
}
