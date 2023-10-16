<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Computer;
use Document;
use Document_Item;

class CommonDBTM extends \GLPITestCase
{
    public function testAddFiles()
    {
        $filename_txt = '65292dc32d6a87.46654965' . 'foo.txt';
        $content = $this->getUniqueString();
        file_put_contents(GLPI_TMP_DIR . '/' . $filename_txt, $content);

        // We need to use an item saved in the DB, then we test AddFiles
        // from an existing class with a table in DB.
        $item = new Computer();
        $item->add([
            'name' => 'a computer',
            'entities_id' => 0,
        ]);
        $item->getFromDB($item->getID());
        $input = [
            'name' => 'Upload new file',
            '_filename' => [
                0 => $filename_txt,
            ],
            '_tag_filename' => [
                0 => '0bf32119-761764d0-65292dc0770083.87619309',
            ],
            '_prefix_filename' => [
                0 => '65292dc32d6a87.46654965',
            ]
        ];

        // The tested method is called in post_addItem()
        // Then the item has a property inpur which contains the form data
        $item->input = $input;
        $output = $item->addFiles($input);
        unlink(GLPI_TMP_DIR . '/' . $filename_txt);
        $document_item = new Document_Item();
        $document_item->getFromDbByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID()
        ]);
        // If no or several relations are found, this assertion will fail
        $this->boolean($document_item->isNewItem())->isFalse();

        // Check the document exists
        $document = new Document();
        $document->getFromDB($document_item->fields['documents_id']);
        $this->boolean($document->isNewItem())->isFalse();

        // Check the file has the name we expect, without prefix
        $this->string($document->fields['filename'])->isEqualTo('foo.txt');

        // Try to add a file which already exists
        $filename_txt = '6079908c4be820.58460925' . 'foo.txt';
        $content = $this->getUniqueString();
        file_put_contents(GLPI_TMP_DIR . '/' . $filename_txt, $content);
        $document = new Document();
        $document->add([
            'entities_id' => 0,
            'is_recursive' => 0,
            '_only_if_upload_succeed' => 1,
            '_filename' => [
                0 => $filename_txt,
            ],
            '_prefix_filename' => [
                0 => '6079908c4be820.58460925',
            ]
        ]);
        unlink(GLPI_TMP_DIR . '/' . $filename_txt);
        $this->boolean($document->isNewItem())->isFalse();

        $item = new Computer();
        $item->add([
            'name' => 'an other computer',
            'entities_id' => 0,
        ]);
        $item->getFromDB($item->getID());
        $filename_txt = '65292dc32d6a87.22222222' . 'bar.txt';
        file_put_contents(GLPI_TMP_DIR . '/' . $filename_txt, $content);
        $input = [
            'name' => 'Upload new file',
            '_filename' => [
                0 => $filename_txt,
            ],
            '_tag_filename' => [
                0 => '0bf32119-761764d0-65292dc0770083.87619309',
            ],
            '_prefix_filename' => [
                0 => '65292dc32d6a87.22222222',
            ]
        ];

        $item->input = $input;
        $output = $item->addFiles($input);
        unlink(GLPI_TMP_DIR . '/' . $filename_txt);
        $document_item = new Document_Item();
        $document_item->getFromDbByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID()
        ]);
        // If no or several relations are found, this assertion will fail
        $this->boolean($document_item->isNewItem())->isFalse();

        // Check the document exists
        $document = new Document();
        $document->getFromDB($document_item->fields['documents_id']);
        $this->boolean($document->isNewItem())->isFalse();

        // Check the file has the name we expect, without prefix
        $this->string($document->fields['filename'])->isEqualTo('bar.txt');
    }
}
