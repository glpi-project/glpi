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

namespace tests\units;

use DbTestCase;
use Glpi\Api\Deprecated\TicketFollowup;
use Glpi\Features\Clonable;
use Glpi\Features\DCBreadcrumb;
use Glpi\Features\Kanban;
use Glpi\Features\PlanningEvent;
use ITILFollowup;
use Ticket;

/* Test for inc/toolbox.class.php */

class Toolbox extends DbTestCase {

   public function testGetRandomString() {
      for ($len = 20; $len < 50; $len += 5) {
         // Low strength
         $str = \Toolbox::getRandomString($len);
         $this->integer(strlen($str))->isIdenticalTo($len);
         $this->boolean(ctype_alnum($str))->isTrue();
      }
   }

   public function testRemoveHtmlSpecialChars() {
      $original = 'My - string èé  Ê À ß';
      $expected = 'my - string ee  e a sz';
      $result = \Toolbox::removeHtmlSpecialChars($original);

      $this->string($result)->isIdenticalTo($expected);
   }

   protected function slugifyProvider() {
      return [
         [
            'string'   => 'My - string èé  Ê À ß',
            'expected' => 'my-string-ee-e-a-ss'
         ], [
            //https://github.com/glpi-project/glpi/issues/2946
            'string'   => 'Έρευνα ικανοποίησης - Αιτήματα',
            'expected' => 'ereuna-ikanopoieses-aitemata'
         ], [
            'string'   => 'a-valid-one',
            'expected' => 'a-valid-one',
         ]
      ];
   }

   /**
    * @dataProvider slugifyProvider
    */
   public function testSlugify($string, $expected) {
      $this->string(\Toolbox::slugify($string))->isIdenticalTo($expected);
   }

   protected function filenameProvider() {
      return [
         [
            'name'  => '00-logoteclib.png',
            'expected'  => '00-logoteclib.png',
         ], [
            // Space is missing between "France" and "très" due to a bug in laminas-mail
            'name'  => '01-Screenshot-2018-4-12 Observatoire - Francetrès haut débit.png',
            'expected'  => '01-screenshot-2018-4-12-observatoire-francetres-haut-debit.png',
         ], [
            'name'  => '01-test.JPG',
            'expected'  => '01-test.JPG',
         ], [
            'name'  => '15-image001.png',
            'expected'  => '15-image001.png',
         ], [
            'name'  => '18-blank.gif',
            'expected'  => '18-blank.gif',
         ], [
            'name'  => '19-ʂǷèɕɩɐɫ ȼɦâʁȿ.gif',
            'expected'  => '19-secl-chas.gif',
         ], [
            'name'  => '20-specïal chars.gif',
            'expected'  => '20-special-chars.gif',
         ], [
            'name'  => '24.1-长文件名，将导致内容处置标头中的连续行.txt',
            'expected'  => '24.1-zhang-wen-jian-ming-jiang-dao-zhi-nei-rong-chu-zhi-biao-tou-zhong-de-lian-xu-xing.txt',
         ], [
            'name'  => '24.2-中国字符.txt',
            'expected'  => '24.2-zhong-guo-zi-fu.txt',
         ], [
            'name'  => '25-New Text - Document.txt',
            'expected'  => '25-new-text-document.txt',
         ], [
            'name'     => 'Έρευνα ικανοποίησης - Αιτήματα',
            'expected' => 'ereuna-ikanopoieses-aitemata'
         ], [
            'name'     => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc gravida, nisi vel scelerisque feugiat, tellus purus volutpat justo, vel aliquam nibh nibh sit amet risus. Aenean eget urna et felis molestie elementum nec sit amet magna. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum nec malesuada elit, non luctus mi. Aliquam quis velit justo. Donec id pulvinar nunc. Phasellus.txt',
            'expected' => 'lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit.-nunc-gravida-nisi-vel-scelerisque-feugiat-tellus-purus-volutpat-justo-vel-aliquam-.txt'
         ]
      ];
   }

   /**
    * @dataProvider filenameProvider
    */
   public function testFilename($name, $expected) {
      $this->string(\Toolbox::filename($name))->isIdenticalTo($expected);
      $this->integer(strlen($expected))->isLessThanOrEqualTo(255);
   }

   public function dataGetSize() {
      return [
         [1,                   '1 o'],
         [1025,                '1 Kio'],
         [1100000,             '1.05 Mio'],
         [1100000000,          '1.02 Gio'],
         [1100000000000,       '1 Tio'],
      ];
   }

   /**
    * @dataProvider dataGetSize
    */
   public function testGetSize($input, $expected) {
      $this->string(\Toolbox::getSize($input))->isIdenticalTo($expected);
   }

   public function testGetIPAddress() {
      // Save values
      $saveServer = $_SERVER;

      // Test REMOTE_ADDR
      unset($_SERVER['HTTP_X_FORWARDED_FOR']);
      $_SERVER['REMOTE_ADDR'] = '123.123.123.123';
      $ip = \Toolbox::getRemoteIpAddress();
      $this->variable($ip)->isEqualTo('123.123.123.123');

      // Test HTTP_X_FORWARDED_FOR takes precedence over REMOTE_ADDR
      $_SERVER['HTTP_X_FORWARDED_FOR'] = '231.231.231.231';
      $ip = \Toolbox::getRemoteIpAddress();
      $this->variable($ip)->isEqualTo('231.231.231.231');

      // Restore values
      $_SERVER = $saveServer;
   }

   public function testFormatOutputWebLink() {
      $this->string(\Toolbox::formatOutputWebLink('www.glpi-project.org/'))
         ->isIdenticalTo('http://www.glpi-project.org/');
      $this->string(\Toolbox::formatOutputWebLink('http://www.glpi-project.org/'))
         ->isIdenticalTo('http://www.glpi-project.org/');
      $this->string(\Toolbox::formatOutputWebLink('https://www.glpi-project.org/'))
         ->isIdenticalTo('https://www.glpi-project.org/');
   }

   public function testgetBijectiveIndex() {
      foreach ([
         1   => 'A',
         2   => 'B',
         27  => 'AA',
         28  => 'AB',
         53  => 'BA',
         702 => 'ZZ',
         703 => 'AAA',
      ] as $number => $bij_string) {
         $this->string(\Toolbox::getBijectiveIndex($number))->isIdenticalTo($bij_string);
      }
   }

   protected function cleanIntegerProvider() {
      return [
         [1, '1'],
         ['1', '1'],
         ['a1', '1'],
         ['-1', '-1'],
         ['-a1', '-1'],
      ];
   }

   /**
    * @dataProvider cleanIntegerProvider
    */
   public function testCleanInteger($value, $expected) {
      $this->variable(\Toolbox::cleanInteger($value))->isIdenticalTo($expected);
   }

   protected function jsonDecodeProvider() {
      return [
         [
            '{"Monitor":[6],"Computer":[35]}',
            ['Monitor' => [6], 'Computer' => [35]]
         ], [
            '{\"Monitor\":[\"6\"],\"Computer\":[\"35\"]}',
            ['Monitor' => ["6"], 'Computer' => ["35"]]
         ]
      ];
   }

   /**
    * @dataProvider jsonDecodeProvider
    */
   public function testJsonDecode($json, $expected) {
      $this
         ->variable(\Toolbox::jsonDecode($json, true))
         ->isIdenticalTo($expected);
   }

   public function testJsonDecodeWException() {
      $this->exception(
         function() {
            $this
               ->variable(\Toolbox::jsonDecode('"Monitor":"6","Computer":"35"', true));
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('Unable to decode JSON string! Is this really JSON?');
   }

   protected function ucProvider() {
      return [
         ['hello you', 'Hello you'],
         ['HEllO you', 'HEllO you'],
         ['éè', 'Éè'],
         ['ÉÈ', 'ÉÈ']
      ];
   }

   /**
    * @dataProvider ucProvider
    */
   public function testUcfirst($in, $out) {
      $this->string(\Toolbox::ucfirst($in))->isIdenticalTo($out);
   }

   protected function shortcutProvider() {
      return [
         ['My menu', 'm', '<u>M</u>y menu'],
         ['Do something', 't', 'Do some<u>t</u>hing'],
         ['Any menu entry', 'z', 'Any menu entry'],
         ['Computer', 'O', 'C<u>o</u>mputer']
      ];
   }

   /**
    * @dataProvider shortcutProvider
    */
   public function testShortcut($string, $letter, $expected) {
      $this->string(\Toolbox::shortcut($string, $letter))->isIdenticalTo($expected);
   }

   protected function strposProvider() {
      return [
         ['Where is Charlie?', 'W', 0, 0],
         ['Where is Charlie?', 'W', 1, false],
         ['Where is Charlie?', 'w', 0, false],
         ['Where is Charlie?', '?', 0, 16],
         ['Where is Charlie?', '?', 3, 16],
         ['Where is Charlie?', 'e', 0, 2],
         ['Where is Charlie?', 'e', 2, 2],
         ['Where is Charlie?', 'e', 3, 4],
         ['Où est Charlie ?', 'ù', 0, 1]
      ];
   }

   /**
    * @dataProvider strposProvider
    */
   public function testStrpos($string, $search, $offset, $expected) {
      $this->variable(\Toolbox::strpos($string, $search, $offset))->isIdenticalTo($expected);
   }

   protected function padProvider() {
      return [
         ['GLPI', 10, " ", STR_PAD_RIGHT, 'GLPI      '],
         ['éè', 10, " ", STR_PAD_RIGHT, 'éè        '],
         ['GLPI', 10, " ", STR_PAD_LEFT, '      GLPI'],
         ['éè', 10, " ", STR_PAD_LEFT, '        éè'],
         ['GLPI', 10, " ", STR_PAD_BOTH, '   GLPI   '],
         ['éè', 10, " ", STR_PAD_BOTH, '    éè    '],
         ['123', 10, " ", STR_PAD_BOTH, '   123    ']
      ];
   }

   /**
    * @dataProvider padProvider
    */
   public function testStr_pad($string, $length, $char, $pad, $expected) {
      $this->string(\Toolbox::str_pad($string, $length, $char, $pad))
         ->isIdenticalTo($expected);
   }

   protected function strlenProvider() {
      return [
         ['GLPI', 4],
         ['Où ça ?', 7]
      ];
   }

   /**
    * @dataProvider strlenProvider
    */
   public function testStrlen($string, $length) {
      $this->integer(\Toolbox::strlen($string))->isIdenticalTo($length);
   }

   protected function substrProvider() {
      return [
         ['I want a substring', 0, -1, 'I want a substring'],
         ['I want a substring', 9, -1, 'substring'],
         ['I want a substring', 9, 3, 'sub'],
         ['Caractères accentués', 0, -1, 'Caractères accentués'],
         ['Caractères accentués', 11, -1, 'accentués'],
         ['Caractères accentués', 11, 8, 'accentué']
      ];
   }

   /**
    * @dataProvider substrProvider
    */
   public function testSubstr($string, $start, $length, $expected) {
      $this->string(\Toolbox::substr($string, $start, $length))
         ->isIdenticalTo($expected);
   }

   protected function lowercaseProvider() {
      return [
         ['GLPI', 'glpi'],
         ['ÉÈ', 'éè'],
         ['glpi', 'glpi']
      ];
   }

   /**
    * @dataProvider lowercaseProvider
    */
   public function testStrtolower($upper, $lower) {
      $this->string(\Toolbox::strtolower($upper))->isIdenticalTo($lower);
   }

   protected function uppercaseProvider() {
      return [
         ['glpi', 'GLPI'],
         ['éè', 'ÉÈ'],
         ['GlPI', 'GLPI']
      ];
   }

   /**
    * @dataProvider uppercaseProvider
    */
   public function testStrtoupper($lower, $upper) {
      $this->string(\Toolbox::strtoupper($lower))->isIdenticalTo($upper);
   }

   protected function utfProvider() {
      return [
         ['a simple string', true],
         ['caractère', true],
         [mb_convert_encoding('caractère', 'ISO-8859-15'), false],
         [mb_convert_encoding('simple string', 'ISO-8859-15'), true]
      ];
   }

   /**
    * @dataProvider utfProvider
    */
   public function testSeems_utf8($string, $utf) {
      $this->boolean(\Toolbox::seems_utf8($string))->isIdenticalTo($utf);
   }

   protected function encryptProvider() {
      return [
         ['My string', 'mykey', 'xuaZ3tnr1ufS'],
         ['keepmysecret', 'keepmykey', '5NDK1d3m7NDI69DZ']
      ];
   }

   protected function sodiumEncryptProvider() {
      return [
         ['My string'],
         ['keepmysecret'],
         ['This is a strng I want to crypt, with some unusual chars like %, \', @, and so on!']
      ];
   }

   /**
    * @dataProvider sodiumEncryptProvider
    */
   public function testSodiumEncrypt($string) {
      $crypted = \Toolbox::sodiumEncrypt($string);
      $this->string($crypted)->isNotEmpty();
      $this->string(\Toolbox::sodiumDecrypt($crypted))->isIdenticalTo($string);
   }

   /**
    * Test blank or null content. If not handled correctly, a sodium exception would be raised and fail the test.
    * This could be a blank password that was never encrypted, so it is a blank value in the DB still.
    * @since 9.5.0
    */
   public function testSodiumDecryptBlank() {
      $this->variable(\Toolbox::sodiumDecrypt(null))->isNull();
      $this->string(\Toolbox::sodiumDecrypt(''))->isEmpty();
   }

   /**
    * Test invalid content. If not handled correctly, following sodium exception would be raised and fail the test.
    * "SodiumException: public nonce size should be SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES bytes"
    */
   public function testSodiumDecryptInvalid() {
      $result = null;

      $this->when(
         function () use (&$result) {
            $result = \Toolbox::sodiumDecrypt('not a valid value');
         }
      )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to extract nonce from content. It may not have been crypted with sodium functions.')
         ->exists();

      $this->string($result)->isEmpty();
   }

   /**
    * Test content crypted with another key.
    */
   public function testSodiumDecryptBadKey() {
      $result = null;

      $this->when(
         function () use (&$result) {
            // 'test' string crypted with a valid key used just for that
            $result = \Toolbox::sodiumDecrypt('CUdPSEgzKroDOwM1F8lbC8WDcQUkGCxIZpdTEpp5W/PLSb70WmkaKP0Q7QY=');
         }
      )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to decrypt content. It may have been crypted with another key.')
         ->exists();

      $this->string($result)->isEmpty();
   }

   protected function cleanProvider() {
      return [
         ['mystring', 'mystring', null, 15, 0.56, false],
         ['<strong>string</strong>', '&#60;strong&#62;string&#60;/strong&#62;', null, 15, 0.56, false],
         [
            [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
            [null, '&#60;strong&#62;string&#60;/strong&#62;', 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798]
         ]
      ];
   }

   protected function uncleanProvider() {
      $dataset = $this->cleanProvider();

      // Data produced by old XSS cleaning process
      $dataset[] = ['<strong>string</strong>', '&lt;strong&gt;string&lt;/strong&gt;', null, 15, 0.56, false];
      $dataset[] = [
         [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
         [null, '&lt;strong&gt;string&lt;/strong&gt;', 3.2, 'string', true, '&lt;p&gt;my&lt;/p&gt;', 9798]
      ];

      return $dataset;
   }

   /**
    * @dataProvider cleanProvider
    */
   public function testClean_cross_side_scripting_deep($value, $expected) {
      $this->variable(\Toolbox::clean_cross_side_scripting_deep($value))
         ->isIdenticalTo($expected);
   }

   /**
    * @dataProvider uncleanProvider
    */
   public function testUnclean_cross_side_scripting_deep($expected, $value) {
      $this->variable(\Toolbox::unclean_cross_side_scripting_deep($value))
         ->isIdenticalTo($expected);
   }

   public function testSaveAndDeletePicture() {
      // Save an image twice
      $test_file = __DIR__ . '/../files/test.png';
      copy(__DIR__ . '/../../pics/add_dropdown.png', $test_file); // saved image will be removed from FS
      $first_pict = \Toolbox::savePicture($test_file);
      $this->string($first_pict)->matches('#[^/]+/.+\.png#'); // generated random name inside subdir

      copy(__DIR__ . '/../../pics/add_dropdown.png', $test_file); // saved image will be removed from FS
      $second_pict = \Toolbox::savePicture($test_file);
      $this->string($second_pict)->matches('#[^/]+/.+\.png#'); // generated random name inside subdir

      // Check that second saving of same image is not overriding first saved image.
      $this->string($first_pict)->isNotEqualTo($second_pict);

      // Delete saved images
      $this->boolean(\Toolbox::deletePicture($first_pict))->isTrue();
      $this->boolean(\Toolbox::deletePicture($second_pict))->isTrue();

      // Save not an image
      $this->boolean(\Toolbox::savePicture(__DIR__ . '/../notanimage.jpg'))->isFalse();

      // Save and delete unexisting files
      $this->boolean(\Toolbox::savePicture('notafile.jpg'))->isFalse();
      $this->boolean(\Toolbox::deletePicture('notafile.jpg'))->isFalse();
   }

   protected function getPictureUrlProvider() {
      global $CFG_GLPI;

      return [
         [
            'path' => '',
            'url'  => null,
         ],
         [
            'path' => 'image.jpg',
            'url'  => $CFG_GLPI['root_doc'] . '/front/document.send.php?file=_pictures/image.jpg',
         ],
         [
            'path' => 'xss\' onclick="alert(\'PWNED\')".jpg',
            'url'  => $CFG_GLPI['root_doc'] . '/front/document.send.php?file=_pictures/xss&apos; onclick=&quot;alert(&apos;PWNED&apos;)&quot;.jpg',
         ],
      ];
   }

   /**
    * @dataProvider getPictureUrlProvider
    */
   public function testGetPictureUrl($path, $url) {
      $this->variable(\Toolbox::getPictureUrl($path))->isIdenticalTo($url);
   }

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
      $expected_result = \Toolbox::clean_cross_side_scripting_deep($expected_result);

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
      $expected_result = \Toolbox::clean_cross_side_scripting_deep($expected_result);

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
      $expected_result = \Toolbox::clean_cross_side_scripting_deep($expected_result);

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
      $expected_result_1 = \Toolbox::clean_cross_side_scripting_deep($expected_result_1);
      $expected_result_2 = \Toolbox::clean_cross_side_scripting_deep($expected_result_2);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id_1 => ['tag' => $img_tag]])
      )->isEqualTo($expected_result_1);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id_2 => ['tag' => $img_tag]])
      )->isEqualTo($expected_result_2);
   }

   /**
    * Check conversion of tags to images when content contains multiple times same inlined image.
    */
   public function testConvertTagToImageWithDuplicatedInlinedImg() {

      $img_tag = uniqid('', true);

      $item = new \Ticket();
      $item->fields['id'] = mt_rand(1, 50);

      // Create multiple documents in DB
      $document = new \Document();
      $doc_id = $document->add([
         'name'     => 'img 1',
         'filename' => 'img.png',
         'mime'     => 'image/png',
         'tag'      => $img_tag,
      ]);
      $this->integer((int)$doc_id)->isGreaterThan(0);

      $content_text     = '<img id="' . $img_tag . '" width="10" height="10" />';
      $content_text    .= $content_text;
      $expected_url     = '/front/document.send.php?docid=' . $doc_id . '&tickets_id=' . $item->fields['id'];
      $expected_result  = '<a href="' . $expected_url . '" target="_blank" ><img alt="' . $img_tag . '" width="10" src="' . $expected_url . '" /></a>';
      $expected_result .= $expected_result;

      // Processed data is expected to be escaped
      $content_text = \Toolbox::addslashes_deep($content_text);
      $expected_result = \Toolbox::clean_cross_side_scripting_deep($expected_result);

      $this->string(
         \Toolbox::convertTagToImage($content_text, $item, [$doc_id => ['tag' => $img_tag]])
      )->isEqualTo($expected_result);
   }

   protected function shortenNumbers() {
      return [
         [
            'number'    => 1500,
            'precision' => 1,
            'expected'  => '1.5K',
         ], [
            'number'    => 1600,
            'precision' => 0,
            'expected'  => '2K',
         ], [
            'number'    => 1600000,
            'precision' => 1,
            'expected'  => '1.6M',
         ], [
            'number'    => 1660000,
            'precision' => 1,
            'expected'  => '1.7M',
         ], [
            'number'    => 1600000000,
            'precision' => 1,
            'expected'  => '1.6B',
         ], [
            'number'    => 1600000000000,
            'precision' => 1,
            'expected'  => '1.6T',
         ], [
            'number'    => "14%",
            'precision' => 1,
            'expected'  => '14%',
         ], [
            'number'    => "test",
            'precision' => 1,
            'expected'  => 'test',
         ]
      ];
   }

   /**
    * @dataProvider shortenNumbers
    */
   public function testShortenNumber($number, int $precision, string $expected) {
      $this->string(\Toolbox::shortenNumber($number, $precision, false))
         ->isEqualTo($expected);
   }

   protected function colors() {
      return [
         [
            'bg_color' => "#FFFFFF",
            'offset'   => 40,
            'fg_color' => '#999999',
         ], [
            'bg_color' => "#FFFFFF",
            'offset'   => 50,
            'fg_color' => '#7f7f7f',
         ], [
            'bg_color' => "#000000",
            'offset'   => 40,
            'fg_color' => '#666666',
         ], [
            'bg_color' => "#000000",
            'offset'   => 50,
            'fg_color' => '#7f7f7f',
         ],
      ];
   }

   /**
    * @dataProvider colors
    */
   public function testGetFgColor(string $bg_color, int $offset, string $fg_color) {
      $this->string(\Toolbox::getFgColor($bg_color, $offset))
         ->isEqualTo($fg_color);
   }

   protected function testIsCommonDBTMProvider() {
      return [
         [
            'class'         => TicketFollowup::class,
            'is_commondbtm' => false,
         ],
         [
            'class'         => Ticket::class,
            'is_commondbtm' => true,
         ],
         [
            'class'         => ITILFollowup::class,
            'is_commondbtm' => true,
         ],
         [
            'class'         => "Not a real class",
            'is_commondbtm' => false,
         ],
      ];
   }

   /**
    * @dataProvider testIsCommonDBTMProvider
    */
   public function testIsCommonDBTM(string $class, bool $is_commondbtm) {
      $this->boolean(\Toolbox::isCommonDBTM($class))->isEqualTo($is_commondbtm);
   }

   protected function testIsAPIDeprecatedProvider() {
      return [
         [
            'class'         => TicketFollowup::class,
            'is_deprecated' => true,
         ],
         [
            'class'         => Ticket::class,
            'is_deprecated' => false,
         ],
         [
            'class'         => ITILFollowup::class,
            'is_deprecated' => false,
         ],
         [
            'class'         => "Not a real class",
            'is_deprecated' => false,
         ],
      ];
   }

   /**
    * @dataProvider testIsAPIDeprecatedProvider
    */
   public function testIsAPIDeprecated(string $class, bool $is_deprecated) {
      $this->boolean(\Toolbox::isAPIDeprecated($class))->isEqualTo($is_deprecated);
   }

   protected function urlProvider() {
      return [
         ['http://localhost', true],
         ['https://localhost', true],
         ['https;//localhost', false],
         ['https://glpi-project.org', true],
         ['https://glpi+project-org', false],
         [' http://my.host.com', false],
         ['http://my.host.com', true],
         ['http://my.host.com/', true],
         ['http://my.host.com/glpi/', true],
         ['http://my.host.com /', false],
         ['http://localhost:8080', true],
         ['http://localhost:8080/', true],
         ['http://my.host.com:8080/glpi/', true],
         ['http://my.host.com:8080 /', false],
         ['http://my.host.com: 8080/', false],
         ['http://my.host.com :8080/', false],
         ['http://helpdesk.global.glpi-project.org', true],
         ['http://dev.helpdesk.global.glpi-project.org', true],
         ['http://127.0.0.1', true],
         ['http://127.0.0.1/glpi', true],
         ['http://127.0.0.1:8080', true],
         ['http://127.0.0.1:8080/', true],
         ['http://127.0.0.1 :8080/', false],
         ['http://127.0.0.1 :8080 /', false],
         ['http://::1', true],
         ['http://::1/glpi', true],
         ['http://::1:8080/', true],
         ['http://::1:8080/', true],
         ['HTTPS://::1:8080/', true],
         ['www.my.host.com', false],
         ['127.0.0.1', false],
         ['::1', false],
         ['http://my.host.com/subdir/glpi/', true],
         ['http://my.host.com/~subdir/glpi/', true],
         ['https://localhost<', false],
         ['https://localhost"', false],
         ['https://localhost\'', false],
         ['https://localhost?test=true', true],
         ['https://localhost?test=true&othertest=false', true],
         ['https://localhost/front/computer.php?is_deleted=0&as_map=0&criteria[0][link]=AND&criteria[0][field]=80&criteria[0][searchtype]=equals&criteria[0][value]=254&search=Search&itemtype=Computer', true],
      ];
   }

   /**
    * @dataProvider urlProvider
    */
   public function testIsValidWebUrl($url, $result) {
      $this->boolean(\Toolbox::isValidWebUrl($url))->isIdenticalTo((bool)$result, $url);
   }

   public function testDeprecated() {
      $this->when(
         function () {
            \Toolbox::deprecated('Calling this function is deprecated');
         }
      )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('Calling this function is deprecated')
         ->exists();
   }

   public function hasTraitProvider() {
      return [
         [\Computer::class, Clonable::class, true],
         [\Monitor::class, Clonable::class, true],
         [\CommonITILObject::class, Clonable::class, true],
         [\Ticket::class, Clonable::class, true],
         [\Plugin::class, Clonable::class, false],
         [\Project::class, Kanban::class, true],
         [\Computer::class, Kanban::class, false],
         [\Computer::class, DCBreadcrumb::class, true],
         [\Ticket::class, DCBreadcrumb::class, false],
         [\CommonITILTask::class, PlanningEvent::class, true],
         [\Computer::class, PlanningEvent::class, false],
      ];
   }

   /**
    * @dataProvider hasTraitProvider
    */
   public function testHasTrait($class, $trait, $result) {
      $this->boolean(\Toolbox::hasTrait($class, $trait))->isIdenticalTo((bool)$result);
   }

   public function appendParametersProvider() {
      return [
         [
            [
               'a'   => 'test1',
               'b'   => 'test2'
            ], '&', 'a=test1&b=test2'
         ],
         [
            [
               'a'   => [
                  'test1', 'test2'
               ],
               'b'   => 'test3'
            ], '&', 'a%5B0%5D=test1&a%5B1%5D=test2&b=test3' // '[' converted to %5B, ']' converted to %5D
         ],
         [
            [
               'a'   => [
                  'test1', 'test2'
               ],
               'b'   => 'test3*'
            ], '&', 'a%5B0%5D=test1&a%5B1%5D=test2&b=test3%2A' // '[' converted to %5B, ']' converted to %5D
         ],
         [
            [
               'a'   => 'test1',
               'b'   => 'test2'
            ], '_', 'a=test1_b=test2'
         ],
         [
            [
               'a'   => [
                  'test1', 'test2'
               ],
               'b'   => 'test3'
            ], '_', 'a%5B0%5D=test1_a%5B1%5D=test2_b=test3' // '[' converted to %5B, ']' converted to %5D
         ]
      ];
   }

   /**
    * @dataProvider appendParametersProvider
    */
   public function testAppendParameters(array $params, string $separator, string $expected) {
      $this->string(\Toolbox::append_params($params, $separator))->isEqualTo($expected);
   }
}
