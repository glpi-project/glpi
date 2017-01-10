<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/html.class.php */

class ToolboxTest extends PHPUnit_Framework_TestCase {

   /**
    * @covers Toolbox::getRandomString
    */
   public function testGetRandomString() {

      for ($len = 20; $len < 50; $len += 5) {
         // Low strength
         $str = Toolbox::getRandomString($len);
         $this->assertEquals($len, strlen($str));
         $this->assertTrue(ctype_alnum($str));
         // High strength
         $str = Toolbox::getRandomString($len, true);
         $this->assertEquals($len, strlen($str));
         $this->assertTrue(ctype_alnum ($str) );
      }
   }

   public function testRemoveHtmlSpecialChars() {
      $original = 'My - string èé  Ê À ß';
      $expected = 'my - string ee  e a sz';
      $result = Toolbox::removeHtmlSpecialChars($original);

      $this->assertEquals($expected, $result);
   }

   public function testSlugify() {
      $original = 'My - string èé  Ê À ß';
      $expected = 'my-string-ee-e-a-sz';
      $result = Toolbox::slugify($original);

      $this->assertEquals($expected, $result);

   }
}
