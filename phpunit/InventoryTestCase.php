<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Inventory\Conf;
use Glpi\Inventory\Converter;
use Glpi\Inventory\Inventory;

class InventoryTestCase extends DbTestCase
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

    public function setUp(): void
    {
        parent::setUp();

        $this->nblogs = countElementsInTable(Log::getTable());

        $conf = new Conf();
        $conf->saveConf([
            'enabled_inventory' => 1,
        ]);

        if (file_exists(self::INVENTORY_ARCHIVE_PATH)) {
            unlink(self::INVENTORY_ARCHIVE_PATH);
        }
    }

    public function tearDown(): void
    {
        global $DB;

        parent::tearDown();

        if (str_starts_with($this->name(), 'testImport')) {
            $nblogsnow = countElementsInTable(Log::getTable());
            $logs = $DB->request([
                'FROM' => Log::getTable(),
                'LIMIT' => $nblogsnow,
                'OFFSET' => $this->nblogs,
                'WHERE' => [
                    'NOT' => [
                        'linked_action' => [
                            Log::HISTORY_ADD_DEVICE,
                            Log::HISTORY_ADD_RELATION,
                            Log::HISTORY_ADD_SUBITEM,
                            Log::HISTORY_CREATE_ITEM,
                        ],
                    ],
                ],
            ]);
            $this->assertSame(
                0,
                count($logs),
                print_r(iterator_to_array($logs), true)
            );
        }

        if (str_starts_with($this->name(), 'testUpdate')) {
            $nblogsnow = countElementsInTable(Log::getTable());
            $logs = $DB->request([
                'FROM' => Log::getTable(),
                'LIMIT' => $nblogsnow,
                'OFFSET' => $this->nblogs,
            ]);
            $this->assertSame(0, count($logs));
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(GLPI_INVENTORY_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
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
     * @return Inventory
     */
    protected function doInventory($source, bool $is_xml = false)
    {
        if ($is_xml === true) {
            $converter = new Converter();
            $source = json_decode($converter->convert($source));
        }

        $inventory = new Inventory($source);

        if ($inventory->inError()) {
            dump($inventory->getErrors());
        }
        $this->assertFalse($inventory->inError());
        $this->assertEmpty($inventory->getErrors());

        return $inventory;
    }
}
