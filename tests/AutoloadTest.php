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

/* Test for inc/autoload.function.php */

class AutoloadTest extends DbTestCase {

   public function dataItemType() {
      return [
         ['Computer',               false, false],
         ['Glpi\\Event',            false, false],
         ['PluginFooBar',           'Foo', 'Bar'],
         ['Plugin\\Foo\\Bar',       'Foo', 'Bar'],
         ['Plugin\\Foo\\Bar\\More', 'Foo', 'Bar\\More'],
      ];
   }

   /**
    * @covers ::isPluginItemType
    * @dataProvider dataItemType
    **/
   public function testIsPluginItemType($type, $plug, $class) {

      $res = isPluginItemType($type);
      if ($plug) {
         $this->assertEquals($plug, $res['plugin'], 'Plugin name');
         $this->assertEquals($class, $res['class'], 'Class name');
      } else {
         $this->assertFalse($res);
      }
   }

   public function testAutoloadEvent() {
      if (class_exists('Event', false)) {
         $this->markTestSkipped('pecl/event extension loaded');
      }
      $this->assertTrue(class_exists('Event'));
   }

   public function testAutoloadGlpiEvent() {
      $this->assertTrue(class_exists('Glpi\\Event'));
   }
}
