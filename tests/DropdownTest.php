<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2015 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

/* Test for inc/dropdown.class.php */

class DropdownTest extends DbTestCase {

   /**
    * @covers Printer::add
    */
   public function testShowLanguages() {

      $opt = [ 'display_emptychoice' => true, 'display' => false ];
      $out = Dropdown::showLanguages('dropfoo', $opt);
      $this->assertContains("name='dropfoo'", $out);
      $this->assertContains("value='' selected", $out);
      $this->assertNotContains("value='0'", $out);
      $this->assertContains("value='fr_FR'", $out);

      $opt = [ 'display' => false, 'value' => 'cs_CZ', 'rand' => '1234' ];
      $out = Dropdown::showLanguages('language', $opt);
      $this->assertNotContains("value=''", $out);
      $this->assertNotContains("value='0'", $out);
      $this->assertContains("name='language' id='dropdown_language1234", $out);
      $this->assertContains("value='cs_CZ' selected", $out);
      $this->assertContains("value='fr_FR'", $out);
   }
}
