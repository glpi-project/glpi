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

use \DbTestCase;

require_once __DIR__ . '/../Autoload.php';

/* Test for inc/autoload.function.php */

class Autoload extends DbTestCase {

   public function dataItemType() {
      return [
         ['Computer',                   false, false],
         ['Glpi\\Event',                false, false],
         ['PluginFooBar',               'Foo', 'Bar'],
         ['GlpiPlugin\\Foo\\Bar',       'Foo', 'Bar'],
         ['GlpiPlugin\\Foo\\Bar\\More', 'Foo', 'Bar\\More'],
      ];
   }

   /**
    * @dataProvider dataItemType
    **/
   public function testIsPluginItemType($type, $plug, $class) {
      $res = isPluginItemType($type);
      if ($plug) {
         $this->array($res)
            ->isIdenticalTo([
               'plugin' => $plug,
               'class'  => $class
            ]);
      } else {
         $this->boolean($res)->isFalse;
      }
   }

   /**
    * @extensions event
    */
   public function testAutoloadEvent() {
      $this->boolean(class_exists('Event'))->isTrue();
   }

   public function testAutoloadGlpiEvent() {
      $this->boolean(class_exists('Glpi\\Event'))->isTrue();
      if (class_exists('Event', false)) {
         //tested if pecl/event is not present
         $this->boolean(class_exists('Event'))->isTrue();
      }
   }
}
