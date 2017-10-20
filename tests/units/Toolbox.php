<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use \atoum;

/* Test for inc/toolbox.class.php */

class Toolbox extends atoum {

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

   public function testSlugify() {
      $original = 'My - string èé  Ê À ß';
      $expected = 'my-string-ee-e-a-sz';
      $result = \Toolbox::slugify($original);

      $this->string($result)->isIdenticalTo($expected);

      //https://github.com/glpi-project/glpi/issues/2946
      $original = 'Έρευνα ικανοποίησης - Αιτήματα'; //el_GR
      $result = \Toolbox::slugify($original);
      $this->string($result)->startWith('nok_')
         ->length->isIdenticalTo(10 + strlen('nok_'));
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
}
