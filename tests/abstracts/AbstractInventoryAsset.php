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

namespace tests\units\Glpi\Inventory\Asset;

abstract class AbstractInventoryAsset extends \DbTestCase {
   protected $myclass = "";

   protected $log_entries;
   protected $new_log_entries = 0;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->log_entries = countElementsInTable(\Log::getTable());
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      $log_entries = countElementsInTable(\Log::getTable());
      $this->integer($log_entries)->isIdenticalTo($this->log_entries + $this->new_log_entries);
   }

   /**
    * Data provider for asset
    *
    * @return array
    */
   abstract protected function assetProvider() :array;

   /**
    * Test asset preparation
    *
    * @param array $json JSON asset data
    *
    * @return void
    */
   //abstract public function testPrepare($json, $asset);
}
