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
      $data = [];

      foreach ([\Computer::class, \Change::class, \Problem::class, \Ticket::class] as $itemtype) {
         $item = new $itemtype();
         $item->fields['id'] = mt_rand(1, 50);

         $img_url = '/front/document.send.php?docid={docid}'; //{docid} to replace by generated doc id
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

   /**
    * Data provider for self::testBaseUrlInConvertTagToImage().
    */
   protected function convertTagToImageBaseUrlProvider() {
      $item = new \Ticket();
      $item->fields['id'] = mt_rand(1, 50);

      $img_url = '/front/document.send.php?docid={docid}'; //{docid} to replace by generated doc id
      $img_url .= '&tickets_id=' . $item->fields['id'];

      return [
         [
            'url_base'     => 'http://glpi.domain.org',
            'item'         => $item,
            'expected_url' => $img_url,
         ],
         [
            'url_base'     => 'http://www.domain.org/glpi/v9.4/',
            'item'         => $item,
            'expected_url' => '/glpi/v9.4/' . $img_url,
         ],
      ];
   }

   /**
    * Check base url handling in conversion of tags to images.
    *
    * @dataProvider convertTagToImageBaseUrlProvider
    */
   public function testBaseUrlInConvertTagToImage($url_base, $item, $expected_url) {

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

      // Save old config
      global $CFG_GLPI;
      $old_url_base = $CFG_GLPI['url_base'];

      // Get result
      $CFG_GLPI['url_base'] = $url_base;
      $result = \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]]);

      // Restore config
      $CFG_GLPI['url_base'] = $old_url_base;

      // Validate result
      $this->string($result)->isEqualTo($expected_result);
   }

   /**
    * Check conversion of tags to images when contents contains multiple inlined images.
    */
   public function testConvertTagToImageWithMultipleInlinedImg() {

      $img_tag_1 = uniqid('', true);
      $img_tag_2 = uniqid('', true);
      $img_tag_3 = uniqid('', true);

      $item = new \Ticket();
      $item->fields['id'] = mt_rand(1, 50);

      // Create multiple documents in DB
      $document = new \Document();
      $doc_id_1 = $document->add([
         'name'     => 'document 1',
         'filename' => 'img1.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag_1,
      ]);
      $this->integer((int)$doc_id_1)->isGreaterThan(0);

      $document = new \Document();
      $doc_id_2 = $document->add([
         'name'     => 'document 2',
         'filename' => 'img2.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag_2,
      ]);
      $this->integer((int)$doc_id_2)->isGreaterThan(0);

      $document = new \Document();
      $doc_id_3 = $document->add([
         'name'     => 'document 3',
         'filename' => 'img3.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag_3,
      ]);
      $this->integer((int)$doc_id_3)->isGreaterThan(0);

      $doc_data = [
         $doc_id_1 => ['tag' => $img_tag_1],
         $doc_id_2 => ['tag' => $img_tag_2],
         $doc_id_3 => ['tag' => $img_tag_3],
      ];

      $content_text    = '';
      $expected_result = '';
      foreach ($doc_data as $doc_id => $doc) {
         $expected_url    = '/front/document.send.php?docid=' . $doc_id . '&tickets_id=' . $item->fields['id'];
         $content_text    .= '<img id="' . $doc['tag'] . '" width="10" height="10" />';
         $expected_result .= '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $doc['tag'] . '" width="10" src="' . $expected_url . '" /></a>';
      }

      // Processed data is expected to be escaped
      $content_text = \Toolbox::addslashes_deep($content_text);
      $expected_result = \Html::entities_deep($expected_result);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, $doc_data)
      )->isEqualTo($expected_result);
   }

   /**
    * Check conversion of tags to images when multiple document matches same tag.
    */
   public function testConvertTagToImageWithMultipleDocMatchesSameTag() {

      $img_tag = uniqid('', true);

      $item = new \Ticket();
      $item->fields['id'] = mt_rand(1, 50);

      // Create multiple documents in DB
      $document = new \Document();
      $doc_id_1 = $document->add([
         'name'     => 'duplicated document 1',
         'filename' => 'img.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag,
      ]);
      $this->integer((int)$doc_id_1)->isGreaterThan(0);

      $document = new \Document();
      $doc_id_2 = $document->add([
         'name'     => 'duplicated document 2',
         'filename' => 'img.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag,
      ]);
      $this->integer((int)$doc_id_2)->isGreaterThan(0);

      $content_text    = '<img id="' . $img_tag . '" width="10" height="10" />';
      $expected_url_1    = '/front/document.send.php?docid=' . $doc_id_1 . '&tickets_id=' . $item->fields['id'];
      $expected_result_1 = '<a href="' . $expected_url_1 . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url_1 . '" /></a>';
      $expected_url_2    = '/front/document.send.php?docid=' . $doc_id_2 . '&tickets_id=' . $item->fields['id'];
      $expected_result_2 = '<a href="' . $expected_url_2 . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url_2 . '" /></a>';

      // Processed data is expected to be escaped
      $content_text = \Toolbox::addslashes_deep($content_text);
      $expected_result_1 = \Html::entities_deep($expected_result_1);
      $expected_result_2 = \Html::entities_deep($expected_result_2);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id_1 => ['tag' => $img_tag]])
      )->isEqualTo($expected_result_1);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id_2 => ['tag' => $img_tag]])
      )->isEqualTo($expected_result_2);
   }
}
