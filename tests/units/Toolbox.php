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

/* Test for inc/toolbox.class.php */

class Toolbox extends \GLPITestCase {

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
            'string'    => 'My - string èé  Ê À ß',
            'expcected' => 'my-string-ee-e-a-sz'
         ], [
            //https://github.com/glpi-project/glpi/issues/2946
            'string'    => 'Έρευνα ικανοποίησης - Αιτήματα',
            'expcected' => 'ereuna-ikanopoieses-aitemata'
         ]
      ];
   }

   /**
    * @dataProvider slugifyProvider
    */
   public function testSlugify($string, $expected) {
      $this->string(\Toolbox::slugify($string))->isIdenticalTo($expected);
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

   public function testUncleanHtmlCrossSideScriptingDeepImageBase64() {
      $image_base_64 = html_entity_decode('&lt;p&gt; &lt;img id="c4b1c031-4c85d722-5a3136eacbe3a9.88695094" src="data:image/png;base64,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" width="251" height="245" /&gt;&lt;/p&gt;&lt;p&gt; &lt;/p&gt;');

      $this->string(\Toolbox::unclean_html_cross_side_scripting_deep($image_base_64))
         ->notContains('denied:');
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

   /**
    * @dataProvider encryptProvider
    */
   public function testEncrypt($string, $key, $expected) {
      $this->string(\Toolbox::encrypt($string, $key))->isIdenticalTo($expected);
   }

   /**
    * @dataProvider encryptProvider
    */
   public function testDecrypt($expected, $key, $string) {
      $this->string(\Toolbox::decrypt($string, $key))->isIdenticalTo($expected);
   }

   protected function cleanProvider() {
      return [
         ['mystring', 'mystring'],
         ['<strong>string</strong>', '&lt;strong&gt;string&lt;/strong&gt;'],
         [
            ['<strong>string</strong>', 'string', '<p>my</p>'],
            ['&lt;strong&gt;string&lt;/strong&gt;', 'string', '&lt;p&gt;my&lt;/p&gt;']
         ]
      ];
   }

   /**
    * @dataProvider cleanProvider
    */
   public function testClean_cross_side_scripting_deep($value, $expected) {
      $this->variable(\Toolbox::clean_cross_side_scripting_deep($value))
         ->isIdenticalTo($expected);
   }

   /**
    * @dataProvider cleanProvider
    */
   public function testUnclean_cross_side_scripting_deep($expected, $value) {
      $this->variable(\Toolbox::unclean_cross_side_scripting_deep($value))
         ->isIdenticalTo($expected);
   }

   protected function cleanHtmlProvider() {
      $dataset = $this->cleanProvider();
      $dataset[] = [
         ['<div>Here a list example: <pre>&lt;ul&gt;&lt;li&gt;one&lt;/li&gt;&lt;li&gt;two&lt;/li&gt;&lt;/ul&gt;</pre></div>'],
         ['&lt;div&gt;Here a list example: &lt;pre&gt;&lt;ul&gt;&lt;li&gt;one&lt;/li&gt;&lt;li&gt;two&lt;/li&gt;&lt;/ul&gt;&lt;/pre&gt;']
      ];
      return $dataset;
   }

   /**
    * @dataProvider cleanHtmlProvider
    */
   public function testUnclean_html_cross_side_scripting_deep($expected, $value) {
      $this->variable(\Toolbox::unclean_html_cross_side_scripting_deep($value))
         ->isIdenticalTo($expected);
   }
}
