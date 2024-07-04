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

class InventoryTestCase extends \DbTestCase
{
    protected const INV_FIXTURES = GLPI_ROOT . '/vendor/glpi-project/inventory_format/examples/';

    /**
     * Path to use to test inventory archive manipulations.
     * File will be removed before/after tests.
     * @var string
     */
    protected const INVENTORY_ARCHIVE_PATH = GLPI_TMP_DIR . '/to_inventory.zip';

    /** @var int */
    protected int $nblogs;

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);

        $this->nblogs = countElementsInTable(\Log::getTable());

        $conf = new \Glpi\Inventory\Conf();
        $conf->saveConf([
            'enabled_inventory' => 1
        ]);

        if (file_exists(self::INVENTORY_ARCHIVE_PATH)) {
            unlink(self::INVENTORY_ARCHIVE_PATH);
        }
    }

    public function afterTestMethod($method)
    {
        global $DB;

        parent::afterTestMethod($method);
        if (str_starts_with($method, 'testImport')) {
           //$this->dump('Checking for unexpected logs');
            $nblogsnow = countElementsInTable(\Log::getTable());
            $logs = $DB->request([
                'FROM' => \Log::getTable(),
                'LIMIT' => $nblogsnow,
                'OFFSET' => $this->nblogs,
                'WHERE' => [
                    'NOT' => [
                        'linked_action' => [
                            \Log::HISTORY_ADD_DEVICE,
                            \Log::HISTORY_ADD_RELATION,
                            \Log::HISTORY_ADD_SUBITEM,
                            \Log::HISTORY_CREATE_ITEM
                        ]
                    ]
                ]
            ]);
            $this->integer(count($logs))->isIdenticalTo(0, print_r(iterator_to_array($logs), true));
        }

        if (str_starts_with($method, 'testUpdate')) {
            $nblogsnow = countElementsInTable(\Log::getTable());
            $logs = $DB->request([
                'FROM' => \Log::getTable(),
                'LIMIT' => $nblogsnow,
                'OFFSET' => $this->nblogs,
            ]);
            $this->integer(count($logs))->isIdenticalTo(0/*, print_r(iterator_to_array($logs), true)*/);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(GLPI_INVENTORY_DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        if (file_exists(self::INVENTORY_ARCHIVE_PATH)) {
            unlink(self::INVENTORY_ARCHIVE_PATH);
        }
    }

    /**
     * Execute an inventory
     *
     * @param mixed   $source Source as JSON or XML
     * @param boolean $is_xml XML or JSON
     *
     * @return \Glpi\Inventory\Inventory
     */
    protected function doInventory($source, bool $is_xml = false)
    {
        if ($is_xml === true) {
            $converter = new \Glpi\Inventory\Converter();
            $source = json_decode($converter->convert($source));
        }

        $inventory = new \Glpi\Inventory\Inventory($source);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        return $inventory;
    }
}
