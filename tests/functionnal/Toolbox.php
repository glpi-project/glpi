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

use \DbTestCase;

/* Test for inc/toolbox.class.php */

class Toolbox extends DbTestCase {

   /**
    * Data provider for self::testConvertTagToImage().
    */
   protected function convertTagToImageProvider() {
      global $CFG_GLPI;

      $data = [];

      foreach ([\Computer::class, \Change::class, \Problem::class, \Ticket::class] as $itemtype) {
         $item = new $itemtype();
         $item->fields['id'] = mt_rand(1, 50);

         $img_url = $CFG_GLPI['url_base'] . '/front/document.send.php?docid={docid}'; //{docid} to replace by generated doc id
         if ($item instanceof \CommonITILObject) {
            $img_url .= '&' . $item->getForeignKeyField() . '=' . $item->fields['id'];
         }

         $data[] = [
            'item'         => $item,
            'expected_url' => $img_url,
         ];

         if ($item instanceof \CommonITILObject) {
            $fup = new \ITILFollowup();
            $fup->input['_job'] = $item;
            $data[] = [
               'item'         => $fup,
               'expected_url' => $img_url,
            ];

            $solution = new \ITILSolution();
            $solution->input['_job'] = $item;
            $data[] = [
               'item'         => $solution,
               'expected_url' => $img_url,
            ];

            $task_itemtype = $itemtype . 'Task';
            $task = new $task_itemtype();
            $task->input['_job'] = $item;
            $data[] = [
               'item'         => $task,
               'expected_url' => $img_url,
            ];
         }
      }

      return $data;
   }

   /**
    * Check conversion of tags to images.
    *
    * @dataProvider convertTagToImageProvider
    */
   public function testConvertTagToImage($item, $expected_url) {

      $img_tag = uniqid('', true);

      // Create document in DB
      $document = new \Document();
      $doc_id = $document->add([
         'name'     => 'basic document',
         'filename' => 'img.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag,
      ]);
      $this->integer((int)$doc_id)->isGreaterThan(0);

      $content_text   = '<img id="' . $img_tag. '" width="10" height="10" />';
      $expected_url   = str_replace('{docid}', $doc_id, $expected_url);
      $expected_result = '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $img_tag. '" width="10" src="' . $expected_url. '" /></a>';

      // Processed data is expected to be escaped
      $content_text = \Toolbox::addslashes_deep($content_text);
      $expected_result = \Html::entities_deep($expected_result);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]])
      )->isEqualTo($expected_result);
   }
}
