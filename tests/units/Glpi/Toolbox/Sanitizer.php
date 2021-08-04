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

namespace tests\units\Glpi\Toolbox;

/**
 * Test class for src/Glpi/Toolbox/sanitizer.class.php
 */
class Sanitizer extends \GLPITestCase {

   protected function rawValueProvider(): iterable {
      // Non string values should not be altered
      yield [
         'value'           => null,
         'sanitized_value' => null,
      ];
      yield [
         'value'           => false,
         'sanitized_value' => false,
      ];
      yield [
         'value'           => 15,
         'sanitized_value' => 15,
      ];
      yield [
         'value'           => 0.56,
         'sanitized_value' => 0.56,
      ];
      $tempfile = tmpfile();
      yield [
         'value'           => $tempfile,
         'sanitized_value' => $tempfile,
      ];

      // Strings should be sanitized
      yield [
         'value'           => 'mystring',
         'sanitized_value' => 'mystring',
      ];
      yield [
         'value'           => '5 > 1',
         'sanitized_value' => '5 &#62; 1',
      ];
      yield [
         'value'           => '<strong>string</strong>',
         'sanitized_value' => '&#60;strong&#62;string&#60;/strong&#62;',
      ];
      yield [
         'value'           => "<strong>text with slashable chars ' \n \"</strong>",
         'sanitized_value' => "&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;",
         'add_slashes'     => true,
      ];

      // Strings in array should be sanitized
      yield [
         'value'           => [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
         'sanitized_value' => [null, '&#60;strong&#62;string&#60;/strong&#62;', 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
      ];
      yield [
         'value'           => [null, "<strong>text with slashable chars ' \n \"</strong>", 3.2, 'string', true, '<p>my</p>', 9798],
         'sanitized_value' => [null, "&#60;strong&#62;text with slashable chars \' \\n \\\"&#60;/strong&#62;", 3.2, 'string', true, '&#60;p&#62;my&#60;/p&#62;', 9798],
         'add_slashes'     => true,
      ];
   }

   /**
    * @dataProvider rawValueProvider
    */
   public function testSanitize(
      $value,
      $sanitized_value,
      bool $add_slashes = false
   ) {
      $sanitizer = $this->newTestedInstance();
      $this->variable($sanitizer->sanitize($value, $add_slashes))->isEqualTo($sanitized_value);
   }

   protected function sanitizedValueProvider(): iterable {
      foreach ($this->rawValueProvider() as $data) {
         yield [
            'value'             => $data['sanitized_value'],
            'unsanitized_value' => $data['value'],
            'strip_slashes'     => $data['add_slashes'] ?? false,
         ];
      }

      // Data produced by old XSS cleaning process
      yield [
         'value'             => '&lt;strong&gt;string&lt;/strong&gt;',
         'unsanitized_value' => '<strong>string</strong>',
      ];
      yield [
         'value'             => [null, '&lt;strong&gt;string&lt;/strong&gt;', 3.2, 'string', true, '&lt;p&gt;my&lt;/p&gt;', 9798],
         'unsanitized_value' => [null, '<strong>string</strong>', 3.2, 'string', true, '<p>my</p>', 9798],
      ];
   }

   /**
    * @dataProvider sanitizedValueProvider
    */
   public function testUnanitize(
      $value,
      $unsanitized_value,
      bool $strip_slashes = false
   ) {
      $sanitizer = $this->newTestedInstance();
      $this->variable($sanitizer->unsanitize($value, $strip_slashes))->isEqualTo($unsanitized_value);
   }

   /**
    * @dataProvider sanitizedValueProvider
    */
   public function isSanitized(
      $value,
      $unsanitized_value
   ) {
      $sanitizer = $this->newTestedInstance();
      $this->boolean($sanitizer->isSanitized($value))->isEqualTo($unsanitized_value === $value);
   }
}
