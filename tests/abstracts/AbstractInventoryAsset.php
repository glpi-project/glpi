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

namespace tests\units\Glpi\Inventory\Asset;

abstract class AbstractInventoryAsset extends \InventoryTestCase
{
    protected $myclass = "";

    protected $log_entries;
    protected $new_log_entries = 0;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);
        $this->log_entries = countElementsInTable(\Log::getTable());
    }

    public function afterTestMethod($method)
    {
        parent::afterTestMethod($method);
        $log_entries = countElementsInTable(\Log::getTable());
        $this->integer($log_entries)->isIdenticalTo($this->log_entries + $this->new_log_entries);
    }

    /**
     * Data provider for asset
     *
     * @return array
     */
    abstract protected function assetProvider(): array;

   /**
    * Test asset preparation
    *
    * @param array $json JSON asset data
    *
    * @return void
    */
   //abstract public function testPrepare($json, $asset);
}
